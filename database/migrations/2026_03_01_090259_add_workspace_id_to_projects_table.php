<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')->after('id')->nullable();
            }
        });

        DB::table('projects')
            ->whereNotIn('workspace_id', DB::table('workspaces')->pluck('id'))
            ->orWhereNull('workspace_id')
            ->update(['workspace_id' => 1]);

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete()
                ->change();

            $table->index(['workspace_id', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
    }
};
