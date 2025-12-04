<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('hosting_package_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('order_number')->unique();
            
            // Status: pending, approved, provisioning, active, suspended, cancelled
            $table->string('status')->default('pending');
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            
            // External invoice ID
            $table->string('moneybird_invoice_id')->nullable();
            
            $table->timestamps();
            
            $table->index('order_number');
            $table->index('status');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
