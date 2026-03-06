<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_row_comments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reclassification_application_id');
            $table->foreign('reclassification_application_id', 'rc_rowc_app_fk')
                ->references('id')
                ->on('reclassification_applications')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('reclassification_section_entry_id');
            $table->foreign('reclassification_section_entry_id', 'rc_rowc_entry_fk')
                ->references('id')
                ->on('reclassification_section_entries')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'rc_rowc_usr_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->text('body');
            $table->string('visibility', 20)->default('faculty_visible'); // faculty_visible | internal

            $table->timestamps();

            $table->index(['reclassification_section_entry_id'], 'rc_rowc_entry_ix');
            $table->index(['reclassification_application_id', 'visibility'], 'rc_rowc_app_vis_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_row_comments');
    }
};
