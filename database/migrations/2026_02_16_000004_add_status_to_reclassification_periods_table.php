<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_periods', 'status')) {
                $table->enum('status', ['draft', 'active', 'ended'])
                    ->default('draft')
                    ->after('cycle_year');
                $table->index('status', 'rc_period_status_ix');
            }
        });

        if (Schema::hasColumn('reclassification_periods', 'status')) {
            DB::table('reclassification_periods')
                ->where('is_open', true)
                ->update(['status' => 'active']);

            DB::table('reclassification_periods')
                ->where('is_open', false)
                ->where('status', 'draft')
                ->update(['status' => 'ended']);
        }
    }

    public function down(): void
    {
        Schema::table('reclassification_periods', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_periods', 'status')) {
                $table->dropIndex('rc_period_status_ix');
                $table->dropColumn('status');
            }
        });
    }
};

