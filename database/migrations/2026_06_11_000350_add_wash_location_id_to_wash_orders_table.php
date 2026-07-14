<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_orders', function (Blueprint $table) {
            $table->foreignId('wash_location_id')
                ->nullable()
                ->after('id')
                ->constrained('wash_locations')
                ->nullOnDelete();

            $table->index(['wash_location_id', 'status', 'entered_at'], 'wash_orders_location_status_entered_index');
        });
    }

    public function down(): void
    {
        Schema::table('wash_orders', function (Blueprint $table) {
            $table->dropForeign(['wash_location_id']);
            $table->dropIndex('wash_orders_location_status_entered_index');
            $table->dropColumn('wash_location_id');
        });
    }
};
