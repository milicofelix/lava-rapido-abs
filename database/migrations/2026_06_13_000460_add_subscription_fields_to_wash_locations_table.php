<?php

use App\Models\WashLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_locations', 'subscription_status')) {
                $table->string('subscription_status')->nullable()->after('account_status');
            }

            if (! Schema::hasColumn('wash_locations', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            }

            if (! Schema::hasColumn('wash_locations', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('subscription_ends_at');
            }
        });

        DB::table('wash_locations')
            ->whereNull('subscription_status')
            ->update(['subscription_status' => DB::raw('account_status')]);

        DB::table('wash_locations')
            ->where('subscription_status', WashLocation::ACCOUNT_STATUS_EXPIRED)
            ->whereNull('blocked_at')
            ->update(['blocked_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            if (Schema::hasColumn('wash_locations', 'blocked_at')) {
                $table->dropColumn('blocked_at');
            }

            if (Schema::hasColumn('wash_locations', 'subscription_ends_at')) {
                $table->dropColumn('subscription_ends_at');
            }

            if (Schema::hasColumn('wash_locations', 'subscription_status')) {
                $table->dropColumn('subscription_status');
            }
        });
    }
};
