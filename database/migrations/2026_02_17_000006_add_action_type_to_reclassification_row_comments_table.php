<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reclassification_row_comments')) {
            return;
        }

        if (!Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            Schema::table('reclassification_row_comments', function (Blueprint $table) {
                $table->string('action_type', 20)
                    ->default('requires_action')
                    ->after('visibility');
            });
        }

        DB::table('reclassification_row_comments')
            ->whereNull('action_type')
            ->update(['action_type' => 'requires_action']);

        DB::table('reclassification_row_comments')
            ->where('visibility', 'internal')
            ->update(['action_type' => 'info']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('reclassification_row_comments')) {
            return;
        }

        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            Schema::table('reclassification_row_comments', function (Blueprint $table) {
                $table->dropColumn('action_type');
            });
        }
    }
};

