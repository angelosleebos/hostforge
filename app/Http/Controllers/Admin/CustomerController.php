<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Customer\ApproveCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ApproveCustomerAction $approveCustomerAction,
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $customers = $this->customerRepository->paginate(
            array_filter($filters),
            (int) $request->input('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers)->response()->getData(),
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer = $this->customerRepository->find($customer->id);

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update customer status.
     */
    public function updateStatus(ApproveCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $newStatus = $request->validated('status');

            if ($newStatus === 'approved' && $customer->status === 'pending') {
                $customer = $this->approveCustomerAction->execute($customer);
            } else {
                $customer = $this->customerRepository->updateStatus($customer, $newStatus);
            }

            return response()->json([
                'success' => true,
                'message' => 'Klant status bijgewerkt',
                'data' => new CustomerResource($customer),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update customer status', [
                'customer_id' => $customer->id,
                'new_status' => $newStatus ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Kon klant status niet bijwerken',
            ], 500);
        }
    }
}
