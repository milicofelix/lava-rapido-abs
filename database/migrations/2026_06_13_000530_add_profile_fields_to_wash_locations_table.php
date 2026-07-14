<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->string('legal_name')->nullable()->after('name');
            $table->string('document', 30)->nullable()->after('legal_name');
            $table->string('state', 2)->nullable()->after('city');
            $table->text('opening_hours')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('wash_locations', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'legal_name',
                'document',
                'state',
                'opening_hours',
            ]);
        });
    }
};
