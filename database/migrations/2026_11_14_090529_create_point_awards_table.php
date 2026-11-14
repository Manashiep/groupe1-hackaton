<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carnet de points : une ligne par point gagné (qui, pourquoi, combien, pour quel dépôt ou ramassage).
     * Le classement fait la somme des lignes du mois.
     */
    public function up(): void
    {
        Schema::create('point_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 20);
            $table->unsignedSmallInteger('points');
            // Le dépôt (Report) ou le ramassage (PickupRequest) qui a rapporté les points.
            $table->morphs('awardable');
            $table->timestamps();

            // Jamais deux fois les mêmes points pour le même dépôt : double clic ou bug, la base refuse.
            $table->unique(['user_id', 'reason', 'awardable_type', 'awardable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_awards');
    }
};
