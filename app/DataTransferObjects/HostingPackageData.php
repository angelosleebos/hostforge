<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class HostingPackageData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly float $price,
        public readonly float $price_yearly,
        public readonly string $billing_period,
        public readonly int $disk_space_mb,
        public readonly int $bandwidth_gb,
        public readonly int $email_accounts,
        public readonly int $databases,
        public readonly int $domains,
        public readonly int $subdomains,
        public readonly bool $active = true,
    ) {}
}
