<?php

// ===================================
// 1. Migration pour QuoteRequest
// ===================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            // Champs pour lier avec les réservations créées
            $table->timestamp('converted_to_reservation_at')->nullable()->after('status');
            $table->unsignedBigInteger('main_reservation_id')->nullable()->after('converted_to_reservation_at');
            
            // Champs pour améliorer le tracking
            $table->string('stripe_session_id')->nullable()->after('main_reservation_id');
            $table->string('payment_intent_id')->nullable()->after('stripe_session_id');
            
            // Index pour performance
            $table->index(['status', 'converted_to_reservation_at']);
            $table->index('main_reservation_id');
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'converted_to_reservation_at']);
            $table->dropIndex(['main_reservation_id']);
            
            $table->dropColumn([
                'converted_to_reservation_at',
                'main_reservation_id',
                'stripe_session_id',
                'payment_intent_id'
            ]);
        });
    }
};

