<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('payment_provider')->nullable();
            $table->string('external_reference')->nullable()->unique();
            $table->string('provider_preference_id')->nullable();
            $table->string('provider_payment_id')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['external_reference']);
            $table->dropColumn([
                'payment_provider',
                'external_reference',
                'provider_preference_id',
                'provider_payment_id',
                'checkout_url',
                'paid_at',
                'provider_payload',
            ]);
        });
    }
};
