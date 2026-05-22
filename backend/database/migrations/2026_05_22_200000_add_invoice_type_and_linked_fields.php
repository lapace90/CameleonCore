<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('type', ['deposit', 'balance', 'complete'])
                  ->default('complete')
                  ->after('status');

            $table->foreignId('linked_invoice_id')
                  ->nullable()
                  ->after('type')
                  ->constrained('invoices')
                  ->nullOnDelete();

            $table->string('factpulse_reference')
                  ->nullable()
                  ->after('linked_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['linked_invoice_id']);
            $table->dropColumn(['type', 'linked_invoice_id', 'factpulse_reference']);
        });
    }
};