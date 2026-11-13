<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Calendrier de passage du camion, points de collecte de sa tournée
     * et points validés par l'agent chaque jour.
     */
    public function up(): void
    {
        Schema::create('truck_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // ISO : 1 = lundi … 7 = dimanche
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('label')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::create('route_stop_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_stop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('visit_date');
            $table->timestamps();

            $table->unique(['route_stop_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stop_visits');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('truck_schedules');
    }
};
