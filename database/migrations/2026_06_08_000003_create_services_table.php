<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->unsignedSmallInteger('estimated_minutes');
            $table->boolean('active')->default(true);
            $table->string('category');
            $table->timestamps();

            $table->index(['name', 'category', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
