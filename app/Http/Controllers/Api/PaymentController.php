<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create Mollie payment for order.
     */
    public function createPayment(Request $request, Order $order): JsonResponse
    {
        try {
            $customer = $order->customer;

            // Ensure customer has Mollie customer ID
            if (! $customer->mollie_customer_id) {
                $mollieCustomer = $customer->createAsMollieCustomer([
                    'name' => $customer->full_name,
                    'email' => $customer->email,
                ]);
            }

            // Create payment
            $payment = $customer->charge($order->total, $order->order_number, [
                'description' => "Bestelling #{$order->order_number}",
                'redirectUrl' => config('app.url')."/order/success?order={$order->order_number}",
                'webhookUrl' => route('api.webhooks.mollie'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            // Create subscription for recurring billing
            if ($order->billing_cycle !== 'one-time') {
                $interval = match ($order->billing_cycle) {
                    'monthly' => '1 month',
                    'quarterly' => '3 months',
                    'yearly' => '1 year',
                    default => '1 month',
                };

                $customer->newSubscription('hosting', 'hosting-'.$order->hosting_package_id)
                    ->create($payment->mandateId, [
                        'description' => "Hosting: {$order->hostingPackage->name}",
                        'amount' => [
                            'currency' => 'EUR',
                            'value' => number_format($order->price, 2, '.', ''),
                        ],
                        'interval' => $interval,
                        'webhookUrl' => route('api.webhooks.mollie'),
                    ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_url' => $payment->getCheckoutUrl(),
                    'payment_id' => $payment->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Betaling kon niet worden aangemaakt',
            ], 500);
        }
    }

    /**
     * Handle Mollie webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('id');

            if (! $paymentId) {
                return response()->json(['success' => false], 400);
            }

            // Process the payment
            $payment = \Mollie\Laravel\Facades\Mollie::api()->payments->get($paymentId);

            if ($payment->isPaid() && ! $payment->hasRefunds() && ! $payment->hasChargebacks()) {
                $orderId = $payment->metadata->order_id ?? null;

                if ($orderId) {
                    $order = Order::find($orderId);

                    if ($order && $order->status === 'pending') {
                        $order->update([
                            'status' => 'paid',
                            'activated_at' => now(),
                        ]);

                        // Dispatch provisioning job
                        \App\Jobs\ProvisionHostingJob::dispatch($order);
                    }
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
