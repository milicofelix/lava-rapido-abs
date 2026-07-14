<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('wash_locations', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('opening_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            if (Schema::hasColumn('wash_locations', 'business_hours')) {
                $table->dropColumn('business_hours');
            }
        });
    }
};
