<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get customer dashboard data.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        
        $orders = $customer->orders()
            ->with(['hostingPackage', 'domains'])
            ->latest()
            ->get();

        $activeOrders = $orders->where('status', 'active')->count();
        $totalSpent = $orders->where('status', 'active')->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'active_orders' => $activeOrders,
                    'total_spent' => number_format($totalSpent, 2),
                ],
                'recent_orders' => $orders->take(5),
            ],
        ]);
    }

    /**
     * Get customer orders.
     */
    public function orders(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        
        $orders = $customer->orders()
            ->with(['hostingPackage', 'domains'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get customer subscriptions from Mollie.
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        
        $subscriptions = $customer->subscriptions()
            ->with('items')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get Plesk login URL for customer.
     */
    public function pleskLogin(Request $request): JsonResponse
    {
        $customer = $request->user('customer');

        if (!$customer->plesk_user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Geen Plesk account gekoppeld',
            ], 404);
        }

        // In production: Generate Plesk SSO token
        $pleskUrl = config('services.plesk.host');
        $loginUrl = "{$pleskUrl}/login_up.php?success_redirect_url=/admin/home";

        return response()->json([
            'success' => true,
            'data' => [
                'login_url' => $loginUrl,
            ],
        ]);
    }
}
