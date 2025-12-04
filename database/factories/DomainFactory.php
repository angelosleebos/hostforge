<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
final class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'domain_name' => fake()->domainName(),
            'status' => 'pending',
            'is_transfer' => false,
            'registration_date' => null,
            'expiry_date' => null,
            'openprovier_domain_id' => null,
            'nameservers' => null,
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'registered',
            'registration_date' => now(),
            'expiry_date' => now()->addYear(),
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_transfer' => true,
        ]);
    }
}
