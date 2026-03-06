<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclassification_events', function (Blueprint $table) {
            $table->id();

            // ✅ short FK names
            $table->unsignedBigInteger('reclassification_application_id');
            $table->foreign('reclassification_application_id', 'rc_evt_app_fk')
                ->references('id')
                ->on('reclassification_applications')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('actor_user_id');
            $table->foreign('actor_user_id', 'rc_evt_actor_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->string('event_type', 50);
            // submitted | forwarded_to_dean | forwarded_to_hr | forwarded_to_vpaa | forwarded_to_president
            // returned_to_faculty | finalized

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            // ✅ short composite index name
            $table->index(['reclassification_application_id', 'event_type'], 'rc_evt_app_type_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassification_events');
    }
};
