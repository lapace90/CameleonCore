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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->dateTime('date');
            $table->dateTime('checkin');
            $table->dateTime('checkout');
            $table->decimal('amount', 8, 2);
             // ← nullable car généré APRÈS insert via l'ID
            $table->string('invoice_number')->nullable()->unique();
            $table->string('booking_source');
            $table->enum('payment_status', ['paid', 'pending', 'canceled'])->default('pending');
            $table->integer('number_of_children');
            $table->integer('number_of_adults');
            $table->text('comment')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('user_id')->nullable();
    
            // Colonnes pour la relation polymorphique
            $table->unsignedBigInteger('product_id');
            $table->string('product_type');
    
            $table->timestamps();
    
            // Définir les clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
