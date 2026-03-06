<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_comments', function (Blueprint $table) {
            $table->id();

            // ===== Application FK (short) =====
            $table->unsignedBigInteger('reclassification_application_id');
            $table->foreign('reclassification_application_id', 'rc_com_app_fk')
                ->references('id')
                ->on('reclassification_applications')
                ->cascadeOnDelete();

            // ===== Author FK (short) =====
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'rc_com_usr_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->text('body');

            $table->string('visibility', 20)->default('internal'); // internal | faculty_visible

            // ===== Resolver FK (short) =====
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->foreign('resolved_by_user_id', 'rc_com_res_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // ===== Short composite index =====
            $table->index(
                ['reclassification_application_id', 'visibility'],
                'rc_com_app_vis_ix'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_comments');
    }
};
