<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique(['plate']);
            $table->unique(['wash_location_id', 'plate'], 'vehicles_wash_location_plate_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique('vehicles_wash_location_plate_unique');
            $table->unique('plate');
        });
    }
};
