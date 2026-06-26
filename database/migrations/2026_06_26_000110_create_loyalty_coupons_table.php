<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_location_id')->constrained('wash_locations')->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_wash_order_id')->constrained('wash_orders')->cascadeOnDelete();
            $table->foreignId('reward_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->timestamp('earned_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wash_location_id', 'customer_id', 'status']);
            $table->index(['loyalty_program_id', 'earned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_coupons');
    }
};
