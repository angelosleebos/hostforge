<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Domain;
use Illuminate\Support\Collection;

interface DomainRepositoryInterface
{
    /**
     * Find domain by ID.
     */
    public function find(int $id): ?Domain;

    /**
     * Find domain by domain name.
     */
    public function findByDomainName(string $domainName): ?Domain;

    /**
     * Get all domains for a specific order.
     */
    public function getByOrder(int $orderId): Collection;

    /**
     * Get all domains for a specific customer.
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get domains by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Create a new domain.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Domain;

    /**
     * Update an existing domain.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Domain $domain, array $data): Domain;

    /**
     * Delete a domain.
     */
    public function delete(Domain $domain): bool;

    /**
     * Get domains expiring soon.
     */
    public function getExpiringSoon(int $days = 30): Collection;

    /**
     * Update domain status.
     */
    public function updateStatus(Domain $domain, string $status): Domain;
}
