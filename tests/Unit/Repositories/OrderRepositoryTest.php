<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Customer;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Repositories\Eloquent\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository();
    }

    public function test_find_returns_order_with_relationships(): void
    {
        $order = Order::factory()->create();

        $result = $this->repository->find($order->id);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->relationLoaded('hostingPackage'));
        $this->assertTrue($result->relationLoaded('domains'));
    }

    public function test_find_by_order_number_returns_correct_order(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-12345',
        ]);

        $result = $this->repository->findByOrderNumber('ORD-12345');

        $this->assertEquals($order->id, $result->id);
        $this->assertEquals('ORD-12345', $result->order_number);
    }

    public function test_paginate_filters_by_status(): void
    {
        Order::factory()->count(5)->create(['status' => 'pending']);
        Order::factory()->count(3)->create(['status' => 'processing']);

        $result = $this->repository->paginate(['status' => 'pending'], 10);

        $this->assertCount(5, $result);
    }

    public function test_paginate_filters_by_customer(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(3)->create(['customer_id' => $customer->id]);
        Order::factory()->count(2)->create();

        $result = $this->repository->paginate(['customer_id' => $customer->id], 10);

        $this->assertCount(3, $result);
    }

    public function test_paginate_searches_by_order_number(): void
    {
        Order::factory()->create(['order_number' => 'ORD-SEARCH-123']);
        Order::factory()->create(['order_number' => 'ORD-OTHER-456']);

        $result = $this->repository->paginate(['search' => 'SEARCH'], 10);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('SEARCH', $result->first()->order_number);
    }

    public function test_get_by_customer_returns_customer_orders(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(4)->create(['customer_id' => $customer->id]);

        $result = $this->repository->getByCustomer($customer->id);

        $this->assertCount(4, $result);
        $this->assertTrue($result->every(fn($order) => $order->customer_id === $customer->id));
    }

    public function test_get_by_status_returns_filtered_orders(): void
    {
        Order::factory()->count(6)->create(['status' => 'active']);
        Order::factory()->count(2)->create(['status' => 'pending']);

        $result = $this->repository->getByStatus('active');

        $this->assertCount(6, $result);
    }

    public function test_create_persists_order(): void
    {
        $customer = Customer::factory()->create();
        $package = HostingPackage::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'hosting_package_id' => $package->id,
            'order_number' => 'ORD-TEST-001',
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'price' => 9.99,
        ];

        $order = $this->repository->create($data);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-TEST-001',
            'price' => 9.99,
        ]);
        $this->assertInstanceOf(Order::class, $order);
    }

    public function test_update_modifies_order(): void
    {
        $order = Order::factory()->create(['price' => 10.00]);

        $updated = $this->repository->update($order, ['price' => 15.00]);

        $this->assertEquals(15.00, $updated->price);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'price' => 15.00,
        ]);
    }

    public function test_update_status_changes_order_status(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $updated = $this->repository->updateStatus($order, 'processing');

        $this->assertEquals('processing', $updated->status);
    }

    public function test_get_due_for_invoicing_returns_correct_orders(): void
    {
        Order::factory()->create([
            'status' => 'active',
            'next_billing_date' => now()->addDays(5),
        ]);
        Order::factory()->create([
            'status' => 'active',
            'next_billing_date' => now()->addDays(10),
        ]);

        $result = $this->repository->getDueForInvoicing(7);

        $this->assertCount(1, $result);
    }

    public function test_delete_removes_order(): void
    {
        $order = Order::factory()->create();

        $this->repository->delete($order);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
