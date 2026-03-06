<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_periods', 'cycle_year')) {
                $table->string('cycle_year', 20)->nullable()->after('name');
                $table->index('cycle_year', 'rc_period_cycle_year_ix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_periods', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_periods', 'cycle_year')) {
                $table->dropIndex('rc_period_cycle_year_ix');
                $table->dropColumn('cycle_year');
            }
        });
    }
};

