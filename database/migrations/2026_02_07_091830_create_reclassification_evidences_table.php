<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_evidences', function (Blueprint $table) {
            $table->id();

            // ===== Application FK (short name) =====
            $table->unsignedBigInteger('reclassification_application_id');
            $table->foreign('reclassification_application_id', 'rc_ev_app_fk')
                ->references('id')
                ->on('reclassification_applications')
                ->cascadeOnDelete();

            // ===== Optional Section FK (short name) =====
            $table->unsignedBigInteger('reclassification_section_id')->nullable();
            $table->foreign('reclassification_section_id', 'rc_ev_sec_fk')
                ->references('id')
                ->on('reclassification_sections')
                ->nullOnDelete();

            // ===== Optional Entry FK (short name) =====
            $table->unsignedBigInteger('reclassification_section_entry_id')->nullable();
            $table->foreign('reclassification_section_entry_id', 'rc_ev_ent_fk')
                ->references('id')
                ->on('reclassification_section_entries')
                ->nullOnDelete();

            // ===== Uploaded By FK (short name) =====
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->foreign('uploaded_by_user_id', 'rc_ev_upl_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // ===== File metadata =====
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->string('label')->nullable(); // e.g. "Section III - Proof"

            $table->timestamps();

            // ✅ short index name
            $table->index('reclassification_application_id', 'rc_ev_app_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_evidences');
    }
};
