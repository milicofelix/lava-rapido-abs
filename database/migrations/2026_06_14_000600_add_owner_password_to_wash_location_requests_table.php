<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->string('owner_password')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('wash_location_requests', function (Blueprint $table) {
            $table->dropColumn('owner_password');
        });
    }
};
