<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_open')->default(false);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_periods');
    }
};
