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
        Schema::create('res_product_option', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id')->nullable();
        $table->unsignedBigInteger('option_id');
        $table->unsignedBigInteger('reservation_id')->nullable();
        $table->timestamps();

        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        $table->foreign('option_id')->references('id')->on('options')->onDelete('cascade');
        $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('res_product_option');
    }
};
