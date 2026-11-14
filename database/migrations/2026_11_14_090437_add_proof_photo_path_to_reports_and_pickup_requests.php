<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photo « après » : la preuve qu'un dépôt a été nettoyé ou qu'un ramassage a été fait.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('proof_photo_path')->nullable()->after('cleaned_at');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->string('proof_photo_path')->nullable()->after('done_at');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('proof_photo_path');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn('proof_photo_path');
        });
    }
};
