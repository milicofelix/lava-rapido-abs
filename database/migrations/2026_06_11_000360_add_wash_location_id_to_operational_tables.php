<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'vehicles', 'services', 'cash_registers'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                if (! Schema::hasColumn($table->getTable(), 'wash_location_id')) {
                    $table->foreignId('wash_location_id')->nullable()->after('id')->constrained('wash_locations')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'vehicles', 'services', 'cash_registers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'wash_location_id')) {
                    $table->dropConstrainedForeignId('wash_location_id');
                }
            });
        }
    }
};
