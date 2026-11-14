<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Passage confirmé par l'agent : « le camion 1 est passé à Badjanani le 14 novembre ».
     * C'est cet événement qui crée la redevance des habitants du village.
     */
    public function up(): void
    {
        Schema::create('truck_passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('passed_on');
            $table->timestamps();

            // Un seul passage par camion, village et jour : un double clic ne facture pas deux fois.
            $table->unique(['truck_id', 'village_id', 'passed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_passages');
    }
};
