<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->string('address_number', 30)->nullable()->after('address');
        });

        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->string('address_number', 30)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->dropColumn('address_number');
        });

        Schema::table('wash_locations', function (Blueprint $table) {
            $table->dropColumn('address_number');
        });
    }
};
