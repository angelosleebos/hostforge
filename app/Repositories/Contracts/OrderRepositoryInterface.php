<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    /**
     * Find order by ID with relationships.
     */
    public function find(int $id): ?Order;

    /**
     * Find order by order number.
     */
    public function findByOrderNumber(string $orderNumber): ?Order;

    /**
     * Get paginated orders with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all orders for a specific customer.
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Create a new order.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Order;

    /**
     * Update an existing order.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order;

    /**
     * Delete an order.
     */
    public function delete(Order $order): bool;

    /**
     * Get orders that need invoicing.
     */
    public function getDueForInvoicing(): Collection;

    /**
     * Update order status.
     */
    public function updateStatus(Order $order, string $status): Order;
}
