<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wash_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('district')->nullable();
            $table->string('city')->default('Sao Paulo');
            $table->string('status')->default('open');
            $table->unsignedTinyInteger('map_x')->default(50);
            $table->unsignedTinyInteger('map_y')->default(50);
            $table->unsignedSmallInteger('active_orders_count')->default(0);
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->index(['status', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wash_locations');
    }
};
