<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faculty_highest_degrees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique();

            $table->enum('highest_degree', ['bachelors', 'masters', 'doctorate']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_highest_degrees');
    }
};
