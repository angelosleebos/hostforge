<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CustomerRepository implements CustomerRepositoryInterface
{
    public function find(int $id): ?Customer
    {
        return Customer::with(['orders', 'domains'])->find($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::with(['orders']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', "%{$filters['search']}%")
                    ->orWhere('first_name', 'like', "%{$filters['search']}%")
                    ->orWhere('last_name', 'like', "%{$filters['search']}%")
                    ->orWhere('company', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getByStatus(string $status): Collection
    {
        return Customer::where('status', $status)->get();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->fresh(['orders', 'domains']);
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function updateStatus(Customer $customer, string $status): Customer
    {
        $customer->update(['status' => $status]);

        return $customer->fresh(['orders', 'domains']);
    }

    public function findByPleskUserId(string $pleskUserId): ?Customer
    {
        return Customer::where('plesk_user_id', $pleskUserId)->first();
    }

    public function findByMoneybirdContactId(string $moneybirdContactId): ?Customer
    {
        return Customer::where('moneybird_contact_id', $moneybirdContactId)->first();
    }
}
