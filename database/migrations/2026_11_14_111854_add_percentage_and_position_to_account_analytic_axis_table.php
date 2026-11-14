<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fait des axes d'un compte sa répartition analytique par défaut, ordonnée : chaque axe reçoit un
     * pourcentage (total de 100 % par compte) et une position.
     *
     * Les comptes qui ont déjà des axes les gardent dans l'ordre où ils s'affichaient (par nom), à
     * parts égales en centièmes, le reste de la division allant au premier (33,34 / 33,33 / 33,33).
     * La version précédente reste compatible : ses affectations reçoivent les valeurs par défaut.
     */
    public function up(): void
    {
        Schema::table('account_analytic_axis', function (Blueprint $table) {
            $table->decimal('percentage', total: 5, places: 2)->default(0)->comment('Part par défaut du montant d\'une opération du compte, en %');
            $table->unsignedSmallInteger('position')->default(0)->comment('Ordre de l\'axe dans le compte, à partir de 0');
            $table->comment('Répartition analytique par défaut des comptes, ordonnée');
        });

        DB::table('account_analytic_axis')
            ->join('analytic_axes', 'analytic_axes.id', '=', 'account_analytic_axis.analytic_axis_id')
            ->orderBy('account_analytic_axis.account_id')
            ->orderBy('analytic_axes.name')
            ->get(['account_analytic_axis.account_id', 'account_analytic_axis.analytic_axis_id'])
            ->groupBy('account_id')
            ->each(function ($links): void {
                $share = intdiv(10000, $links->count());
                $remainder = 10000 - $share * $links->count();

                $links->values()->each(fn (object $link, int $position) => DB::table('account_analytic_axis')
                    ->where('account_id', $link->account_id)
                    ->where('analytic_axis_id', $link->analytic_axis_id)
                    ->update([
                        'percentage' => ($share + ($position === 0 ? $remainder : 0)) / 100,
                        'position' => $position,
                    ]));
            });
    }

    /**
     * Retire le pourcentage, la position et le commentaire de la table.
     */
    public function down(): void
    {
        Schema::table('account_analytic_axis', function (Blueprint $table) {
            $table->dropColumn(['percentage', 'position']);
            $table->comment('');
        });
    }
};
