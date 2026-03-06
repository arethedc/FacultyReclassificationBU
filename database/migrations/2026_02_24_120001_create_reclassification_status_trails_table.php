<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_status_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclassification_application_id')
                ->constrained('reclassification_applications')
                ->cascadeOnDelete();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_role', 50)->nullable();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('from_step', 50)->nullable();
            $table->string('to_step', 50)->nullable();
            $table->string('action', 80)->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_status_trails');
    }
};

