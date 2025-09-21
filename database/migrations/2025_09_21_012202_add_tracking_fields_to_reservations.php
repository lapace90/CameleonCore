<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Référence vers le devis d'origine
            $table->string('quote_reference')->nullable()->after('invoice_number');
            
            // Informations de paiement Stripe
            $table->string('stripe_session_id')->nullable()->after('quote_reference');
            $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            
            // Lien vers réservation parent (pour produits additionnels)
            $table->unsignedBigInteger('parent_reservation_id')->nullable()->after('stripe_payment_intent');
            
            // Méthode de paiement
            $table->enum('payment_method', ['stripe_card', 'bank_transfer', 'cash', 'other'])
                  ->default('stripe_card')
                  ->after('payment_status');
            
            // Index pour performance
            $table->index('quote_reference');
            $table->index('parent_reservation_id');
            $table->index(['checkin', 'checkout']);
            $table->index(['status', 'payment_status']);
        });

        // ✅ CORRECTION POSTGRESQL : Modifier la contrainte status séparément
        if (DB::getDriverName() === 'pgsql') {
            // Étape 1: Supprimer l'ancienne contrainte s'il y en a une
            DB::statement("ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_status_check");
            
            // Étape 2: Ajouter la nouvelle contrainte avec plus d'options
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_status_check CHECK (status IN ('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'))");
        } else {
            // Pour MySQL/autres bases
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])
                      ->default('pending')
                      ->change();
            });
        }
    }

    public function down(): void
    {
        // Restaurer l'ancienne contrainte status
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_status_check");
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_status_check CHECK (status IN ('confirmed', 'cancelled', 'pending'))");
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('status', ['confirmed', 'cancelled', 'pending'])
                      ->default('pending')
                      ->change();
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['quote_reference']);
            $table->dropIndex(['parent_reservation_id']);
            $table->dropIndex(['checkin', 'checkout']);
            $table->dropIndex(['status', 'payment_status']);
            
            $table->dropColumn([
                'quote_reference',
                'stripe_session_id',
                'stripe_payment_intent',
                'parent_reservation_id',
                'payment_method'
            ]);
        });
    }
};