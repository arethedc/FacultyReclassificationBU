<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('reclassifications', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        $table->string('school_year');
        $table->string('current_rank');
        $table->string('desired_rank');

        $table->text('remarks')->nullable();

        $table->enum('status', [
            'draft',
            'submitted',
            'dean_review',
            'hr_review',
            'vpaa_review',
            'approved',
            'rejected'
        ])->default('draft');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclassifications');
    }
};
