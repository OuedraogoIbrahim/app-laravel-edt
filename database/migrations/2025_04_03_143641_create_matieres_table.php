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
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('periode', ['semestre 1', 'semestre 2']);
            $table->string('description')->nullable();
            $table->integer('nombre_heures');
            $table->integer('heures_utilisees');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matieres');
    }
};
