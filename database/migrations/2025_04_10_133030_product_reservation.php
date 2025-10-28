<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reservation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('reservation_id');
            $table->integer('quantity')->default(1); 
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            
            // Index pour performance
            $table->index(['reservation_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reservation');
    }
};