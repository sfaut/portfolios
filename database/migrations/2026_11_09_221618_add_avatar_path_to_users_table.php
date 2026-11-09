<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute aux utilisateurs le chemin de leur photo sur le disque privé, '' sans photo : la version
 * précédente, qui ignore la colonne, reste compatible.
 */
return new class extends Migration
{
    /**
     * Ajoute la colonne avatar_path.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path', length: 100)->default('')->after('color_scheme');
        });
    }

    /**
     * Retire la colonne avatar_path.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
