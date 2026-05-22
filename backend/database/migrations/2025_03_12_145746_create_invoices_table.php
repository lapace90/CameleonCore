<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 8, 2);
            $table->dateTime('issue_date');
            $table->dateTime('due_date');
            $table->enum('status', ['paid', 'pending', 'canceled'])->default('pending');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->timestamps();

            // Définir les clés étrangères
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};