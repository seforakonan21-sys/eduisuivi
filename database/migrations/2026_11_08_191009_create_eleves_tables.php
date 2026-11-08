<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('matricule', 20);
            $table->string('nom');
            $table->string('prenoms');
            $table->date('date_naissance');
            $table->string('sexe', 1);
            $table->string('photo')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('adresse')->nullable();
            $table->timestamps();
            $table->unique(['etablissement_id', 'matricule']);
        });

        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->restrictOnDelete();
            $table->string('statut', 20)->default('inscrit');
            $table->date('date_inscription');
            $table->timestamps();
            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });

        Schema::create('eleve_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->string('lien', 20)->default('tuteur');
            $table->timestamps();
            $table->unique(['eleve_id', 'parent_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('doit_changer_mot_de_passe')->default(false)->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('doit_changer_mot_de_passe');
        });
        Schema::dropIfExists('eleve_parent');
        Schema::dropIfExists('inscriptions');
        Schema::dropIfExists('eleves');
    }
};