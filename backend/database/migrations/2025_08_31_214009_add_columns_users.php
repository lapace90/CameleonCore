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
        Schema::table('users', function (Blueprint $table) {
            // Statut de l'utilisateur
            $table->enum('status', ['active', 'inactive', 'blocked'])
                  ->default('active')
                  ->after('email_verified_at');
            
            // Rôle principal (relation belongsTo avec roles)
            $table->unsignedBigInteger('role_id')->nullable()->after('status');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            
            // Champs de suivi des connexions
            $table->timestamp('last_login_at')->nullable()->after('role_id');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            
            // Champ pour forcer la réinitialisation du mot de passe
            $table->boolean('password_reset_required')->default(false)->after('last_login_ip');
            
            // Métadonnées utilisateur (JSON pour flexibilité)
            $table->json('metadata')->nullable()->after('password_reset_required');
            
            // Champs de soft delete si pas déjà présents
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère avant de supprimer la colonne
            $table->dropForeign(['role_id']);
            
            // Supprimer les colonnes ajoutées
            $table->dropColumn([
                'status',
                'role_id', 
                'last_login_at',
                'last_login_ip',
                'password_reset_required',
                'metadata'
            ]);
            
            // Supprimer soft deletes si ajouté
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};