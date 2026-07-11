<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionDatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_tables_have_production_query_indexes(): void
    {
        $expectedIndexes = [
            'wash_orders' => [
                'wash_orders_location_entered_index',
                'wash_orders_location_payment_entered_index',
                'wash_orders_location_customer_entered_index',
            ],
            'payments' => [
                'payments_paid_method_index',
            ],
            'loyalty_coupons' => [
                'loyalty_coupons_location_status_expires_index',
            ],
            'audit_logs' => [
                'audit_logs_location_action_created_index',
                'audit_logs_location_user_created_index',
            ],
            'customers' => [
                'customers_location_name_index',
                'customers_location_phone_index',
            ],
            'vehicles' => [
                'vehicles_location_customer_index',
                'vehicles_location_brand_model_index',
            ],
        ];

        foreach ($expectedIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->assertTrue(
                    Schema::hasIndex($table, $index),
                    "O indice {$index} deveria existir na tabela {$table}.",
                );
            }
        }
    }
}
