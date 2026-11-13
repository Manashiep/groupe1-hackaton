<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Villages (ou quartiers) et camions affectés par la mairie.
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
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->string('color', 7)->default('#0f766e');
            $table->timestamps();
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
        Schema::dropIfExists('trucks');
        Schema::dropIfExists('villages');
    }
};
