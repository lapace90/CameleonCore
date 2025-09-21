
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->enum('type', ['maintenance', 'animation', 'formation', 'other'])->default('other');
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('animator')->nullable();
            $table->string('technician')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->string('status')->default('active');
            $table->string('background_color', 7)->default('#28a745');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};