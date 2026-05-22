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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained(); 

            $table->date('checkin_date')->nullable();
            $table->date('checkout_date')->nullable();
            $table->integer('guests')->default(2);
            $table->decimal('total_amount', 10, 2);

            $table->string('quote_reference')->unique();
            $table->text('message')->nullable();
            $table->string('validation_token', 128)->nullable();
            $table->timestampTz('token_expires_at')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['draft', 'sent', 'expired'])->default('draft');
            $table->string('source')->default('website');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
