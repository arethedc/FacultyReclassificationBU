<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            $table->string('current_rank_label_at_approval', 120)->nullable()->after('finalized_at');
            $table->string('approved_rank_label', 120)->nullable()->after('current_rank_label_at_approval');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->after('approved_rank_label')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn([
                'current_rank_label_at_approval',
                'approved_rank_label',
                'approved_at',
            ]);
        });
    }
};
