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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->string('domain_name')->unique();
            $table->string('tld'); // .com, .nl, etc.

            // Status: pending, registered, active, suspended, expired
            $table->string('status')->default('pending');

            // Registration details
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // External IDs
            $table->string('openprovider_domain_id')->nullable();
            $table->string('plesk_domain_id')->nullable();

            $table->timestamps();

            $table->index('domain_name');
            $table->index('status');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
