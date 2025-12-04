<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Jobs\CreateInvoiceJob;
use App\Jobs\ProvisionHostingJob;
use App\Jobs\RegisterDomainJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'hostingPackage', 'domains']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'hostingPackage', 'domains']);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            DB::beginTransaction();

            $oldStatus = $order->status;
            $newStatus = $request->validated('status');

            $order->update([
                'status' => $newStatus,
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
