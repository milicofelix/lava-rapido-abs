<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_wash_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wash_order_id', 'user_id']);
        });

        DB::table('wash_orders')
            ->whereNotNull('assigned_user_id')
            ->orderBy('id')
            ->select(['id', 'assigned_user_id', 'created_at', 'updated_at'])
            ->lazy()
            ->each(fn ($washOrder) => DB::table('user_wash_order')->insertOrIgnore([
                'wash_order_id' => $washOrder->id,
                'user_id' => $washOrder->assigned_user_id,
                'created_at' => $washOrder->created_at,
                'updated_at' => $washOrder->updated_at,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wash_order');
    }
};
