<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Signalements de déchets abandonnés.
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quartier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 30);
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->string('status', 20)->default('nouveau')->index();
            $table->boolean('points_awarded')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Historique de traitement d'un signalement (timeline visible par le citoyen).
        Schema::create('report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Demandes de collecte exceptionnelle (encombrants, restaurants, commerces…).
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quartier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_type', 20)->default('particulier');
            $table->string('waste_type', 20)->default('encombrants');
            $table->string('volume', 20)->default('moyen');
            $table->text('description');
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('status', 20)->default('en_attente')->index();
            $table->dateTime('appointment_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        // Interventions affectées aux équipes (nettoyage d'un signalement ou collecte exceptionnelle).
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pickup_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quartier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('priority', 10)->default('normale');
            $table->dateTime('scheduled_at')->index();
            $table->string('status', 20)->default('planifiee')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->string('completion_photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('pickup_requests');
        Schema::dropIfExists('report_events');
        Schema::dropIfExists('reports');
    }
};
