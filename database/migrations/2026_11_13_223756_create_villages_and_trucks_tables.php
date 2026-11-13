<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Villages, camions de la mairie (un camion peut desservir plusieurs villages) et profils.
     */
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ile')->default('Ngazidja');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });

        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plate')->nullable();
            $table->string('color', 7)->default('#1d6b47');
            $table->timestamps();
        });

        Schema::create('truck_village', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unique(['truck_id', 'village_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('role', 20)->default('citoyen')->index()->after('password');
            $table->foreignId('truck_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('truck_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('village_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_id');
            $table->dropConstrainedForeignId('village_id');
            $table->dropColumn(['phone', 'role', 'is_active']);
        });
        Schema::dropIfExists('truck_village');
        Schema::dropIfExists('trucks');
        Schema::dropIfExists('villages');
    }
};
