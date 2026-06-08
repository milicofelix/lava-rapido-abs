<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_wash_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('service_name');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('estimated_minutes');
            $table->timestamps();

            $table->unique(['wash_order_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_wash_order');
    }
};
