<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faculty_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique();

            $table->string('employee_no')->unique();

            $table->enum('employment_type', ['full_time', 'part_time'])->default('full_time');
            $table->string('teaching_rank')->default('Instructor');
            $table->string('rank_step')->nullable(); // A/B/C
            $table->date('original_appointment_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_profiles');
    }
};
