<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url')->nullable()->after('status');
            }
            if (!Schema::hasColumn('products', 'variant_count')) {
                $table->unsignedInteger('variant_count')->default(0)->after('image_url');
            }
            if (!Schema::hasColumn('products', 'has_variants')) {
                $table->boolean('has_variants')->default(false)->after('variant_count');
            }
            if (!Schema::hasColumn('products', 'min_price')) {
                $table->decimal('min_price', 14, 4)->nullable()->after('has_variants');
            }
            if (!Schema::hasColumn('products', 'max_price')) {
                $table->decimal('max_price', 14, 4)->nullable()->after('min_price');
            }
            if (!Schema::hasColumn('products', 'health_status')) {
                $table->string('health_status')->default('needs_review')->after('max_price');
            }
            if (!Schema::hasColumn('products', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('health_status');
            }
            if (!Schema::hasColumn('products', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('last_synced_at');
            }
            if (!Schema::hasColumn('products', 'raw_data')) {
                $table->json('raw_data')->nullable()->after('last_checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'raw_data')) {
                $table->dropColumn('raw_data');
            }
            if (Schema::hasColumn('products', 'last_checked_at')) {
                $table->dropColumn('last_checked_at');
            }
            if (Schema::hasColumn('products', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            if (Schema::hasColumn('products', 'health_status')) {
                $table->dropColumn('health_status');
            }
            if (Schema::hasColumn('products', 'max_price')) {
                $table->dropColumn('max_price');
            }
            if (Schema::hasColumn('products', 'min_price')) {
                $table->dropColumn('min_price');
            }
            if (Schema::hasColumn('products', 'has_variants')) {
                $table->dropColumn('has_variants');
            }
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
            if (Schema::hasColumn('products', 'variant_count')) {
                $table->dropColumn('variant_count');
            }
        });
    }
};
