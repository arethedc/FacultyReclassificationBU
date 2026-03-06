<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->foreignId('rank_level_id')
                ->nullable()
                ->constrained('rank_levels')
                ->nullOnDelete()
                ->after('rank_step');
        });
    }

    public function down(): void
    {
        Schema::table('faculty_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rank_level_id');
        });
    }
};
