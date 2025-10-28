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
        // Table pivot pour les quantités réelles 
        Schema::create('quote_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // SEULE donnée métier nécessaire : quantité calculée selon useQuotePricing.js
            $table->integer('quantity')->default(1);
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['quote_request_id', 'product_id']);
        });
        
        // Colonne pour indiquer si un devis a été migré vers le système pivot
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->boolean('pivot_migrated')->default(false)->after('selected_product_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_request_items');
        
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn('pivot_migrated');
        });
    }
};