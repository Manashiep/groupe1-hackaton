<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Calendrier récurrent : « la camionnette passe le lundi à 8h à Badjanani ».
        Schema::create('collection_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quartier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // ISO : 1 = lundi … 7 = dimanche
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('waste_type', 20)->default('menagers');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Passages concrets générés à partir du calendrier.
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quartier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->date('scheduled_date')->index();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('waste_type', 20)->default('menagers');
            $table->string('status', 20)->default('programmee')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('agent_note')->nullable();
            $table->timestamps();

            $table->unique(['collection_schedule_id', 'scheduled_date']);
        });

        // « La camionnette n'est pas passée » signalé par un habitant.
        Schema::create('collection_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('comment', 500)->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_complaints');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('collection_schedules');
    }
};
