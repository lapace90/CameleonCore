<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('action')->unique();
            $table->timestamps();
            
            $table->index('action'); // Pour les requêtes rapides
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};