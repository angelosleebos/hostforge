<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Order\CreateOrderAction;
use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\DomainData;
use App\DataTransferObjects\OrderData;
use App\Events\Order\OrderCreated;
use App\Models\Customer;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelData\DataCollection;
use Tests\TestCase;

final class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateOrderAction $action;
    private CustomerRepositoryInterface $customerRepository;
    private OrderRepositoryInterface $orderRepository;
    private DomainRepositoryInterface $domainRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = $this->app->make(CustomerRepositoryInterface::class);
        $this->orderRepository = $this->app->make(OrderRepositoryInterface::class);
        $this->domainRepository = $this->app->make(DomainRepositoryInterface::class);

        $this->action = new CreateOrderAction(
            $this->orderRepository,
            $this->customerRepository,
            $this->domainRepository
        );
    }

    public function test_creates_new_customer_and_order(): void
    {
        Event::fake([OrderCreated::class]);

        $hostingPackage = HostingPackage::factory()->create([
            'price' => 9.99,
            'price_yearly' => 99.99,
        ]);

        $customerData = new CustomerData(
            email: 'john@example.com',
            first_name: 'John',
            last_name: 'Doe',
            company: 'ACME Corp',
            phone: '+31612345678',
            address: 'Main Street 1',
            postal_code: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        );

        $domainData = new \Spatie\LaravelData\DataCollection(DomainData::class, [
            DomainData::from([
                'domain_name' => 'example',
                'tld' => 'com',
                'order_id' => 0,
                'customer_id' => 0,
                'status' => 'pending',
                'register_domain' => true,
            ]),
        ]);

        $orderData = new OrderData(
            hosting_package_id: $hostingPackage->id,
            billing_cycle: 'monthly',
            domains: $domainData,
        );

        $order = $this->action->execute($customerData, $orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_number' => $order->order_number,
            'price' => 19.98, // 9.99 (hosting) + 9.99 (domain)
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('domains', [
            'domain_name' => 'example',
            'order_id' => $order->id,
        ]);

        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_uses_existing_customer_by_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $hostingPackage = HostingPackage::factory()->create();

        $customerData = new CustomerData(
            email: 'existing@example.com',
            first_name: 'Updated',
            last_name: 'Name',
            company: null,
            phone: '+31612345678',
            address: 'Test Street 1',
            postal_code: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        );

        $orderData = new OrderData(
            customer_id: 0,
            hosting_package_id: $hostingPackage->id,
            billing_cycle: 'monthly',
            domains: new \Spatie\LaravelData\DataCollection(DomainData::class, []),
        );

        $order = $this->action->execute($customerData, $orderData);

        $this->assertEquals($existingCustomer->id, $order->customer_id);
        $this->assertEquals(1, Customer::where('email', 'existing@example.com')->count());
    }

    public function test_calculates_yearly_price_correctly(): void
    {
        $hostingPackage = HostingPackage::factory()->create([
            'price' => 10.00,
            'price_yearly' => 100.00,
        ]);

        $customerData = new CustomerData(
            email: 'test@example.com',
            first_name: 'Test',
            last_name: 'User',
            company: null,
            phone: '+31612345678',
            address: 'Test Street 1',
            postal_code: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        );

        $orderData = new OrderData(
            customer_id: 0,
            hosting_package_id: $hostingPackage->id,
            billing_cycle: 'yearly',
            domains: new \Spatie\LaravelData\DataCollection(DomainData::class, []),
        );

        $order = $this->action->execute($customerData, $orderData);

        $this->assertEquals(100.00, $order->price);
        $this->assertEquals('yearly', $order->billing_cycle);
    }

    public function test_rolls_back_on_failure(): void
    {
        $this->expectException(\Exception::class);

        $customerData = new CustomerData(
            email: 'test@example.com',
            first_name: 'Test',
            last_name: 'User',
            company: null,
            phone: '+31612345678',
            address: 'Test Street 1',
            postal_code: '1234AB',
            city: 'Amsterdam',
            country: 'NL',
        );

        $orderData = new OrderData(
            customer_id: 0,
            hosting_package_id: 99999, // Non-existent package
            billing_cycle: 'monthly',
            domains: new \Spatie\LaravelData\DataCollection(DomainData::class, []),
        );

        $this->action->execute($customerData, $orderData);
    }
}
