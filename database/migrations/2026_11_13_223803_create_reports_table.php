<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signalements de dépôts sauvages, avec la prise en charge par un camion.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->string('description', 500)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('status', 20)->default('nouveau')->index();
            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('cleanup_at')->nullable();
            $table->timestamp('cleaned_at')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
