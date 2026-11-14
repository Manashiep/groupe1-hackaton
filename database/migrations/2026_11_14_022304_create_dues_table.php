<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Redevance d'un habitant pour un passage du camion dans son village.
     */
    public function up(): void
    {
        Schema::create('dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_passage_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');                      // copié au moment du passage
            $table->string('status', 20)->default('a_payer')->index();
            $table->string('receipt_code', 20)->nullable()->unique(); // preuve de paiement montrée à l'agent
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Un habitant ne doit qu'une redevance par passage.
            $table->unique(['user_id', 'truck_passage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues');
    }
};
