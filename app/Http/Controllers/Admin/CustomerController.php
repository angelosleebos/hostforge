<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('orders');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['orders.hostingPackage', 'orders.domain']);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * Update customer status.
     */
    public function updateStatus(ApproveCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update([
            'status' => $request->validated('status'),
        ]);

        // TODO: Dispatch job to send notification email
        // TODO: If suspended, dispatch job to suspend hosting

        return response()->json([
            'success' => true,
            'message' => 'Klant status bijgewerkt',
            'data' => $customer,
        ]);
    }
}
