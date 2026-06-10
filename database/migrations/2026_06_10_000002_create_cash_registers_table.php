<?php

use App\Models\CashRegister;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default(CashRegister::STATUS_OPEN);
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('counted_cash', 10, 2)->nullable();
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('cash_difference', 10, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
