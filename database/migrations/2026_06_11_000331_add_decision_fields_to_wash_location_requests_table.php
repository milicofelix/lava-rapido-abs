<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->text('decision_notes')->nullable()->after('status');
            $table->timestamp('decided_at')->nullable()->after('decision_notes');
            $table->foreignId('decided_by_user_id')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
            $table->foreignId('wash_location_id')->nullable()->after('decided_by_user_id')->constrained('wash_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wash_location_id');
            $table->dropConstrainedForeignId('decided_by_user_id');
            $table->dropColumn([
                'decision_notes',
                'decided_at',
            ]);
        });
    }
};
