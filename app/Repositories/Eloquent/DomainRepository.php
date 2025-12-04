<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Domain;
use App\Repositories\Contracts\DomainRepositoryInterface;
use Illuminate\Support\Collection;

final class DomainRepository implements DomainRepositoryInterface
{
    public function find(int $id): ?Domain
    {
        return Domain::with(['order', 'customer'])->find($id);
    }

    public function findByDomainName(string $domainName): ?Domain
    {
        return Domain::where('domain_name', $domainName)->first();
    }

    public function getByOrder(int $orderId): Collection
    {
        return Domain::where('order_id', $orderId)->get();
    }

    public function getByCustomer(int $customerId): Collection
    {
        return Domain::where('customer_id', $customerId)
            ->with(['order'])
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return Domain::where('status', $status)
            ->with(['order', 'customer'])
            ->get();
    }

    public function create(array $data): Domain
    {
        return Domain::create($data);
    }

    public function update(Domain $domain, array $data): Domain
    {
        $domain->update($data);

        return $domain->fresh(['order', 'customer']);
    }

    public function delete(Domain $domain): bool
    {
        return $domain->delete();
    }

    public function getExpiringSoon(int $days = 30): Collection
    {
        return Domain::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->with(['order', 'customer'])
            ->get();
    }

    public function updateStatus(Domain $domain, string $status): Domain
    {
        $domain->update(['status' => $status]);

        return $domain->fresh(['order', 'customer']);
    }
}
