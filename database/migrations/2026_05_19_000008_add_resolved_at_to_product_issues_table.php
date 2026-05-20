<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('product_issues', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('metadata');
            }

            try {
                $table->index(['product_id', 'issue_key', 'resolved_at'], 'product_issues_lookup_index');
            } catch (\Throwable $throwable) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_issues', function (Blueprint $table) {
            try {
                $table->dropIndex('product_issues_lookup_index');
            } catch (\Throwable $throwable) {
            }

            if (Schema::hasColumn('product_issues', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
        });
    }
};
