<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Village où habite le citoyen : c'est là qu'il paie la redevance quand le camion passe.
     * (Différent de village_id, qui mémorise seulement le dernier village consulté dans « Camion ».)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('home_village_id')->nullable()->after('village_id')->constrained('villages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('home_village_id');
        });
    }
};
