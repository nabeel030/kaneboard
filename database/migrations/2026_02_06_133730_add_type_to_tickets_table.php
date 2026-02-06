<?php
use App\Enums\TicketType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('type', array_column(TicketType::cases(), 'value'))
                  ->default(TicketType::FEATURE->value)
                  ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
