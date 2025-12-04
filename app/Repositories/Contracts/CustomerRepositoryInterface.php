<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Find customer by ID with relationships.
     */
    public function find(int $id): ?Customer;

    /**
     * Find customer by email.
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Get paginated customers with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get customers by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Create a new customer.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer;

    /**
     * Update an existing customer.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer;

    /**
     * Delete a customer (soft delete).
     */
    public function delete(Customer $customer): bool;

    /**
     * Update customer status.
     */
    public function updateStatus(Customer $customer, string $status): Customer;

    /**
     * Find customer by Plesk user ID.
     */
    public function findByPleskUserId(string $pleskUserId): ?Customer;

    /**
     * Find customer by Moneybird contact ID.
     */
    public function findByMoneybirdContactId(string $moneybirdContactId): ?Customer;
}
