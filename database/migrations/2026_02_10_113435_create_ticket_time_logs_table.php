<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_time_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->unsignedInteger('duration_seconds')->default(0);

            $table->string('note')->nullable(); 
            $table->timestamps();

            $table->index(['ticket_id', 'user_id']);
            $table->index(['user_id', 'ended_at']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_time_logs');
    }
};
