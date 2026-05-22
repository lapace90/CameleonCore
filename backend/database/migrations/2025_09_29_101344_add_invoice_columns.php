<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Méthode de paiement
            $table->string('payment_method')->nullable()->after('status');
            
            // Date de paiement effective
            $table->datetime('payment_date')->nullable()->after('payment_method');
            
            // Notes internes
            $table->text('notes')->nullable()->after('payment_date');
            
            // Chemin du PDF généré
            $table->string('pdf_path')->nullable()->after('notes');
            
            // Date d'envoi par email
            $table->datetime('sent_at')->nullable()->after('pdf_path');
            
            // Compteur d'envois (tracking)
            $table->integer('sent_count')->default(0)->after('sent_at');
        });

        // Mettre à jour le statut enum pour inclure 'overdue'
        // Note: PostgreSQL ne permet pas de modifier facilement un ENUM
        // On va donc recréer la colonne avec les bonnes valeurs
        
        // 1. Supprimer l'ancienne contrainte CHECK si elle existe
        DB::statement("ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check");
        
        // 2. Sauvegarder les données actuelles
        DB::statement("ALTER TABLE invoices ALTER COLUMN status DROP DEFAULT");
        DB::statement("ALTER TABLE invoices ALTER COLUMN status TYPE VARCHAR(20)");
        
        // 3. Mettre à jour les valeurs existantes si nécessaire
        DB::table('invoices')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
            
        // 4. Ajouter la nouvelle contrainte CHECK avec toutes les valeurs
        DB::statement("
            ALTER TABLE invoices 
            ADD CONSTRAINT invoices_status_check 
            CHECK (status IN ('paid', 'pending', 'overdue', 'canceled'))
        ");
        
        // 5. Remettre la valeur par défaut
        DB::statement("ALTER TABLE invoices ALTER COLUMN status SET DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_date',
                'notes',
                'pdf_path',
                'sent_at',
                'sent_count'
            ]);
        });

        // Retour à l'enum original
        DB::statement("ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check");
        DB::statement("ALTER TABLE invoices ALTER COLUMN status TYPE VARCHAR(20)");
        DB::statement("
            ALTER TABLE invoices 
            ADD CONSTRAINT invoices_status_check 
            CHECK (status IN ('paid', 'pending', 'canceled'))
        ");
    }
};