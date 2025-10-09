<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Informations client
            $table->string('client_name');
            $table->string('location')->nullable();
            $table->string('email');
            
            // Contenu de l'avis
            $table->text('testimonial_text');
            $table->integer('rating')->default(5); // 1-5 étoiles
            
            // Catégories et featured
            $table->string('category')->default('all'); // couples, families, solo, groups
            $table->boolean('featured')->default(false);
            $table->boolean('is_published')->default(false); // Validation admin
            
            // Photos (JSON pour stocker les URLs)
            $table->json('photos')->nullable();
            
            // Metadata
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index('status');
            $table->index('is_published');
            $table->index('rating');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};