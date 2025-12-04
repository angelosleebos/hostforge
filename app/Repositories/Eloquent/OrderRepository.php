<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class OrderRepository implements OrderRepositoryInterface
{
    public function find(int $id): ?Order
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'hostingPackage', 'domains']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($filters) {
                        $customerQuery->where('email', 'like', "%{$filters['search']}%")
                            ->orWhere('first_name', 'like', "%{$filters['search']}%")
                            ->orWhere('last_name', 'like', "%{$filters['search']}%");
                    });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getByCustomer(int $customerId): Collection
    {
        return Order::with(['hostingPackage', 'domains'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])
            ->where('status', $status)
            ->get();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh(['customer', 'hostingPackage', 'domains']);
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function getDueForInvoicing(int $daysAhead = 7): Collection
    {
        return Order::with(['customer', 'hostingPackage', 'domains'])
            ->where('status', 'active')
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', now()->addDays($daysAhead))
            ->whereNull('moneybird_invoice_id')
            ->get();
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        return $order->fresh(['customer', 'hostingPackage', 'domains']);
    }
}
