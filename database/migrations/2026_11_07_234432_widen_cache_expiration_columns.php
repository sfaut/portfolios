<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passe l'expiration du cache en entier 64 bits, comme le squelette de Laravel 13.
 *
 * Un élément mis en cache sans limite (`forever()`, dont le signal de `queue:restart`) expire dans dix
 * ans : à partir de 2028, ce délai dépasse la capacité d'un entier 32 bits.
 */
return new class extends Migration
{
    /**
     * Élargit les colonnes `expiration`.
     */
    public function up(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->bigInteger('expiration')->change();
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->bigInteger('expiration')->change();
        });
    }

    /**
     * Rétablit les colonnes `expiration` en entier 32 bits.
     */
    public function down(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->integer('expiration')->change();
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->integer('expiration')->change();
        });
    }
};
