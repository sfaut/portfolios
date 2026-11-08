<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tient pour vérifiées les adresses des utilisateurs déjà inscrits.
 *
 * La vérification d'adresse devient obligatoire pour les pages métier (middleware `verified`). Les
 * comptes créés avant elle, administrateur compris, n'ont jamais reçu de lien : sans cette migration,
 * ils seraient bloqués sur la page de vérification à leur prochaine connexion.
 */
return new class extends Migration
{
    /**
     * Marque comme vérifiées les adresses qui ne le sont pas encore.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('email_verified_at', null)
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Laisse les adresses vérifiées : rien ne distingue plus celles que la migration a marquées.
     */
    public function down(): void
    {
        //
    }
};
