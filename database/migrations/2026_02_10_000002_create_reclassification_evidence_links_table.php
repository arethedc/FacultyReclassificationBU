<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_evidence_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reclassification_evidence_id');
            $table->unsignedBigInteger('reclassification_section_entry_id');
            $table->unsignedBigInteger('reclassification_section_id');
            $table->timestamps();

            $table->foreign('reclassification_evidence_id', 'rc_ev_link_ev_fk')
                ->references('id')
                ->on('reclassification_evidences')
                ->cascadeOnDelete();

            $table->foreign('reclassification_section_entry_id', 'rc_ev_link_entry_fk')
                ->references('id')
                ->on('reclassification_section_entries')
                ->cascadeOnDelete();

            $table->foreign('reclassification_section_id', 'rc_ev_link_sec_fk')
                ->references('id')
                ->on('reclassification_sections')
                ->cascadeOnDelete();

            $table->unique(['reclassification_evidence_id', 'reclassification_section_entry_id'], 'rc_ev_link_unique');
            $table->index('reclassification_section_entry_id', 'rc_ev_link_entry_ix');
            $table->index('reclassification_section_id', 'rc_ev_link_sec_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_evidence_links');
    }
};
