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
        Schema::create('edt_provisoires', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->enum('statut', ['en attente', 'terminer', 'annuler'])->default('en attente');
            $table->enum('type', ['cours', 'devoir', 'autre']);
            $table->enum('activite', ['active', 'inactive'])->default('inactive');

            $table->foreignId('salle_id')->constrained('salles')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date_creation')->default(now());

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edt_provisoires');
    }
};
