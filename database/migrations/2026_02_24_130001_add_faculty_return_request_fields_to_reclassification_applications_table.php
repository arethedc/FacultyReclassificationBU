<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_applications', 'faculty_return_requested_by_user_id')) {
                $table->foreignId('faculty_return_requested_by_user_id')
                    ->nullable()
                    ->after('returned_from')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('reclassification_applications', 'faculty_return_requested_at')) {
                $table->timestamp('faculty_return_requested_at')
                    ->nullable()
                    ->after('faculty_return_requested_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_at')) {
                $table->dropColumn('faculty_return_requested_at');
            }
            if (Schema::hasColumn('reclassification_applications', 'faculty_return_requested_by_user_id')) {
                $table->dropConstrainedForeignId('faculty_return_requested_by_user_id');
            }
        });
    }
};

