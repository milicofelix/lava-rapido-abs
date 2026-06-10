<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plate')->unique();
            $table->string('model');
            $table->string('brand');
            $table->string('color');
            $table->string('type');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['plate', 'model', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
