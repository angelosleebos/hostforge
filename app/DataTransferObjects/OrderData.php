<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class OrderData extends Data
{
    public function __construct(
        public readonly int $customer_id,
        public readonly ?int $hosting_package_id,
        public readonly string $billing_cycle,
        /** @var DataCollection<int, DomainData> */
        #[DataCollectionOf(DomainData::class)]
        public readonly DataCollection $domains,
        public readonly string $status = 'pending',
        public readonly ?float $subtotal = null,
        public readonly ?float $tax = null,
        public readonly ?float $total = null,
    ) {}
}
