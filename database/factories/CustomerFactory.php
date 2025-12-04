<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
final class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->optional()->company(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->streetAddress(),
            'postal_code' => fake()->optional()->postcode(),
            'city' => fake()->optional()->city(),
            'country' => fake()->randomElement(['NL', 'BE', 'DE', 'FR', 'UK']),
            'vat_number' => null,
            'status' => 'pending',
            'plesk_user_id' => null,
            'moneybird_contact_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
