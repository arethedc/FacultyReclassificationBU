<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_section_entries', function (Blueprint $table) {
            $table->id();

            // ✅ use explicit FK with short constraint name (MySQL 64-char limit)
            $table->unsignedBigInteger('reclassification_section_id');

            $table->foreign('reclassification_section_id', 'rc_ent_sec_fk')
                ->references('id')
                ->on('reclassification_sections')
                ->cascadeOnDelete();

            $table->string('criterion_key', 80); // e.g. journal_article, book_authorship

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->string('evidence_note')->nullable();

            $table->decimal('points', 8, 2)->default(0);
            $table->boolean('is_validated')->default(false);

            $table->json('data')->nullable(); // flexible extra fields per criterion

            $table->timestamps();

            // ✅ short index name (optional but recommended)
            $table->index('criterion_key', 'rc_ent_crit_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_section_entries');
    }
};
