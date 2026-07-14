<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('customer_review_rating')->nullable()->after('notes');
            $table->text('customer_review_comment')->nullable()->after('customer_review_rating');
            $table->boolean('customer_review_public')->default(false)->after('customer_review_comment');
            $table->timestamp('customer_reviewed_at')->nullable()->after('customer_review_public');
            $table->index(['wash_location_id', 'customer_review_public', 'customer_review_rating'], 'wash_orders_public_reviews_index');
        });
    }

    public function down(): void
    {
        Schema::table('wash_orders', function (Blueprint $table) {
            $table->dropIndex('wash_orders_public_reviews_index');
            $table->dropColumn([
                'customer_review_rating',
                'customer_review_comment',
                'customer_review_public',
                'customer_reviewed_at',
            ]);
        });
    }
};
