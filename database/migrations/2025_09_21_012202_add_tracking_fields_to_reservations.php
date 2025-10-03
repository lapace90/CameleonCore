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
            
            // Méthode de paiement - TOUTES les méthodes possibles
            $table->enum('payment_method', [
                'stripe_card',     // Stripe carte bancaire
                'stripe',          // Stripe générique
                'card',            // Carte bancaire directe
                'cash',            // Espèces
                'bank_transfer',   // Virement bancaire
                'transfer',        // Virement (alias)
                'check',           // Chèque
                'paypal',          // PayPal
                'online',          // Paiement en ligne générique
                'other'            // Autre
            ])
                ->nullable()  // NULLABLE au lieu de default
                ->after('payment_status');
            
            // Index pour performance
            $table->index('quote_reference');
            $table->index('parent_reservation_id');
            $table->index(['checkin', 'checkout']);
            $table->index(['status', 'payment_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_status_check");
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_status_check CHECK (status IN ('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'))");
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])
                    ->default('pending')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_status_check");
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_status_check CHECK (status IN ('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'))");
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])
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