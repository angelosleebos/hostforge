<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Store a new order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create or find customer
            $customerData = $request->validated('customer');
            
            // Split name into first_name and last_name
            $nameParts = explode(' ', $customerData['name'], 2);
            $customerData['first_name'] = $nameParts[0];
            $customerData['last_name'] = $nameParts[1] ?? '';
            unset($customerData['name']);
            
            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );

            // Calculate pricing
            $package = \App\Models\HostingPackage::findOrFail($request->validated('hosting_package_id'));
            $billingCycle = $request->validated('billing_cycle');
            
            $price = $package->price;
            $tax = $price * 0.21; // 21% BTW
            $total = $price + $tax;

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'hosting_package_id' => $package->id,
                'status' => 'pending',
                'subtotal' => $price,
                'tax' => $tax,
                'total' => $total,
            ]);

            // Create domain if needed
            $domainData = $request->validated('domain');
            if ($domainData['register_domain']) {
                $domainParts = explode('.', $domainData['name'], 2);
                $domainName = $domainParts[0] ?? '';
                $tld = isset($domainParts[1]) ? '.' . $domainParts[1] : '';
                
                Domain::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'domain_name' => $domainData['name'],
                    'tld' => $tld,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // TODO: Dispatch job to send confirmation email
            // TODO: Dispatch job to notify admin

            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol geplaatst',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Er ging iets mis bij het plaatsen van de bestelling',
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['customer', 'hostingPackage', 'domains'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Bestelling niet gevonden',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }
}
