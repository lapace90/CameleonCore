<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // dates réelles (timezone aware)
            $table->timestampTz('actual_checkin')->nullable()->after('checkin');
            $table->timestampTz('actual_checkout')->nullable()->after('actual_checkin');

            // petite aide perf (facultatif)
            $table->index('actual_checkin');
            $table->index('actual_checkout');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['actual_checkin']);
            $table->dropIndex(['actual_checkout']);
            $table->dropColumn(['actual_checkin','actual_checkout']);
        });
    }
};
