<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('last_name');

            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            // Vérification email + tokens limités
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('limited_token', 128)->nullable()->unique(); // Str::random(64)
            $table->timestampTz('token_expires_at')->nullable();

            $table->timestamps();

            // Index utiles (optionnels)
            $table->index('phone');
            $table->index(['city', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
