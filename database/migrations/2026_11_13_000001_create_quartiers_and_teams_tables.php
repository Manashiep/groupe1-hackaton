<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quartiers / villages couverts par le service de collecte.
        Schema::create('quartiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ville')->default('Moroni');
            $table->string('ile')->default('Ngazidja');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('population')->nullable();
            $table->timestamps();
        });

        // Équipes de collecte (une camionnette par équipe).
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vehicle_plate')->nullable();
            $table->string('color', 7)->default('#0f766e');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('citoyen')->index()->after('password');
            $table->string('phone', 30)->nullable()->after('email');
            $table->foreignId('quartier_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('quartier_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('points')->default(0)->after('team_id');
            $table->boolean('is_active')->default(true)->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quartier_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['role', 'phone', 'points', 'is_active']);
        });
        Schema::dropIfExists('teams');
        Schema::dropIfExists('quartiers');
    }
};
