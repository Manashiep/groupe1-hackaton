<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Problèmes signalés par les habitants sur un camion (retard, comportement…).
     */
    public function up(): void
    {
        Schema::create('truck_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 20);
            $table->string('message', 500)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_complaints');
    }
};
