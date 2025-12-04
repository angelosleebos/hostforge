<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class CustomerData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?string $company,
        public readonly string $phone,
        public readonly string $address,
        public readonly string $postal_code,
        public readonly string $city,
        public readonly string $country,
        public readonly ?string $vat_number = null,
        public readonly string $status = 'pending',
    ) {}
}
