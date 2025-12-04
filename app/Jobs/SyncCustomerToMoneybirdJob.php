<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\MoneybirdService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCustomerToMoneybirdJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Customer $customer
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MoneybirdService $moneybirdService): void
    {
        try {
            Log::info('Syncing customer to Moneybird', [
                'customer_id' => $this->customer->id,
                'email' => $this->customer->email,
            ]);

            // Check if already synced
            if ($this->customer->moneybird_contact_id) {
                Log::info('Customer already synced to Moneybird', [
                    'customer_id' => $this->customer->id,
                    'moneybird_contact_id' => $this->customer->moneybird_contact_id,
                ]);
                return;
            }

            // Create contact in Moneybird
            $contact = $moneybirdService->createContact([
                'company_name' => $this->customer->company ?? "{$this->customer->first_name} {$this->customer->last_name}",
                'firstname' => $this->customer->first_name,
                'lastname' => $this->customer->last_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'address1' => $this->customer->address,
                'zipcode' => $this->customer->postal_code,
                'city' => $this->customer->city,
                'country' => $this->customer->country,
            ]);

            // Update customer with Moneybird contact ID
            $this->customer->update([
                'moneybird_contact_id' => $contact['id'],
            ]);

            Log::info('Customer synced to Moneybird successfully', [
                'customer_id' => $this->customer->id,
                'moneybird_contact_id' => $contact['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Customer sync to Moneybird failed', [
                'customer_id' => $this->customer->id,
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
        Log::error('Customer sync job failed permanently', [
            'customer_id' => $this->customer->id,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send notification to admin
    }
}
