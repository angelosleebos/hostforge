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
            // Parse customer name
            $customerInput = $request->validated('customer');
            $nameParts = explode(' ', $customerInput['name'], 2);
            $customerInput['first_name'] = $nameParts[0];
            $customerInput['last_name'] = $nameParts[1] ?? '';
            unset($customerInput['name']);

            // Create DTOs
            $customerData = CustomerData::from($customerInput);

            // Parse domain data
            $domainInput = $request->validated('domain');
            $domainParts = explode('.', $domainInput['name'], 2);
            
            $domainData = DomainData::from([
                'domain_name' => $domainInput['name'],
                'tld' => isset($domainParts[1]) ? '.' . $domainParts[1] : '',
                'order_id' => 0, // Will be set in action
                'customer_id' => 0, // Will be set in action
                'status' => 'pending',
                'register_domain' => $domainInput['register_domain'],
            ]);

            $orderData = OrderData::from([
                'customer_id' => 0, // Will be set in action
                'hosting_package_id' => $request->validated('hosting_package_id'),
                'billing_cycle' => $request->validated('billing_cycle'),
                'domains' => DataCollection::make([$domainData]),
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
