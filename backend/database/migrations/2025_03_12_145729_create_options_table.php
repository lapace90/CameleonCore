<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->default(0.00);
            // Colonnes pour la relation polymorphique
            $table->unsignedBigInteger('productable_id');
            $table->string('productable_type');
            $table->timestamps();
    
            // Index pour optimiser les requêtes polymorphiques
            $table->index(['productable_id', 'productable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};