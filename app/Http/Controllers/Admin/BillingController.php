<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MoneybirdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        private MoneybirdService $moneybirdService
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
        $orders = Order::with(['customer', 'hostingPackage'])
            ->where('status', 'active')
            ->where('next_billing_date', '<=', now()->addDays(7))
            ->latest('next_billing_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Create invoice for an order in Moneybird.
     */
    public function createInvoice(Order $order): JsonResponse
    {
        try {
            if (!$order->customer->moneybird_contact_id) {
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
}
