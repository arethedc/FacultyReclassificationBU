<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_applications', 'rejection_recommended_by_user_id')) {
                $table->unsignedBigInteger('rejection_recommended_by_user_id')
                    ->nullable()
                    ->after('approved_at');
                $table->foreign('rejection_recommended_by_user_id', 'rapp_rej_rec_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('reclassification_applications', 'rejection_recommendation_reason')) {
                $table->text('rejection_recommendation_reason')
                    ->nullable()
                    ->after('rejection_recommended_by_user_id');
            }
            if (!Schema::hasColumn('reclassification_applications', 'rejection_recommended_at')) {
                $table->timestamp('rejection_recommended_at')
                    ->nullable()
                    ->after('rejection_recommendation_reason');
            }
            if (!Schema::hasColumn('reclassification_applications', 'rejection_finalized_by_user_id')) {
                $table->unsignedBigInteger('rejection_finalized_by_user_id')
                    ->nullable()
                    ->after('rejection_recommended_at');
                $table->foreign('rejection_finalized_by_user_id', 'rapp_rej_fin_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('reclassification_applications', 'rejection_final_reason')) {
                $table->text('rejection_final_reason')
                    ->nullable()
                    ->after('rejection_finalized_by_user_id');
            }
            if (!Schema::hasColumn('reclassification_applications', 'rejection_finalized_at')) {
                $table->timestamp('rejection_finalized_at')
                    ->nullable()
                    ->after('rejection_final_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_applications', 'rejection_finalized_at')) {
                $table->dropColumn('rejection_finalized_at');
            }
            if (Schema::hasColumn('reclassification_applications', 'rejection_final_reason')) {
                $table->dropColumn('rejection_final_reason');
            }
            if (Schema::hasColumn('reclassification_applications', 'rejection_finalized_by_user_id')) {
                $table->dropForeign('rapp_rej_fin_by_fk');
                $table->dropColumn('rejection_finalized_by_user_id');
            }
            if (Schema::hasColumn('reclassification_applications', 'rejection_recommended_at')) {
                $table->dropColumn('rejection_recommended_at');
            }
            if (Schema::hasColumn('reclassification_applications', 'rejection_recommendation_reason')) {
                $table->dropColumn('rejection_recommendation_reason');
            }
            if (Schema::hasColumn('reclassification_applications', 'rejection_recommended_by_user_id')) {
                $table->dropForeign('rapp_rej_rec_by_fk');
                $table->dropColumn('rejection_recommended_by_user_id');
            }
        });
    }
};
