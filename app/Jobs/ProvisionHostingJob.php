<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PleskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProvisionHostingJob implements ShouldQueue
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
    public function handle(PleskService $pleskService): void
    {
        try {
            Log::info('Starting hosting provisioning', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $customer = $this->order->customer;
            $package = $this->order->hostingPackage;
            $domain = $this->order->domains->first();

            // Create Plesk customer if not exists
            if (!$customer->plesk_user_id) {
                $pleskUser = $pleskService->createCustomer([
                    'name' => "{$customer->first_name} {$customer->last_name}",
                    'email' => $customer->email,
                    'company' => $customer->company,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'city' => $customer->city,
                    'postal_code' => $customer->postal_code,
                    'country' => $customer->country,
                ]);

                $customer->update([
                    'plesk_user_id' => $pleskUser['id'],
                ]);

                Log::info('Plesk customer created', [
                    'plesk_user_id' => $pleskUser['id'],
                ]);
            }

            // Create hosting subscription in Plesk
            if ($domain) {
                $subscription = $pleskService->createSubscription([
                    'owner_id' => $customer->plesk_user_id,
                    'domain_name' => $domain->domain_name,
                    'plan_name' => $package->name,
                    'ip_address' => config('services.plesk.default_ip'),
                ]);

                $domain->update([
                    'plesk_domain_id' => $subscription['id'],
                    'status' => 'active',
                ]);

                Log::info('Plesk subscription created', [
                    'subscription_id' => $subscription['id'],
                    'domain' => $domain->domain_name,
                ]);
            }

            // Update order status
            $this->order->update([
                'status' => 'active',
                'provisioned_at' => now(),
                'activated_at' => now(),
            ]);

            Log::info('Hosting provisioning completed successfully', [
                'order_id' => $this->order->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Hosting provisioning failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update order to show provisioning failed
            $this->order->update([
                'status' => 'pending',
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Hosting provisioning job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send notification to admin
    }
}
