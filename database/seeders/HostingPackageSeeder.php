<?php

namespace Database\Seeders;

use App\Models\HostingPackage;
use Illuminate\Database\Seeder;

class HostingPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Startup',
                'description' => 'Perfect voor kleine websites en startende ondernemers',
                'disk_space_mb' => 5120, // 5 GB
                'bandwidth_gb' => 50,
                'email_accounts' => 5,
                'databases' => 1,
                'domains' => 1,
                'subdomains' => 5,
                'price' => 19.99, // Maandcontract
                'price_yearly' => 14.99, // Jaarcontract per maand
                'billing_period' => 'monthly',
                'active' => true,
            ],
            [
                'name' => 'Plus',
                'description' => 'Ideaal voor groeiende bedrijven met meer functionaliteit',
                'disk_space_mb' => 20480, // 20 GB
                'bandwidth_gb' => 200,
                'email_accounts' => 25,
                'databases' => 5,
                'domains' => 5,
                'subdomains' => 25,
                'price' => 39.99, // Maandcontract
                'price_yearly' => 34.99, // Jaarcontract per maand
                'billing_period' => 'monthly',
                'active' => true,
            ],
            [
                'name' => 'Premium',
                'description' => 'Voor professionele websites met maximale performance',
                'disk_space_mb' => 51200, // 50 GB
                'bandwidth_gb' => 500,
                'email_accounts' => 100,
                'databases' => 20,
                'domains' => 20,
                'subdomains' => 100,
                'price' => 79.99, // Maandcontract
                'price_yearly' => 74.99, // Jaarcontract per maand
                'billing_period' => 'monthly',
                'active' => true,
            ],
        ];

        foreach ($packages as $package) {
            HostingPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
