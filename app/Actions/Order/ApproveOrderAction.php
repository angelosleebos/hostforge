<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Events\Order\OrderApproved;
use App\Jobs\CreateInvoiceJob;
use App\Jobs\ProvisionHostingJob;
use App\Jobs\RegisterDomainJob;
use App\Jobs\SyncCustomerToMoneybirdJob;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ApproveOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Update order status
            $order = $this->orderRepository->update($order, [
                'status' => 'processing',
                'approved_at' => now(),
            ]);

            // Dispatch background jobs
            if ($order->hosting_package_id) {
                ProvisionHostingJob::dispatch($order);
            }

            // Register domains
            foreach ($order->domains as $domain) {
                RegisterDomainJob::dispatch($domain);
            }

            // Sync customer to Moneybird
            SyncCustomerToMoneybirdJob::dispatch($order->customer);

            // Create invoice
            CreateInvoiceJob::dispatch($order);

            // Dispatch event
            event(new OrderApproved($order));

            return $order;
        });
    }
}
