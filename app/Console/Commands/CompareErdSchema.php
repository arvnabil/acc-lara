<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompareErdSchema extends Command
{
    protected $signature = 'schema:compare-erd';

    protected $description = 'Compare the frozen ERD contract with information_schema';

    public function handle(): int
    {
        $spec = $this->columns();
        $diffs = 0;

        foreach ($spec as $table => $columns) {
            $actual = DB::table('information_schema.columns')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', $table)
                ->orderBy('ordinal_position')
                ->selectRaw('COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable')
                ->get()
                ->keyBy('column_name');

            $expectedNames = array_keys($columns);
            $actualNames = $actual->keys()->all();
            foreach (array_diff($expectedNames, $actualNames) as $name) {
                $this->error("COLUMN MISSING {$table}.{$name}");
                $diffs++;
            }
            foreach (array_diff($actualNames, $expectedNames) as $name) {
                $this->error("COLUMN EXTRA {$table}.{$name}");
                $diffs++;
            }
            foreach ($columns as $name => [$type, $nullable]) {
                if (! isset($actual[$name])) {
                    continue;
                }
                $got = [strtolower(str_replace(' ', '', $actual[$name]->column_type)), $actual[$name]->is_nullable === 'YES'];
                $want = [strtolower(str_replace(' ', '', $type)), $nullable];
                if ($got !== $want) {
                    $this->error("COLUMN DIFFERENCE {$table}.{$name} expected={$type}/".($nullable ? 'NULL' : 'NOT NULL')." actual={$actual[$name]->column_type}/{$actual[$name]->is_nullable}");
                    $diffs++;
                }
            }
        }

        foreach ($this->foreignKeys() as $expected) {
            [$table, $column, $parent, $deleteRule] = $expected;
            $actual = DB::table('information_schema.key_column_usage as k')
                ->join('information_schema.referential_constraints as r', function ($join) {
                    $join->on('r.constraint_schema', '=', 'k.constraint_schema')
                        ->on('r.constraint_name', '=', 'k.constraint_name');
                })
                ->whereRaw('k.table_schema = DATABASE()')
                ->where('k.table_name', $table)
                ->where('k.column_name', $column)
                ->whereNotNull('k.referenced_table_name')
                ->selectRaw('k.REFERENCED_TABLE_NAME AS referenced_table_name, r.DELETE_RULE AS delete_rule')
                ->first();
            $got = $actual ? [$actual->referenced_table_name, $actual->delete_rule] : ['MISSING', 'MISSING'];
            if ($got !== [$parent, $deleteRule]) {
                $this->error("FK DIFFERENCE {$table}.{$column} expected={$parent}/{$deleteRule} actual={$got[0]}/{$got[1]}");
                $diffs++;
            }
        }

        foreach ($this->uniqueIndexes() as $index => [$table, $columns]) {
            $actual = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->where('non_unique', 0)
                ->orderBy('seq_in_index')
                ->pluck('COLUMN_NAME')
                ->all();
            if ($actual !== $columns) {
                $this->error("UNIQUE DIFFERENCE {$table}.{$index} expected=".implode(',', $columns).' actual='.implode(',', $actual));
                $diffs++;
            }
        }

        $this->line($diffs === 0 ? 'ERD_COMPARE_OK columns/types/nullability/FKs/unique verified' : "ERD_COMPARE_DIFFS={$diffs}");

        return $diffs === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function columns(): array
    {
        $t = fn (string $type, bool $nullable = true): array => [$type, $nullable];
        $id = $t('bigint unsigned', false);
        $timestamp = $t('timestamp');
        $json = $t('json');

        return [
            'promo_codes' => ['id' => $id, 'code' => $t('varchar(50)', false), 'label' => $t('varchar(255)', false), 'type' => $t("enum('percent','fixed')", false), 'value' => $t('bigint unsigned', false), 'min_order' => $t('bigint unsigned', false), 'max_uses' => $t('int unsigned'), 'used_count' => $t('int unsigned', false), 'valid_from' => $timestamp, 'valid_until' => $timestamp, 'is_active' => $t('tinyint(1)', false), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'vouchers' => ['id' => $id, 'code' => $t('varchar(100)', false), 'title' => $t('varchar(255)', false), 'type' => $t("enum('fixed','percent','free_shipping')", false), 'discount_value' => $t('bigint unsigned', false), 'min_order' => $t('bigint unsigned', false), 'client_id' => $t('bigint unsigned', false), 'category_id' => $t('bigint unsigned'), 'source' => $t("enum('tier_bronze','redeem','free_shipping')", false), 'points_cost' => $t('int unsigned'), 'used_at' => $timestamp, 'expires_at' => $timestamp, 'quarter_key' => $t('varchar(7)'), 'month_key' => $t('varchar(7)'), 'slot' => $t('tinyint unsigned'), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'carts' => ['id' => $id, 'user_id' => $t('bigint unsigned'), 'session_id' => $t('varchar(100)'), 'promo_code_id' => $t('bigint unsigned'), 'voucher_id' => $t('bigint unsigned'), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'cart_items' => ['id' => $id, 'cart_id' => $t('bigint unsigned', false), 'product_id' => $t('bigint unsigned', false), 'variant_id' => $t('bigint unsigned'), 'quantity' => $t('int', false), 'rental_start' => $t('date'), 'rental_end' => $t('date'), 'rental_start_time' => $t('varchar(5)'), 'rental_end_time' => $t('varchar(5)'), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'wishlists' => ['id' => $id, 'user_id' => $t('bigint unsigned', false), 'product_id' => $t('bigint unsigned', false), 'created_at' => $timestamp],
            'orders' => ['id' => $id, 'number' => $t('varchar(20)', false), 'user_id' => $t('bigint unsigned', false), 'client_id' => $t('bigint unsigned', false), 'status' => $t("enum('pending_payment','awaiting_verification','paid','processing','packed','shipped','completed','returned','cancelled')", false), 'payment_type' => $t('varchar(20)', false), 'net_days' => $t('tinyint unsigned'), 'goods_subtotal' => $t('bigint unsigned', false), 'discount_on_goods' => $t('bigint unsigned', false), 'ppn' => $t('bigint unsigned', false), 'shipping_cost' => $t('bigint unsigned', false), 'admin_fee' => $t('bigint unsigned', false), 'grand_total' => $t('bigint unsigned', false), 'has_dummy_price_item' => $t('tinyint(1)', false), 'promo_code_id' => $t('bigint unsigned'), 'voucher_id' => $t('bigint unsigned'), 'shipping_method' => $t('varchar(100)'), 'shipping_label' => $t('varchar(255)'), 'tracking_number' => $t('varchar(100)'), 'shipping_address' => $t('json', false), 'payment_method' => $t('varchar(50)'), 'paid_at' => $timestamp, 'completed_at' => $timestamp, 'shipped_at' => $timestamp, 'invoice_paid_at' => $timestamp, 'points_awarded_at' => $timestamp, 'points_skipped_reason' => $t('varchar(30)'), 'customer_notes' => $t('text'), 'admin_notes' => $t('text'), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'order_status_history' => ['id' => $id, 'order_id' => $t('bigint unsigned', false), 'from_status' => $t('varchar(30)'), 'to_status' => $t('varchar(30)', false), 'actor_id' => $t('bigint unsigned'), 'notes' => $t('text'), 'created_at' => $t('timestamp', false)],
            'order_items' => ['id' => $id, 'order_id' => $t('bigint unsigned', false), 'product_id' => $t('bigint unsigned', false), 'variant_id' => $t('bigint unsigned'), 'product_name' => $t('varchar(500)', false), 'variant_name' => $t('varchar(255)'), 'sku' => $t('varchar(100)'), 'unit_price' => $t('bigint unsigned', false), 'quantity' => $t('int', false), 'subtotal' => $t('bigint unsigned', false), 'is_dummy_price' => $t('tinyint(1)', false), 'rental_start' => $t('date'), 'rental_end' => $t('date'), 'rental_days' => $t('int'), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'payment_proofs' => ['id' => $id, 'order_id' => $t('bigint unsigned', false), 'file_path' => $t('varchar(500)', false), 'uploaded_at' => $timestamp, 'verified_at' => $timestamp, 'verified_by' => $t('bigint unsigned')],
            'payment_transactions' => ['id' => $id, 'order_id' => $t('bigint unsigned', false), 'provider' => $t('varchar(20)', false), 'provider_order_id' => $t('varchar(50)', false), 'attempt' => $t('tinyint unsigned', false), 'snap_token' => $t('varchar(100)'), 'redirect_url' => $t('varchar(500)'), 'status' => $t('varchar(30)', false), 'payment_channel' => $t('varchar(50)'), 'gross_amount' => $t('bigint unsigned', false), 'expires_at' => $t('timestamp', false), 'paid_at' => $timestamp, 'last_checked_at' => $timestamp, 'raw' => $json, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'loyalty_accounts' => ['id' => $id, 'client_id' => $t('bigint unsigned', false), 'tier_points' => $t('int', false), 'points_balance' => $t('int', false), 'current_tier' => $t("enum('classic','bronze','silver','gold','platinum','diamond')", false), 'tier_valid_until' => $t('date', false), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'point_ledger' => ['id' => $id, 'loyalty_account_id' => $t('bigint unsigned', false), 'order_id' => $t('bigint unsigned'), 'review_id' => $t('bigint unsigned'), 'type' => $t("enum('earn_base','earn_bonus','earn_review','redeem_ewallet','redeem_voucher','expire','reversal','adjustment')", false), 'points' => $t('int', false), 'remaining_points' => $t('int'), 'counts_for_tier' => $t('tinyint(1)', false), 'earned_at' => $t('timestamp', false), 'expires_at' => $timestamp, 'idempotency_key' => $t('varchar(100)'), 'notes' => $t('varchar(500)'), 'created_at' => $timestamp],
            'redemptions' => ['id' => $id, 'loyalty_account_id' => $t('bigint unsigned', false), 'type' => $t("enum('ewallet','voucher')", false), 'points' => $t('int unsigned', false), 'amount_idr' => $t('bigint unsigned', false), 'ewallet_provider' => $t('varchar(50)'), 'ewallet_account' => $t('varchar(100)'), 'ewallet_account_name' => $t('varchar(255)'), 'voucher_id' => $t('bigint unsigned'), 'status' => $t("enum('pending','paid','failed','cancelled')", false), 'requested_by' => $t('bigint unsigned', false), 'processed_by' => $t('bigint unsigned'), 'processed_at' => $timestamp, 'rejection_reason' => $t('text'), 'idempotency_key' => $t('varchar(100)', false), 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'reviews' => ['id' => $id, 'order_id' => $t('bigint unsigned', false), 'order_item_id' => $t('bigint unsigned', false), 'product_id' => $t('bigint unsigned', false), 'user_id' => $t('bigint unsigned', false), 'client_id' => $t('bigint unsigned', false), 'rating' => $t('tinyint unsigned', false), 'body' => $t('text', false), 'is_approved' => $t('tinyint(1)', false), 'is_anonymous' => $t('tinyint(1)', false), 'media' => $json, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'banners' => ['id' => $id, 'title' => $t('varchar(255)', false), 'image_url' => $t('varchar(500)', false), 'mobile_image_url' => $t('varchar(500)'), 'target_url' => $t('varchar(500)'), 'sort_order' => $t('int', false), 'is_active' => $t('tinyint(1)', false), 'starts_at' => $timestamp, 'expires_at' => $timestamp, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            'rfq_requests' => ['id' => $id, 'name' => $t('varchar(255)', false), 'email' => $t('varchar(255)', false), 'phone' => $t('varchar(30)', false), 'company_name' => $t('varchar(255)', false), 'message' => $t('text'), 'status' => $t("enum('new','in_progress','quoted','closed')", false), 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];
    }

    private function foreignKeys(): array
    {
        return [['vouchers', 'client_id', 'clients', 'RESTRICT'], ['vouchers', 'category_id', 'categories', 'RESTRICT'], ['carts', 'user_id', 'users', 'CASCADE'], ['carts', 'promo_code_id', 'promo_codes', 'SET NULL'], ['carts', 'voucher_id', 'vouchers', 'SET NULL'], ['cart_items', 'cart_id', 'carts', 'CASCADE'], ['cart_items', 'product_id', 'products', 'CASCADE'], ['cart_items', 'variant_id', 'variants', 'CASCADE'], ['wishlists', 'user_id', 'users', 'CASCADE'], ['wishlists', 'product_id', 'products', 'CASCADE'], ['orders', 'user_id', 'users', 'RESTRICT'], ['orders', 'client_id', 'clients', 'RESTRICT'], ['orders', 'promo_code_id', 'promo_codes', 'RESTRICT'], ['orders', 'voucher_id', 'vouchers', 'RESTRICT'], ['order_status_history', 'order_id', 'orders', 'RESTRICT'], ['order_status_history', 'actor_id', 'users', 'SET NULL'], ['order_items', 'order_id', 'orders', 'RESTRICT'], ['order_items', 'product_id', 'products', 'RESTRICT'], ['order_items', 'variant_id', 'variants', 'RESTRICT'], ['payment_proofs', 'order_id', 'orders', 'RESTRICT'], ['payment_proofs', 'verified_by', 'users', 'RESTRICT'], ['payment_transactions', 'order_id', 'orders', 'RESTRICT'], ['loyalty_accounts', 'client_id', 'clients', 'RESTRICT'], ['point_ledger', 'loyalty_account_id', 'loyalty_accounts', 'RESTRICT'], ['point_ledger', 'order_id', 'orders', 'RESTRICT'], ['redemptions', 'loyalty_account_id', 'loyalty_accounts', 'RESTRICT'], ['redemptions', 'voucher_id', 'vouchers', 'RESTRICT'], ['redemptions', 'requested_by', 'users', 'RESTRICT'], ['redemptions', 'processed_by', 'users', 'SET NULL'], ['reviews', 'client_id', 'clients', 'RESTRICT'], ['reviews', 'order_id', 'orders', 'RESTRICT'], ['reviews', 'order_item_id', 'order_items', 'RESTRICT'], ['reviews', 'product_id', 'products', 'RESTRICT'], ['reviews', 'user_id', 'users', 'RESTRICT']];
    }

    private function uniqueIndexes(): array
    {
        return ['promo_codes_code_unique' => ['promo_codes', ['code']], 'vouchers_code_unique' => ['vouchers', ['code']], 'uq_bronze_per_quarter' => ['vouchers', ['client_id', 'source', 'quarter_key']], 'uq_free_shipping_slot' => ['vouchers', ['client_id', 'source', 'month_key', 'slot']], 'uq_user_product' => ['wishlists', ['user_id', 'product_id']], 'orders_number_unique' => ['orders', ['number']], 'payment_transactions_provider_order_id_unique' => ['payment_transactions', ['provider_order_id']], 'loyalty_accounts_client_id_unique' => ['loyalty_accounts', ['client_id']], 'point_ledger_idempotency_key_unique' => ['point_ledger', ['idempotency_key']], 'redemptions_idempotency_key_unique' => ['redemptions', ['idempotency_key']], 'uq_company_product' => ['reviews', ['client_id', 'product_id']]];
    }
}


