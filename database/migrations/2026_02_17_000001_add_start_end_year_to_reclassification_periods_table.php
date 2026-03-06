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
            if (!Schema::hasColumn('reclassification_periods', 'start_year')) {
                $table->unsignedSmallInteger('start_year')->nullable()->after('cycle_year');
                $table->index('start_year', 'rc_period_start_year_ix');
            }

            if (!Schema::hasColumn('reclassification_periods', 'end_year')) {
                $table->unsignedSmallInteger('end_year')->nullable()->after('start_year');
                $table->index('end_year', 'rc_period_end_year_ix');
            }
        });

        if (!Schema::hasTable('reclassification_periods') || !Schema::hasColumn('reclassification_periods', 'cycle_year')) {
            return;
        }

        DB::table('reclassification_periods')
            ->select(['id', 'cycle_year'])
            ->orderBy('id')
            ->get()
            ->each(function ($period): void {
                $cycleYear = trim((string) ($period->cycle_year ?? ''));
                if (!preg_match('/^(\d{4})-(\d{4})$/', $cycleYear, $matches)) {
                    return;
                }

                DB::table('reclassification_periods')
                    ->where('id', $period->id)
                    ->update([
                        'start_year' => (int) $matches[1],
                        'end_year' => (int) $matches[2],
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('reclassification_periods', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_periods', 'end_year')) {
                $table->dropIndex('rc_period_end_year_ix');
                $table->dropColumn('end_year');
            }

            if (Schema::hasColumn('reclassification_periods', 'start_year')) {
                $table->dropIndex('rc_period_start_year_ix');
                $table->dropColumn('start_year');
            }
        });
    }
};
