<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute aux utilisateurs leur thème (App\Enums\ColorScheme), « system » par défaut : la version
 * précédente, qui ignore la colonne, reste compatible.
 */
return new class extends Migration
{
    /**
     * Ajoute la colonne color_scheme.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('color_scheme', length: 6)->default('system')->after('is_admin');
        });
    }

    /**
     * Retire la colonne color_scheme.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('color_scheme');
        });
    }
};
