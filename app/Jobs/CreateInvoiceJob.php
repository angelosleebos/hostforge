<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\MoneybirdService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateInvoiceJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MoneybirdService $moneybirdService): void
    {
        try {
            Log::info('Starting invoice creation', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $customer = $this->order->customer;

            // Ensure customer is synced to Moneybird first
            if (! $customer->moneybird_contact_id) {
                Log::info('Customer not synced to Moneybird, syncing now');
                dispatch(new SyncCustomerToMoneybirdJob($customer));

                // Wait for sync to complete (in a real scenario, we'd use job chaining)
                sleep(2);
                $customer->refresh();

                if (! $customer->moneybird_contact_id) {
                    throw new \Exception('Customer sync to Moneybird failed');
                }
            }

            $package = $this->order->hostingPackage;
            $domain = $this->order->domains->first();

            // Prepare invoice details
            $invoiceItems = [
                [
                    'description' => "{$package->name} Hosting Pakket",
                    'price' => $this->order->subtotal,
                    'amount' => 1,
                    'tax_rate_id' => config('services.moneybird.default_tax_rate_id'), // 21% BTW
                ],
            ];

            // Add domain registration if applicable
            if ($domain && $domain->status === 'registered') {
                $invoiceItems[] = [
                    'description' => "Domeinregistratie: {$domain->domain_name}",
                    'price' => 15.00, // Default domain price
                    'amount' => 1,
                    'tax_rate_id' => config('services.moneybird.default_tax_rate_id'),
                ];
            }

            // Create invoice in Moneybird
            $invoice = $moneybirdService->createInvoice([
                'contact_id' => $customer->moneybird_contact_id,
                'invoice_date' => now()->format('Y-m-d'),
                'reference' => $this->order->order_number,
                'details_attributes' => $invoiceItems,
            ]);

            // Update order with invoice ID
            $this->order->update([
                'moneybird_invoice_id' => $invoice['id'],
            ]);

            Log::info('Invoice created successfully', [
                'order_id' => $this->order->id,
                'invoice_id' => $invoice['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice creation failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice creation job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send notification to admin
    }
}
