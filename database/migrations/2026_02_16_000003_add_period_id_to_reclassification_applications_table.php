<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_applications', 'period_id')) {
                $table->unsignedBigInteger('period_id')->nullable()->after('faculty_user_id');
                $table->index('period_id', 'rc_app_period_ix');
                $table->foreign('period_id', 'rc_app_period_fk')
                    ->references('id')
                    ->on('reclassification_periods')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasTable('reclassification_periods')) {
            $periodsByCycle = DB::table('reclassification_periods')
                ->whereNotNull('cycle_year')
                ->orderByDesc('is_open')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('cycle_year');

            foreach ($periodsByCycle as $cycleYear => $periods) {
                $period = $periods->first();
                if (!$period) {
                    continue;
                }

                DB::table('reclassification_applications')
                    ->whereNull('period_id')
                    ->where('cycle_year', $cycleYear)
                    ->update(['period_id' => $period->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_applications', 'period_id')) {
                $table->dropForeign('rc_app_period_fk');
                $table->dropIndex('rc_app_period_ix');
                $table->dropColumn('period_id');
            }
        });
    }
};

