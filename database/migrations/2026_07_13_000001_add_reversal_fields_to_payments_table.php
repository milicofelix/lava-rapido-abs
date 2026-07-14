<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('paid_at');
            $table->foreignId('reversed_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable()->after('notes');
            $table->index(['reversed_at', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['reversed_at', 'paid_at']);
            $table->dropConstrainedForeignId('reversed_by_user_id');
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
    }
};
