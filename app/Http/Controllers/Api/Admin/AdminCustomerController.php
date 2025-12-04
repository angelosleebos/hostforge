<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdminCustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Get all customers with optional filters
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->input('per_page', 15);

        $customers = $this->customerRepository->paginate($filters, (int) $perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Get pending customers awaiting approval
     */
    public function pending(): AnonymousResourceCollection
    {
        $customers = $this->customerRepository->paginate(['status' => 'pending'], 50);

        return CustomerResource::collection($customers);
    }

    /**
     * Update customer status (approve/reject)
     */
    public function updateStatus(Request $request, int $customerId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,suspended',
            'reason' => 'nullable|string|max:500',
        ]);

        $customer = $this->customerRepository->findOrFail($customerId);

        $customer->update([
            'status' => $request->input('status'),
            'status_reason' => $request->input('reason'),
        ]);

        // If approved, send welcome email
        if ($request->input('status') === 'approved') {
            // TODO: Send welcome email with login credentials
        }

        // If rejected, send rejection email
        if ($request->input('status') === 'rejected') {
            // TODO: Send rejection email with reason
        }

        return response()->json([
            'success' => true,
            'message' => 'Status succesvol bijgewerkt',
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }

    /**
     * Get customer details
     */
    public function show(int $customerId): CustomerResource
    {
        $customer = $this->customerRepository->findOrFail($customerId);
        $customer->load(['orders', 'domains']);

        return new CustomerResource($customer);
    }
}
