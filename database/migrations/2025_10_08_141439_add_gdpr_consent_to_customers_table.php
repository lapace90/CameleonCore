<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Consentements RGPD
            $table->boolean('gdpr_consent')->default(false)->after('country');
            $table->timestamp('gdpr_consent_at')->nullable()->after('gdpr_consent');
            $table->string('gdpr_consent_ip', 45)->nullable()->after('gdpr_consent_at');
            
            $table->boolean('newsletter_consent')->default(false)->after('gdpr_consent_ip');
            $table->timestamp('newsletter_consent_at')->nullable()->after('newsletter_consent');
            
            // Index pour requêtes
            $table->index('newsletter_consent');
            $table->index('gdpr_consent');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['newsletter_consent']);
            $table->dropIndex(['gdpr_consent']);
            $table->dropColumn([
                'gdpr_consent',
                'gdpr_consent_at',
                'gdpr_consent_ip',
                'newsletter_consent',
                'newsletter_consent_at'
            ]);
        });
    }
};