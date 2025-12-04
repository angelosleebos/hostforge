<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\CreateOrderAction;
use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\DomainData;
use App\DataTransferObjects\OrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\DataCollection;
use Mollie\Laravel\Facades\Mollie;

final class OrderController extends Controller
{
    public function __construct(
        private readonly CreateOrderAction $createOrderAction,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    /**
     * Store a new order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            // Get validated data
            $validated = $request->validated();
            
            // Create DTOs
            $customerData = CustomerData::from($validated['customer']);

            // Parse domain data from array
            $domains = [];
            foreach ($validated['order']['domains'] as $domainInput) {
                $domains[] = DomainData::from([
                    'domain_name' => $domainInput['domain_name'] . '.' . $domainInput['tld'],
                    'tld' => '.' . $domainInput['tld'],
                    'order_id' => 0, // Will be set in action
                    'customer_id' => 0, // Will be set in action
                    'status' => 'pending',
                    'register_domain' => $domainInput['register_domain'],
                ]);
            }

            $orderData = OrderData::from([
                'customer_id' => 0, // Will be set in action
                'hosting_package_id' => $validated['order']['hosting_package_id'],
                'billing_cycle' => $validated['order']['billing_cycle'],
                'domains' => new DataCollection(DomainData::class, $domains),
            ]);

            // Execute action
            $order = $this->createOrderAction->execute($customerData, $orderData);

            $paymentUrl = null;

            // Skip Mollie in local development if account is suspended
            if (config('services.mollie.key') && !str_starts_with(config('services.mollie.key'), 'test_')) {
                // Create Mollie payment using direct API
                $paymentData = [
                    'amount' => [
                        'currency' => 'EUR',
                        'value' => number_format((float) $order->total, 2, '.', ''),
                    ],
                    'description' => "Bestelling #{$order->order_number}",
                    'redirectUrl' => config('app.url') . "/payment/return?order={$order->order_number}",
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_id' => $order->customer_id,
                    ],
                ];

                // Only add webhook in production (localhost not reachable by Mollie)
                if (!app()->environment('local')) {
                    $paymentData['webhookUrl'] = route('api.webhooks.mollie');
                }

                $payment = Mollie::api()->payments->create($paymentData);
                $paymentUrl = $payment->getCheckoutUrl();
            } else {
                // In development with test key, return a mock URL
                $paymentUrl = config('app.url') . "/payment/return?order={$order->order_number}&mock=true";
            }

            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol geplaatst',
                'data' => [
                    'order' => new OrderResource($order),
                    'payment_url' => $paymentUrl,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Er ging iets mis bij het plaatsen van de bestelling',
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Bestelling niet gevonden',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}
