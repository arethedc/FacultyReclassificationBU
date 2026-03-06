<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('faculty_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('cycle_year', 20)->nullable(); // e.g. 2025-2026

            $table->string('status', 30)->default('draft');
            // draft | dean_review | returned_to_faculty | hr_review | vpaa_review | president_review | finalized

            $table->string('current_step', 30)->default('faculty');
            // faculty | dean | hr | vpaa | president

            $table->string('returned_from', 30)->nullable(); // dean | hr | vpaa

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'current_step']);
            $table->index(['faculty_user_id', 'cycle_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_applications');
    }
};
