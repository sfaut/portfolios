<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute à chaque axe analytique son icône Lucide (App\Support\AxisIcons) et sa couleur
     * (App\Enums\AxisColor), les axes existants prenant celles par défaut.
     */
    public function up(): void
    {
        Schema::table('analytic_axes', function (Blueprint $table) {
            $table->string('icon', length: 40)->default('tag')->after('name');
            $table->string('color', length: 20)->default('indigo')->after('icon');
        });
    }

    /**
     * Retire l'icône et la couleur des axes analytiques.
     */
    public function down(): void
    {
        Schema::table('analytic_axes', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color']);
        });
    }
};
