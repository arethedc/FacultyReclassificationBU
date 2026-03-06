<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reclassification_row_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_row_comments', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('reclassification_row_comments', 'status')) {
                $table->string('status', 20)->default('open')->after('visibility');
            }
            if (!Schema::hasColumn('reclassification_row_comments', 'resolved_by_user_id')) {
                $table->unsignedBigInteger('resolved_by_user_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reclassification_row_comments', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by_user_id');
            }
        });

        Schema::table('reclassification_row_comments', function (Blueprint $table) {
            $table->foreign('parent_id', 'rc_rowc_parent_fk')
                ->references('id')
                ->on('reclassification_row_comments')
                ->nullOnDelete();

            $table->foreign('resolved_by_user_id', 'rc_rowc_resolver_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['parent_id'], 'rc_rowc_parent_ix');
            $table->index(['status'], 'rc_rowc_status_ix');
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_row_comments', function (Blueprint $table) {
            $table->dropIndex('rc_rowc_parent_ix');
            $table->dropIndex('rc_rowc_status_ix');

            $table->dropForeign('rc_rowc_parent_fk');
            $table->dropForeign('rc_rowc_resolver_fk');

            $table->dropColumn([
                'parent_id',
                'status',
                'resolved_by_user_id',
                'resolved_at',
            ]);
        });
    }
};
