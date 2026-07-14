<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_location_id')->unique()->constrained('wash_locations')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('threshold')->default(10);
            $table->string('count_scope')->default('any');
            $table->foreignId('qualifying_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('qualifying_category')->nullable();
            $table->string('reward_type')->default('fixed_service');
            $table->foreignId('reward_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->unsignedSmallInteger('coupon_valid_days')->default(30);
            $table->timestamps();

            $table->index(['is_active', 'count_scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
