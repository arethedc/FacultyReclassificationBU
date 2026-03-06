<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_sections', function (Blueprint $table) {
            $table->id();

       $table->unsignedBigInteger('reclassification_application_id');

$table->foreign('reclassification_application_id', 'rc_sec_app_fk')
    ->references('id')
    ->on('reclassification_applications')
    ->cascadeOnDelete();

            $table->string('section_code', 10); // I, II, III, IV, V
            $table->string('title')->nullable();

            $table->boolean('is_complete')->default(false);
            $table->decimal('points_total', 8, 2)->default(0);

            $table->timestamps();

$table->unique(
    ['reclassification_application_id', 'section_code'],
    'rc_sec_app_code_uq'
);            $table->index(['section_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_sections');
    }
};
