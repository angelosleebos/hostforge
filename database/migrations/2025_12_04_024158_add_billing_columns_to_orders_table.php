<?php

declare(strict_types=1);

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
        Schema::table('orders', function (Blueprint $table) {
            // Billing cycle: monthly, yearly, quarterly, biannual
            $table->string('billing_cycle')->default('monthly')->after('status');
            
            // Pricing (subtotal, tax, total blijven behouden maar we voegen price toe voor backwards compatibility)
            $table->decimal('price', 10, 2)->after('billing_cycle');
            
            // Next billing date for recurring payments
            $table->timestamp('next_billing_date')->nullable()->after('activated_at');
            
            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_date');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            
            // Index for billing queries
            $table->index(['status', 'next_billing_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'next_billing_date']);
            $table->dropColumn([
                'billing_cycle',
                'price',
                'next_billing_date',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
