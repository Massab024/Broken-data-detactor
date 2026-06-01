<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('validation_rules')) {
            return;
        }

        $now = now();
        $rules = [
            [
                'rule_key' => 'missing_product_title',
                'name' => 'Missing Product Title',
                'description' => 'Product title is missing or empty.',
                'severity' => 'critical',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'invalid_product_price',
                'name' => 'Invalid Product Price',
                'description' => 'Product price is missing, zero, negative, or invalid.',
                'severity' => 'critical',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'product_has_no_variants',
                'name' => 'Product Has No Variants',
                'description' => 'Product does not have any variants.',
                'severity' => 'high',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'variant_missing_price',
                'name' => 'Variant Missing Price',
                'description' => 'One or more variants have missing or invalid price.',
                'severity' => 'high',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'missing_product_image',
                'name' => 'Missing Product Image',
                'description' => 'Product does not have an image.',
                'severity' => 'medium',
                'is_enabled' => false,
            ],
            [
                'rule_key' => 'missing_vendor',
                'name' => 'Missing Vendor',
                'description' => 'Product vendor is missing.',
                'severity' => 'low',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'missing_product_type',
                'name' => 'Missing Product Type',
                'description' => 'Product type is missing.',
                'severity' => 'low',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'missing_sku',
                'name' => 'Missing SKU',
                'description' => 'One or more product variants are missing SKU.',
                'severity' => 'low',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'product_status_draft',
                'name' => 'Product Status is Draft',
                'description' => 'Product is currently in draft status and should be reviewed.',
                'severity' => 'low',
                'is_enabled' => true,
            ],
            [
                'rule_key' => 'weak_handle',
                'name' => 'Weak Handle',
                'description' => 'Product handle is missing, too short, or not SEO-friendly.',
                'severity' => 'low',
                'is_enabled' => true,
            ],
        ];

        $rows = [];
        $hasHandle = Schema::hasColumn('validation_rules', 'handle');
        $hasConfig = Schema::hasColumn('validation_rules', 'config');
        $updateColumns = ['name', 'description', 'severity', 'is_enabled', 'updated_at'];

        if ($hasHandle) {
            $updateColumns[] = 'handle';
        }

        if ($hasConfig) {
            $updateColumns[] = 'config';
        }

        foreach ($rules as $rule) {
            $row = [
                'rule_key' => $rule['rule_key'],
                'name' => $rule['name'],
                'description' => $rule['description'],
                'severity' => $rule['severity'],
                'is_enabled' => $rule['is_enabled'],
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if ($hasHandle) {
                $row['handle'] = $rule['rule_key'];
            }

            if ($hasConfig) {
                $row['config'] = null;
            }

            $rows[] = $row;
        }

        DB::table('validation_rules')->upsert($rows, ['rule_key'], $updateColumns);
    }

    public function down(): void
    {
        if (!Schema::hasTable('validation_rules')) {
            return;
        }

        DB::table('validation_rules')
            ->whereIn('rule_key', [
                'missing_product_title',
                'invalid_product_price',
                'product_has_no_variants',
                'variant_missing_price',
                'missing_product_image',
                'missing_vendor',
                'missing_product_type',
                'missing_sku',
                'product_status_draft',
                'weak_handle',
            ])
            ->delete();
    }
};
