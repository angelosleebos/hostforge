<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HostingPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
final class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 100);
        $tax = round($subtotal * 0.21, 2);
        $billingCycle = fake()->randomElement(['monthly', 'yearly']);

        return [
            'customer_id' => Customer::factory(),
            'hosting_package_id' => HostingPackage::factory(),
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'status' => 'pending',
            'billing_cycle' => $billingCycle,
            'price' => $subtotal,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'approved_at' => null,
            'provisioned_at' => null,
            'activated_at' => null,
            'next_billing_date' => $billingCycle === 'monthly' ? now()->addMonth() : now()->addYear(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'moneybird_invoice_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
