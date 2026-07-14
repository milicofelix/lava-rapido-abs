<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wash_orders', function (Blueprint $table): void {
            $table->index(['wash_location_id', 'entered_at'], 'wash_orders_location_entered_index');
            $table->index(['wash_location_id', 'payment_status', 'entered_at'], 'wash_orders_location_payment_entered_index');
            $table->index(['wash_location_id', 'customer_id', 'entered_at'], 'wash_orders_location_customer_entered_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['paid_at', 'method'], 'payments_paid_method_index');
        });

        Schema::table('loyalty_coupons', function (Blueprint $table): void {
            $table->index(['wash_location_id', 'status', 'expires_at'], 'loyalty_coupons_location_status_expires_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['wash_location_id', 'action', 'created_at'], 'audit_logs_location_action_created_index');
            $table->index(['wash_location_id', 'user_id', 'created_at'], 'audit_logs_location_user_created_index');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->index(['wash_location_id', 'name'], 'customers_location_name_index');
            $table->index(['wash_location_id', 'phone'], 'customers_location_phone_index');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->index(['wash_location_id', 'customer_id'], 'vehicles_location_customer_index');
            $table->index(['wash_location_id', 'brand', 'model'], 'vehicles_location_brand_model_index');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropIndex('vehicles_location_brand_model_index');
            $table->dropIndex('vehicles_location_customer_index');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_location_phone_index');
            $table->dropIndex('customers_location_name_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_location_user_created_index');
            $table->dropIndex('audit_logs_location_action_created_index');
        });

        Schema::table('loyalty_coupons', function (Blueprint $table): void {
            $table->dropIndex('loyalty_coupons_location_status_expires_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_paid_method_index');
        });

        Schema::table('wash_orders', function (Blueprint $table): void {
            $table->dropIndex('wash_orders_location_customer_entered_index');
            $table->dropIndex('wash_orders_location_payment_entered_index');
            $table->dropIndex('wash_orders_location_entered_index');
        });
    }
};
