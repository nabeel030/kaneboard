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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('deadline');
            $table->dateTime('completed_at')->nullable()->after('started_at');
            $table->integer('estimate')->nullable()->after('completed_at');

            $table->index(['project_id', 'deadline'], 'tickets_project_deadline_idx');
            $table->index(['project_id', 'completed_at'], 'tickets_project_completed_idx');
            $table->index(['project_id', 'status'], 'tickets_project_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_project_deadline_idx');
            $table->dropIndex('tickets_project_completed_idx');
            $table->dropIndex('tickets_project_status_idx');
            
            $table->dropColumn(['started_at', 'completed_at', 'estimate']);
        });
    }
};
