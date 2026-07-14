<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_orders', function (Blueprint $table) {
            $table->foreignId('loyalty_coupon_id')->nullable()->after('payment_status')->constrained('loyalty_coupons')->nullOnDelete();
            $table->decimal('loyalty_discount_amount', 10, 2)->default(0)->after('total_amount');
        });

        Schema::table('loyalty_coupons', function (Blueprint $table) {
            $table->foreignId('used_wash_order_id')->nullable()->after('source_wash_order_id')->constrained('wash_orders')->nullOnDelete();
            $table->foreignId('used_by_user_id')->nullable()->after('used_wash_order_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_by_user_id');
            $table->dropConstrainedForeignId('used_wash_order_id');
        });

        Schema::table('wash_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_coupon_id');
            $table->dropColumn('loyalty_discount_amount');
        });
    }
};
