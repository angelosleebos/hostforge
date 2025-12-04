<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\OpenProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RegisterDomainJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Domain $domain
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenProviderService $openProviderService): void
    {
        try {
            Log::info('Starting domain registration', [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->domain_name,
            ]);

            $customer = $this->domain->customer;

            // Check domain availability first
            $isAvailable = $openProviderService->checkDomainAvailability($this->domain->domain_name);

            if (! $isAvailable) {
                Log::warning('Domain not available for registration', [
                    'domain_name' => $this->domain->domain_name,
                ]);

                $this->domain->update([
                    'status' => 'unavailable',
                ]);

                return;
            }

            // Register domain
            $registration = $openProviderService->registerDomain([
                'domain_name' => $this->domain->domain_name,
                'period' => 1, // 1 year
                'owner_handle' => $customer->email,
                'admin_handle' => $customer->email,
                'tech_handle' => $customer->email,
                'billing_handle' => $customer->email,
                'ns_group' => config('services.openprovider.default_ns_group'),
                'nameservers' => [
                    ['name' => 'ns1.domeindiscounter.nl'],
                    ['name' => 'ns2.domeindiscounter.nl'],
                    ['name' => 'ns3.domeindiscounter.nl'],
                ],
            ]);

            // Update domain with registration details
            $this->domain->update([
                'openprovider_domain_id' => $registration['id'],
                'status' => 'registered',
                'registered_at' => now(),
                'expires_at' => now()->addYear(),
            ]);

            Log::info('Domain registered successfully', [
                'domain_id' => $this->domain->id,
                'openprovider_domain_id' => $registration['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Domain registration failed', [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->domain_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->domain->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Domain registration job failed permanently', [
            'domain_id' => $this->domain->id,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send notification to admin
    }
}
