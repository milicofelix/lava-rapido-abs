<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wash_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 40)->default('whatsapp_manual');
            $table->string('template_key', 80);
            $table->string('target')->nullable();
            $table->text('message');
            $table->text('action_url')->nullable();
            $table->string('status', 40)->default('prepared');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('manually_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['wash_order_id', 'channel']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
