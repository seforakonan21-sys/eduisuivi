<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->foreignId('etablissement_id')->nullable()->after('id')
              ->constrained('etablissements')->nullOnDelete();
            $table->string('role')->default('parent')->after('email');
            $table->string('telephone')->nullable()->after('role');
            $table->boolean('actif')->default(true)->after('telephone');
            $table->timestamp('consentement_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropConstrainedForeignId('etablissement_id');
            $table->dropColumn(['role', 'telephone', 'actif', 'consentement_at']);

        });
    }
};
