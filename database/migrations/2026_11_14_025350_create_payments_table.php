<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tentatives de paiement, côté EcoCom. Un dû peut avoir plusieurs tentatives
     * (ex. « solde insuffisant » puis réussite) : c'est pour ça que ce n'est pas le dû lui-même.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('due_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20)->default('simupay');
            $table->string('provider_id', 40)->nullable()->unique(); // « sp_… » renvoyé par SimuPay
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending')->index();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
