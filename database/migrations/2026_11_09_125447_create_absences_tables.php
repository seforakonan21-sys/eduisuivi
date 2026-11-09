<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trace du fait que l'appel a bien été fait ce jour-là pour cette classe
        Schema::create('appels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->restrictOnDelete();
            $table->date('date_appel');
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['classe_id', 'date_appel']);
        });

        // Un élève absent ou en retard : une ligne par élève et par jour
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->restrictOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->restrictOnDelete();
            $table->date('date_absence');
            $table->string('type', 10)->default('absence');
            $table->unsignedSmallInteger('duree_minutes')->nullable();
            $table->boolean('justifiee')->default(false);
            $table->string('motif')->nullable();
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['eleve_id', 'date_absence']);
            $table->index(['classe_id', 'date_absence']);
        });

        // Table standard des notifications Laravel
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('absences');
        Schema::dropIfExists('appels');
    }
};