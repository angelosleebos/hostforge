<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Order\ApproveOrderAction;
use App\Jobs\CreateInvoiceJob;
use App\Jobs\ProvisionHostingJob;
use App\Jobs\SyncCustomerToMoneybirdJob;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ApproveOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private ApproveOrderAction $action;

    private OrderRepositoryInterface $orderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->app->make(OrderRepositoryInterface::class);
        $this->action = new ApproveOrderAction($this->orderRepository);
    }

    public function test_approves_pending_order(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        $approvedOrder = $this->action->execute($order);

        $this->assertEquals('processing', $approvedOrder->status);
        $this->assertNotNull($approvedOrder->approved_at);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_dispatches_background_jobs(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status' => 'pending',
        ]);

        // Load domains relationship
        $order->load('domains');

        $this->action->execute($order);

        Queue::assertPushed(ProvisionHostingJob::class);
        Queue::assertPushed(CreateInvoiceJob::class);
        Queue::assertPushed(SyncCustomerToMoneybirdJob::class);
    }

    public function test_throws_exception_for_already_processed_order(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status' => 'processing',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->action->execute($order);
    }

    public function test_throws_exception_for_cancelled_order(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'status' => 'cancelled',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->action->execute($order);
    }
}
