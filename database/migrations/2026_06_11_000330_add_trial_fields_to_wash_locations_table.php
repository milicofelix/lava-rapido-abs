<?php

use App\Models\WashLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->string('account_status')->default(WashLocation::ACCOUNT_STATUS_ACTIVE)->after('status');
            $table->boolean('public_visible')->default(true)->after('account_status');
            $table->timestamp('trial_started_at')->nullable()->after('public_visible');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
            $table->foreignId('approved_location_request_id')->nullable()->after('trial_ends_at')->constrained('wash_location_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_location_request_id');
            $table->dropColumn([
                'account_status',
                'public_visible',
                'trial_started_at',
                'trial_ends_at',
            ]);
        });
    }
};
