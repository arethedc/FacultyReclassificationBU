<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_move_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reclassification_application_id');
            $table->foreign('reclassification_application_id', 'rc_move_app_fk')
                ->references('id')
                ->on('reclassification_applications')
                ->cascadeOnDelete();

            $table->string('source_section_code', 10);
            $table->string('source_criterion_key', 80);
            $table->string('target_section_code', 10);
            $table->string('target_criterion_key', 80);

            $table->text('note')->nullable();

            $table->unsignedBigInteger('requested_by_user_id');
            $table->foreign('requested_by_user_id', 'rc_move_req_by_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->string('status', 20)->default('pending'); // pending | resolved
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->foreign('resolved_by_user_id', 'rc_move_res_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['reclassification_application_id', 'status'], 'rc_move_app_status_ix');
            $table->index(['source_section_code', 'source_criterion_key'], 'rc_move_source_ix');
            $table->index(['target_section_code', 'target_criterion_key'], 'rc_move_target_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_move_requests');
    }
};

