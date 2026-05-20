<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable("validation_rules")) {
            return;
        }

        if (Schema::hasColumn("validation_rules", "handle") && Schema::hasColumn("validation_rules", "rule_key")) {
            DB::table("validation_rules")
                ->whereNull("rule_key")
                ->whereNotNull("handle")
                ->update(["rule_key" => DB::raw("handle")]);
        }

        if (Schema::hasColumn("validation_rules", "rule_key")) {
            $keepIds = DB::table("validation_rules")
                ->whereNotNull("rule_key")
                ->selectRaw("MIN(id) as id")
                ->groupBy("rule_key")
                ->pluck("id")
                ->filter()
                ->values()
                ->all();
            
            if (!empty($keepIds)) {
                DB::table("validation_rules")
                    ->whereNotIn("id", $keepIds)
                    ->delete();
            }
        }

        if (Schema::hasColumn("validation_rules", "active")) {
            Schema::table("validation_rules", function (Blueprint $table) {
                $table->dropColumn("active");
            });
        }

        try {
            Schema::table("validation_rules", function (Blueprint $table) {
                $table->unique("rule_key", "validation_rules_rule_key_unique");
            });
        } catch (\Throwable $throwable) {
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable("validation_rules")) {
            return;
        }

        Schema::table("validation_rules", function (Blueprint $table) {
            try {
                $table->dropUnique("validation_rules_rule_key_unique");
            } catch (\Throwable $throwable) {
            }
        });
    }
};
