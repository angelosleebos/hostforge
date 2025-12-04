<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class DomainData extends Data
{
    public function __construct(
        public readonly string $domain_name,
        public readonly string $tld,
        public readonly int $order_id,
        public readonly int $customer_id,
        public readonly string $status = 'pending',
        public readonly bool $register_domain = true,
    ) {}
}
