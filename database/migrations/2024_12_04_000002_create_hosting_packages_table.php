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
        Schema::create('hosting_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('billing_period')->default('monthly'); // monthly, yearly

            // Package limits
            $table->integer('disk_space_mb')->default(1000); // MB
            $table->integer('bandwidth_gb')->default(10); // GB per month
            $table->integer('email_accounts')->default(5);
            $table->integer('databases')->default(1);
            $table->integer('domains')->default(1);
            $table->integer('subdomains')->default(5);

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_packages');
    }
};
