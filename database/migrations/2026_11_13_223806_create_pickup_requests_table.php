<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Demandes de collecte (beaucoup de déchets, encombrants…), prises en charge
     * par un citoyen ou une association.
     */
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->date('preferred_date')->nullable();
            $table->string('status', 20)->default('en_attente')->index();
            $table->foreignId('taken_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('appointment_at')->nullable();
            $table->string('taker_note', 300)->nullable();
            $table->timestamp('done_at')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
