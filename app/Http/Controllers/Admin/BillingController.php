<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\MoneybirdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class BillingController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly MoneybirdService $moneybirdService
    ) {}

    /**
     * Get billing overview.
     */
    public function index(Request $request): JsonResponse
    {
        $stats = [
            'total_revenue' => Order::whereIn('status', ['active', 'processing'])
                ->sum('price'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'active_subscriptions' => Order::where('status', 'active')->count(),
            'next_billing_batch' => Order::where('status', 'active')
                ->where('next_billing_date', '<=', now()->addDays(7))
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get orders due for billing.
     */
    public function dueOrders(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getDueForInvoicing(7);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders)->response()->getData(),
        ]);
    }

    /**
     * Create invoice for an order in Moneybird.
     */
    public function createInvoice(Order $order): JsonResponse
    {
        try {
            if (! $order->customer->moneybird_contact_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Klant is nog niet gesynchroniseerd met Moneybird',
                ], 400);
            }

            $invoiceData = [
                'contact_id' => $order->customer->moneybird_contact_id,
                'invoice_date' => now()->format('Y-m-d'),
                'details_attributes' => [
                    [
                        'description' => "{$order->hostingPackage->name} - {$order->billing_cycle}",
                        'price' => $order->price,
                        'amount' => 1,
                    ],
                ],
            ];

            $invoice = $this->moneybirdService->createInvoice($invoiceData);

            return response()->json([
                'success' => true,
                'message' => 'Factuur aangemaakt in Moneybird',
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon factuur niet aanmaken',
            ], 500);
        }
    }

    /**
     * Sync customer to Moneybird.
     */
    public function syncCustomer(Order $order): JsonResponse
    {
        try {
            $customer = $order->customer;

            if ($customer->moneybird_contact_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Klant is al gesynchroniseerd',
                ], 400);
            }

            $contactData = [
                'company_name' => $customer->company ?? $customer->name,
                'firstname' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address1' => $customer->address,
                'zipcode' => $customer->postal_code,
                'city' => $customer->city,
                'country' => $customer->country,
            ];

            $contact = $this->moneybirdService->createContact($contactData);

            $customer->update([
                'moneybird_contact_id' => $contact['id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Klant gesynchroniseerd met Moneybird',
                'data' => $contact,
            ]);

        } catch (\Exception $e) {
            Log::error('Customer sync to Moneybird failed', [
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon klant niet synchroniseren',
            ], 500);
        }
    }

    /**
     * Get billing statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_revenue' => Order::whereIn('status', ['active', 'processing'])->sum('price'),
                'monthly_revenue' => Order::whereIn('status', ['active', 'processing'])
                    ->whereMonth('created_at', now()->month)
                    ->sum('price'),
                'pending_invoices' => Order::where('status', 'pending')->count(),
                'overdue_invoices' => Order::where('status', 'active')
                    ->where('next_billing_date', '<', now())
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch billing stats', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Kon statistieken niet ophalen'], 500);
        }
    }

    /**
     * Get list of invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        try {
            // For now return mock data, later integrate with Moneybird API
            $invoices = Order::with('customer', 'hostingPackage')
                ->whereIn('status', ['active', 'processing'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => OrderResource::collection($invoices)->response()->getData(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch invoices', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Kon facturen niet ophalen'], 500);
        }
    }

    /**
     * Generate invoices for all due orders.
     */
    public function generateInvoices(): JsonResponse
    {
        try {
            $dueOrders = Order::where('status', 'active')
                ->where('next_billing_date', '<=', now())
                ->get();

            $generated = 0;
            $failed = 0;

            foreach ($dueOrders as $order) {
                try {
                    if ($order->customer->moneybird_contact_id) {
                        // Dispatch job to create invoice
                        dispatch(new \App\Jobs\CreateInvoiceJob($order));
                        $generated++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to generate invoice', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$generated} facturen gegenereerd, {$failed} gefaald",
                'data' => [
                    'generated' => $generated,
                    'failed' => $failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Batch invoice generation failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Kon facturen niet genereren'], 500);
        }
    }
}
