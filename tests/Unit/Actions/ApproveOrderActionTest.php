<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Order\ApproveOrderAction;
use App\Events\OrderApproved;
use App\Jobs\CreateInvoiceJob;
use App\Jobs\ProvisionHostingJob;
use App\Jobs\RegisterDomainJob;
use App\Jobs\SyncToMoneybirdJob;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->action->execute($order);

        Queue::assertPushed(ProvisionHostingJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });

        Queue::assertPushed(RegisterDomainJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });

        Queue::assertPushed(CreateInvoiceJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });

        Queue::assertPushed(SyncToMoneybirdJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    public function test_throws_exception_for_already_processed_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'processing',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is not in pending status');

        $this->action->execute($order);
    }

    public function test_throws_exception_for_cancelled_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'cancelled',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->action->execute($order);
    }
}
