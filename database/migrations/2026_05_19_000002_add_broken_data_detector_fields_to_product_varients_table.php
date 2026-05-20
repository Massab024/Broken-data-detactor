<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_varients', function (Blueprint $table) {
            if (!Schema::hasColumn('product_varients', 'shopify_variant_id')) {
                $table->unsignedBigInteger('shopify_variant_id')->nullable()->after('shopify_product_varient_id');
            }
            if (!Schema::hasColumn('product_varients', 'raw_data')) {
                $table->json('raw_data')->nullable()->after('inventory_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_varients', function (Blueprint $table) {
            if (Schema::hasColumn('product_varients', 'raw_data')) {
                $table->dropColumn('raw_data');
            }
            if (Schema::hasColumn('product_varients', 'shopify_variant_id')) {
                $table->dropColumn('shopify_variant_id');
            }
        });
    }
};
