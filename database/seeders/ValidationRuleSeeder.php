<?php

namespace Database\Seeders;

use App\Models\ValidationRule;
use Illuminate\Database\Seeder;

class ValidationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultRules = [
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
        ];

        foreach ($defaultRules as $rule) {
            ValidationRule::updateOrCreate(
                [
                    'rule_key' => $rule['rule_key'],
                ],
                [
                    'handle' => $rule['rule_key'],
                    'name' => $rule['name'],
                    'description' => $rule['description'],
                    'severity' => $rule['severity'],
                    'is_enabled' => $rule['is_enabled'],
                    'config' => null,
                ]
            );
        }
    }
}
