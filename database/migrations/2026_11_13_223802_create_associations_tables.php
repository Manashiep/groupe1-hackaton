<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Associations de nettoyage : demandées par un citoyen, validées par la mairie,
     * avec leurs membres et leurs publications.
     */
    public function up(): void
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('en_attente')->index();
            $table->text('motivation')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->string('color', 7)->default('#1d6b47');
            $table->timestamps();
        });

        Schema::create('association_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('en_attente');
            $table->timestamps();

            $table->unique(['association_id', 'user_id']);
        });

        Schema::create('association_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('publication');
            $table->text('body');
            $table->string('media_path')->nullable();
            $table->string('media_type', 10)->nullable();
            $table->dateTime('event_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_posts');
        Schema::dropIfExists('association_user');
        Schema::dropIfExists('associations');
    }
};
