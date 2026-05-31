<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'vendor')) {
                $table->string('vendor')->nullable()->after('title');
            }
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->nullable()->after('vendor');
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status')->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('products', 'handle')) {
                $table->string('handle')->nullable()->after('status');
            }
            if (!Schema::hasColumn('products', 'health_status')) {
                $table->string('health_status')->default('needs_review')->after('handle');
            }
            if (!Schema::hasColumn('products', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('health_status');
            }
        });

        if (Schema::hasColumn('products', 'health_status')) {
            try {
                $columns = DB::select("SHOW COLUMNS FROM products LIKE 'health_status'");
                if (!empty($columns)) {
                    $type = strtolower((string) ($columns[0]->Type ?? $columns[0]->type ?? ''));
                    if (str_contains($type, 'enum(') && !str_contains($type, 'needs_review')) {
                        DB::statement("ALTER TABLE products MODIFY health_status VARCHAR(255) DEFAULT 'needs_review'");
                    }
                }
            } catch (\Throwable $throwable) {
            }
        }

        Schema::table('product_varients', function (Blueprint $table) {
            if (!Schema::hasColumn('product_varients', 'sku')) {
                $table->string('sku')->nullable()->after('shopify_inventory_item_id');
            }
            if (!Schema::hasColumn('product_varients', 'price')) {
                $table->decimal('price', 14, 4)->nullable()->after('compare_at_price');
            }
            if (!Schema::hasColumn('product_varients', 'shopify_variant_id')) {
                $table->string('shopify_variant_id')->nullable()->after('shopify_product_varient_id');
            }
        });

        if (Schema::hasColumn('product_varients', 'price')) {
            try {
                $columns = DB::select("SHOW COLUMNS FROM product_varients LIKE 'price'");
                if (!empty($columns)) {
                    $type = strtolower((string) ($columns[0]->Type ?? $columns[0]->type ?? ''));
                    if (!str_contains($type, 'decimal')) {
                        DB::statement("ALTER TABLE product_varients MODIFY price DECIMAL(14,4) NULL");
                    }
                }
            } catch (\Throwable $throwable) {
            }
        }

        Schema::table('product_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('product_issues', 'issue_key')) {
                $table->string('issue_key')->after('shopify_product_id');
            }
            if (!Schema::hasColumn('product_issues', 'issue_type')) {
                $table->string('issue_type')->nullable()->after('issue_key');
            }
            if (!Schema::hasColumn('product_issues', 'severity')) {
                $table->string('severity')->after('issue_type');
            }
            if (!Schema::hasColumn('product_issues', 'status')) {
                $table->string('status')->default('open')->after('severity');
            }
            if (!Schema::hasColumn('product_issues', 'message')) {
                $table->text('message')->after('status');
            }
            if (!Schema::hasColumn('product_issues', 'suggested_fix')) {
                $table->text('suggested_fix')->nullable()->after('message');
            }
            if (!Schema::hasColumn('product_issues', 'detected_at')) {
                $table->timestamp('detected_at')->nullable()->after('suggested_fix');
            }
            if (!Schema::hasColumn('product_issues', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('detected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_issues', function (Blueprint $table) {
            if (Schema::hasColumn('product_issues', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
            if (Schema::hasColumn('product_issues', 'detected_at')) {
                $table->dropColumn('detected_at');
            }
            if (Schema::hasColumn('product_issues', 'suggested_fix')) {
                $table->dropColumn('suggested_fix');
            }
            if (Schema::hasColumn('product_issues', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('product_issues', 'issue_type')) {
                $table->dropColumn('issue_type');
            }
        });
    }
};
