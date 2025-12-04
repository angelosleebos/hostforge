<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Customer;
use App\Repositories\Eloquent\CustomerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CustomerRepository();
    }

    public function test_find_returns_customer_with_relationships(): void
    {
        $customer = Customer::factory()->create();

        $result = $this->repository->find($customer->id);

        $this->assertInstanceOf(Customer::class, $result);
        $this->assertTrue($result->relationLoaded('orders'));
    }

    public function test_find_by_email_returns_correct_customer(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->repository->findByEmail('test@example.com');

        $this->assertEquals($customer->id, $result->id);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function test_find_by_email_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    public function test_paginate_filters_by_status(): void
    {
        Customer::factory()->count(4)->create(['status' => 'approved']);
        Customer::factory()->count(2)->create(['status' => 'pending']);

        $result = $this->repository->paginate(['status' => 'approved'], 10);

        $this->assertCount(4, $result);
    }

    public function test_paginate_searches_by_name(): void
    {
        Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $result = $this->repository->paginate(['search' => 'John'], 10);

        $this->assertCount(1, $result);
        $this->assertEquals('John', $result->first()->first_name);
    }

    public function test_paginate_searches_by_email(): void
    {
        Customer::factory()->create(['email' => 'john@example.com']);
        Customer::factory()->create(['email' => 'jane@example.com']);

        $result = $this->repository->paginate(['search' => 'john@'], 10);

        $this->assertCount(1, $result);
    }

    public function test_get_by_status_returns_filtered_customers(): void
    {
        Customer::factory()->count(5)->create(['status' => 'approved']);
        Customer::factory()->count(3)->create(['status' => 'suspended']);

        $result = $this->repository->getByStatus('approved');

        $this->assertCount(5, $result);
    }

    public function test_create_persists_customer(): void
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'test@example.com',
            'status' => 'pending',
            'phone' => '+31612345678',
            'address' => 'Test Street 1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'country' => 'NL',
        ];

        $customer = $this->repository->create($data);

        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Customer',
        ]);
        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function test_update_modifies_customer(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Old']);

        $updated = $this->repository->update($customer, ['first_name' => 'New']);

        $this->assertEquals('New', $updated->first_name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'New',
        ]);
    }

    public function test_update_status_changes_customer_status(): void
    {
        $customer = Customer::factory()->create(['status' => 'pending']);

        $updated = $this->repository->updateStatus($customer, 'approved');

        $this->assertEquals('approved', $updated->status);
    }

    public function test_find_by_plesk_user_id_returns_correct_customer(): void
    {
        $customer = Customer::factory()->create(['plesk_user_id' => 'plesk-123']);

        $result = $this->repository->findByPleskUserId('plesk-123');

        $this->assertEquals($customer->id, $result->id);
    }

    public function test_find_by_moneybird_contact_id_returns_correct_customer(): void
    {
        $customer = Customer::factory()->create(['moneybird_contact_id' => 'mb-456']);

        $result = $this->repository->findByMoneybirdContactId('mb-456');

        $this->assertEquals($customer->id, $result->id);
    }

    public function test_delete_removes_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->repository->delete($customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
