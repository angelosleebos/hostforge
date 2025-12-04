<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\HostingPackageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
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
Route::prefix('v1')->group(function () {
    // Hosting packages
    Route::get('/packages', [HostingPackageController::class, 'index']);
    Route::get('/packages/{package}', [HostingPackageController::class, 'show']);

    // Domain management
    Route::post('/domains/check', [DomainController::class, 'check']);
    Route::get('/domains/pricing', [DomainController::class, 'pricing']);

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
});

// Admin API routes - Protected with Sanctum authentication
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Customer management
    Route::get('/customers', [AdminCustomerController::class, 'index']);
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show']);
    Route::patch('/customers/{customer}/status', [AdminCustomerController::class, 'updateStatus']);

    // Order management
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

    // Billing
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
