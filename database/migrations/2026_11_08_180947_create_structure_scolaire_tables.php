<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('est_active')->default(false);
            $table->timestamps();
            $table->unique(['etablissement_id', 'libelle']);
        });

        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->cascadeOnDelete();
            $table->string('nom');
            $table->unsignedTinyInteger('ordre');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();
            $table->unique(['annee_scolaire_id', 'ordre']);
        });

        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('cycle', 20)->default('college');
            $table->unsignedTinyInteger('ordre')->default(1);
            $table->timestamps();
            $table->unique(['etablissement_id', 'nom']);
            
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained('niveaux')->restrictOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires')->restrictOnDelete();
            $table->string('nom');
            $table->unsignedSmallInteger('effectif_max')->nullable();
            $table->timestamps();
            $table->unique(['annee_scolaire_id', 'nom']);
        });

        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('code', 10);
            $table->timestamps();
            $table->unique(['etablissement_id', 'code']);
        });

        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('matieres')->restrictOnDelete();
            $table->foreignId('enseignant_id')->constrained('users')->restrictOnDelete();
            $table->decimal('coefficient', 4, 2)->default(1);
            $table->timestamps();
            $table->unique(['classe_id', 'matiere_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('matieres');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('niveaux');
        Schema::dropIfExists('periodes');
        Schema::dropIfExists('annees_scolaires');
    }
};