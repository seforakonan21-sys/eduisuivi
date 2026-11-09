<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Barème du niveau : 10 (par défaut au primaire) ou 20
        Schema::table('niveaux', function (Blueprint $table) {
            $table->unsignedTinyInteger('bareme')->default(20)->after('cycle');
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affectation_id')->constrained('affectations')->restrictOnDelete();
            $table->foreignId('periode_id')->constrained('periodes')->restrictOnDelete();
            $table->string('titre', 120);
            $table->string('type', 20)->default('devoir');
            $table->unsignedTinyInteger('note_max');
            $table->date('date_evaluation');
            $table->string('statut', 20)->default('brouillon');
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->restrictOnDelete();
            $table->decimal('valeur', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'eleve_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('evaluations');
        Schema::table('niveaux', function (Blueprint $table) {
            $table->dropColumn('bareme');
        });
    }
};