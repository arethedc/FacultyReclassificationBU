<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclassification_application_id')
                ->constrained('reclassification_applications')
                ->cascadeOnDelete();
            $table->foreignId('reclassification_section_id')
                ->nullable()
                ->constrained('reclassification_sections')
                ->nullOnDelete();
            $table->foreignId('reclassification_section_entry_id')
                ->nullable()
                ->constrained('reclassification_section_entries')
                ->nullOnDelete();
            $table->string('section_code', 10)->nullable();
            $table->string('criterion_key', 80)->nullable();
            $table->string('change_type', 32);
            $table->string('summary')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['reclassification_application_id', 'created_at'], 'rc_change_logs_app_created_ix');
            $table->index(['section_code', 'criterion_key'], 'rc_change_logs_section_criterion_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_change_logs');
    }
};

