<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('validation_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('validation_rules', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (!Schema::hasColumn('validation_rules', 'rule_key')) {
                $table->string('rule_key')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('validation_rules', 'severity')) {
                $table->string('severity')->default('medium')->after('description');
            }

            if (!Schema::hasColumn('validation_rules', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('severity');
            }

            $table->unique(['user_id', 'rule_key'], 'validation_rules_user_id_rule_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('validation_rules', function (Blueprint $table) {
            $table->dropUnique('validation_rules_user_id_rule_key_unique');

            if (Schema::hasColumn('validation_rules', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }

            if (Schema::hasColumn('validation_rules', 'severity')) {
                $table->dropColumn('severity');
            }

            if (Schema::hasColumn('validation_rules', 'rule_key')) {
                $table->dropColumn('rule_key');
            }

            if (Schema::hasColumn('validation_rules', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
