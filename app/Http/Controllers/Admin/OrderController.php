<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Order\ApproveOrderAction;
use App\Actions\Order\CancelOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ApproveOrderAction $approveOrderAction,
        private readonly CancelOrderAction $cancelOrderAction,
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'search' => $request->input('search'),
        ];

        $orders = $this->orderRepository->paginate(
            array_filter($filters),
            (int) $request->input('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders)->response()->getData(),
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order = $this->orderRepository->find($order->id);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $newStatus = $request->validated('status');

            // Handle status changes with actions
            if ($newStatus === 'processing' && $order->status === 'pending') {
                $order = $this->approveOrderAction->execute($order);
            } elseif ($newStatus === 'cancelled') {
                $order = $this->cancelOrderAction->execute($order, $request->input('reason', ''));
            } else {
                $order = $this->orderRepository->updateStatus($order, $newStatus);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status bijgewerkt',
                'data' => new OrderResource($order),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
                'order_id' => $order->id,
                'new_status' => $newStatus ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon order status niet bijwerken',
            ], 500);
        }
    }
}
            ]);

            // Trigger provisioning when approved
            if ($oldStatus === 'pending' && $newStatus === 'processing') {
                // Dispatch jobs for provisioning
                dispatch(new ProvisionHostingJob($order));
                
                // Register domain if exists
                $domain = $order->domains->first();
                if ($domain) {
                    dispatch(new RegisterDomainJob($domain));
                }
                
                // Create invoice in Moneybird
                dispatch(new CreateInvoiceJob($order));
                
                Log::info('Provisioning jobs dispatched', [
                    'order_id' => $order->id,
                ]);
            }

            // Suspend services when order is suspended
            if ($newStatus === 'suspended') {
                // TODO: Dispatch SuspendHostingJob
                Log::info('Order suspended', ['order_id' => $order->id]);
            }

            // Reactivate services
            if ($oldStatus === 'suspended' && $newStatus === 'active') {
                // TODO: Dispatch ReactivateHostingJob
                Log::info('Order reactivated', ['order_id' => $order->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status bijgewerkt',
                'data' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Order status update failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Er ging iets mis',
            ], 500);
        }
    }
}
