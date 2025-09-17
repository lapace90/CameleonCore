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

            $table->json('selected_product_ids'); // [1,2,5,8]
            $table->foreignId('customer_id')->constrained(); // ✅ Validation gérée par Customer

            $table->date('checkin_date')->nullable();
            $table->date('checkout_date')->nullable();
            $table->integer('guests')->default(2);

            $table->string('quote_reference')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->text('message')->nullable();

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
