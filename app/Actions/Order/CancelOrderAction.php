<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Events\Order\OrderCancelled;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CancelOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function execute(Order $order, string $reason = ''): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            // Update order status
            $order = $this->orderRepository->update($order, [
                'status' => 'cancelled',
            ]);

            // Update all domains to cancelled
            foreach ($order->domains as $domain) {
                $domain->update(['status' => 'cancelled']);
            }

            // Dispatch event
            event(new OrderCancelled($order, $reason));

            return $order;
        });
    }
}
