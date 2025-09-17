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
        // Migration pour Customer
        Schema::table('customers', function (Blueprint $table) {
            $table->string('last_name')->after('name');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('limited_token', 64)->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->index('limited_token');
            $table->index(['email', 'email_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
