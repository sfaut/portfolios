<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des axes analytiques affectés aux comptes, pour les repérer : un compte a de 0 à
     * n axes de son portfolio.
     */
    public function up(): void
    {
        Schema::create('account_analytic_axis', function (Blueprint $table) {
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analytic_axis_id')->constrained()->cascadeOnDelete();
            $table->primary(['account_id', 'analytic_axis_id']);
        });
    }

    /**
     * Supprime la table des axes des comptes.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_analytic_axis');
    }
};
