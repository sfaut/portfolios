<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée les axes analytiques d'un portfolio et les lignes qui répartissent une opération entre eux.
     */
    public function up(): void
    {
        Schema::create('analytic_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', length: 50);
            $table->string('description', length: 1000)->default('');
            $table->dateTime('created_at', precision: 6);
            $table->dateTime('updated_at', precision: 6);
            $table->dateTime('deleted_at', precision: 6)->nullable()->comment('Archivage (SoftDeletes)');
            $table->unique(['portfolio_id', 'name']);
        });

        Schema::create('analytic_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('analytic_axis_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('percentage', total: 5, places: 2)->comment('Part du montant de l\'opération, en %');
            $table->dateTime('created_at', precision: 6);
            $table->dateTime('updated_at', precision: 6);
            $table->unique(['operation_id', 'analytic_axis_id']);
        });
    }

    /**
     * Supprime les lignes, puis les axes analytiques.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytic_lines');
        Schema::dropIfExists('analytic_axes');
    }
};
