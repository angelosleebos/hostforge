<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminCustomerController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\HostingPackageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/revoke-all', [AuthController::class, 'revokeAll']);
    });
});

// Public API routes
Route::get('/hosting-packages', [HostingPackageController::class, 'index']);
Route::get('/hosting-packages/{package}', [HostingPackageController::class, 'show']);

// Domain management
Route::post('/domains/check', [DomainController::class, 'check']);
Route::get('/domains/pricing', [DomainController::class, 'pricing']);

// Orders
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);

// Payments
Route::post('/orders/{order}/payment', [PaymentController::class, 'createPayment'])->name('api.payment.create');
Route::post('/webhooks/mollie', [PaymentController::class, 'webhook'])->name('api.webhooks.mollie');

// Customer Portal API routes
Route::prefix('customer')->group(function () {
    // Authentication
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);

    // Protected customer routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/me', [CustomerAuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [CustomerDashboardController::class, 'index']);
        Route::get('/orders', [CustomerDashboardController::class, 'orders']);
        Route::get('/subscriptions', [CustomerDashboardController::class, 'subscriptions']);
        Route::get('/plesk-login', [CustomerDashboardController::class, 'pleskLogin']);
    });
});

// Admin API routes - Protected with Sanctum authentication
Route::prefix('admin')->group(function () {
    // Login endpoint (niet beveiligd)
    Route::post('/login', [AuthController::class, 'login']);

    // Beveiligde admin routes
    Route::middleware('auth:sanctum')->group(function () {
        // Dashboard stats
        Route::get('/dashboard/stats', [AdminOrderController::class, 'dashboardStats']);

        // Customer management
        Route::get('/customers', [AdminCustomerController::class, 'index']);
        Route::get('/customers/pending', [AdminCustomerController::class, 'pending']);
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show']);
        Route::patch('/customers/{customer}/status', [AdminCustomerController::class, 'updateStatus']);

        // Order management
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve']);
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

        // Billing
        Route::get('/billing/stats', [BillingController::class, 'stats']);
        Route::get('/billing/invoices', [BillingController::class, 'invoices']);
        Route::post('/billing/generate-invoices', [BillingController::class, 'generateInvoices']);
        Route::get('/billing', [BillingController::class, 'index']);
        Route::get('/billing/due-orders', [BillingController::class, 'dueOrders']);
        Route::post('/billing/orders/{order}/invoice', [BillingController::class, 'createInvoice']);
        Route::post('/billing/orders/{order}/sync-customer', [BillingController::class, 'syncCustomer']);

        // Test endpoints for job execution (TODO: Remove in production)
        Route::post('/test/provision/{order}', function (\App\Models\Order $order) {
            dispatch(new \App\Jobs\ProvisionHostingJob($order));

            return response()->json(['message' => 'Provisioning job dispatched']);
        });

        Route::post('/test/register-domain/{domain}', function (\App\Models\Domain $domain) {
            dispatch(new \App\Jobs\RegisterDomainJob($domain));

            return response()->json(['message' => 'Domain registration job dispatched']);
        });

        Route::post('/test/create-invoice/{order}', function (\App\Models\Order $order) {
            dispatch(new \App\Jobs\CreateInvoiceJob($order));

            return response()->json(['message' => 'Invoice creation job dispatched']);
        });
    });
});
