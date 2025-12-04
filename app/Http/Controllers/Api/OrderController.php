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
                'domains' => DataCollection::make($domains),
            ]);

            // Execute action
            $order = $this->createOrderAction->execute($customerData, $orderData);

            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol geplaatst',
                'data' => new OrderResource($order),
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
