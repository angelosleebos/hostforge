<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HostingPackage>
 */
final class HostingPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Hosting',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 50),
            'price_yearly' => fake()->randomFloat(2, 50, 500),
            'billing_period' => 'monthly',
            'disk_space_mb' => fake()->randomElement([1000, 5000, 10000, 50000]),
            'bandwidth_gb' => fake()->randomElement([10, 50, 100, 500]),
            'email_accounts' => fake()->randomElement([5, 10, 25, 50, 100]),
            'databases' => fake()->randomElement([1, 5, 10, 25]),
            'domains' => fake()->randomElement([1, 5, 10, 25]),
            'subdomains' => fake()->randomElement([5, 10, 25, 50]),
            'active' => true,
        ];
    }
}
