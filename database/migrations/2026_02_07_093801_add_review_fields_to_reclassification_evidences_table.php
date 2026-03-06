<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up(): void
{
    Schema::table('reclassification_evidences', function (Blueprint $table) {
        $table->string('status', 20)->default('pending')->after('path'); // pending|accepted|rejected

        $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('status');
        $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
        $table->text('review_note')->nullable()->after('reviewed_at');

        $table->foreign('reviewed_by_user_id', 'rc_ev_reviewed_by_fk')
            ->references('id')->on('users')
            ->nullOnDelete();
    });
}
public function down(): void
{
    Schema::table('reclassification_evidences', function (Blueprint $table) {
        $table->dropForeign('rc_ev_reviewed_by_fk');
        $table->dropColumn(['status', 'reviewed_by_user_id', 'reviewed_at', 'review_note']);
    });
}
};
