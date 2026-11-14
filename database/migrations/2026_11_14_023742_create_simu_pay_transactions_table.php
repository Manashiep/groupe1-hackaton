<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transactions de SimuPay, la fausse passerelle de paiement.
     * Dans la vraie vie, cette table serait chez l'opérateur (sur ses serveurs), pas chez nous :
     * EcoCom ne la lit jamais directement, il passe toujours par l'API de SimuPay.
     */
    public function up(): void
    {
        Schema::create('simu_pay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 40)->unique();        // « sp_… » : l'identifiant communiqué au marchand
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('merchant_reference', 100);        // la référence du marchand (chez nous : le n° du dû)
            $table->string('description')->nullable();
            $table->string('callback_url');                   // où envoyer la confirmation (webhook)
            $table->string('return_url');                     // où renvoyer le client après paiement
            $table->string('status', 20)->default('pending'); // pending → succeeded ou failed
            $table->string('payer_phone', 30)->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedSmallInteger('webhook_status')->nullable(); // code HTTP renvoyé par le marchand
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simu_pay_transactions');
    }
};
