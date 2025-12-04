<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\OrderData;
use App\Events\Order\OrderCreated;
use App\Models\Order;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly DomainRepositoryInterface $domainRepository,
    ) {}

    public function execute(CustomerData $customerData, OrderData $orderData): Order
    {
        return DB::transaction(function () use ($customerData, $orderData) {
            // Create or find customer
            $customer = $this->customerRepository->findByEmail($customerData->email)
                ?? $this->customerRepository->create($customerData->toArray());

            // Calculate pricing
            $subtotal = $this->calculateSubtotal($orderData);
            $tax = $this->calculateTax($subtotal);
            $total = $subtotal + $tax;

            // Create order
            $order = $this->orderRepository->create([
                'customer_id' => $customer->id,
                'hosting_package_id' => $orderData->hosting_package_id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'billing_cycle' => $orderData->billing_cycle,
                'price' => $subtotal,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            // Create domains
            foreach ($orderData->domains as $domainData) {
                $this->domainRepository->create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'domain_name' => $domainData->domain_name,
                    'tld' => $domainData->tld,
                    'status' => 'pending',
                ]);
            }

            // Dispatch event
            event(new OrderCreated($order));

            return $order->fresh(['customer', 'hostingPackage', 'domains']);
        });
    }

    private function generateOrderNumber(): string
    {
        return 'HF-'.date('Ymd').'-'.strtoupper(Str::random(6));
    }

    private function calculateSubtotal(OrderData $orderData): float
    {
        $subtotal = 0;

        // Add hosting package price
        if ($orderData->hosting_package_id) {
            $package = \App\Models\HostingPackage::find($orderData->hosting_package_id);
            if ($package) {
                $subtotal += $orderData->billing_cycle === 'yearly'
                    ? $package->price_yearly
                    : $package->price;
            }
        }

        // Add domain prices (simplified - €9.99 per domain)
        $subtotal += $orderData->domains->count() * 9.99;

        return $subtotal;
    }

    private function calculateTax(float $subtotal): float
    {
        // Dutch VAT: 21%
        return round($subtotal * 0.21, 2);
    }
}
