<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reprend la description du compte dans la vue account_reports, pour la carte d'un compte.
     */
    public function up(): void
    {
        $this->createAccountReports(withDescription: true);
    }

    /**
     * Rend à la vue account_reports sa définition sans description.
     */
    public function down(): void
    {
        $this->createAccountReports(withDescription: false);
    }

    /**
     * Crée ou remplace la vue account_reports : les comptes, avec leur nombre d'opérations, leurs
     * première et dernière dates effectives et leur solde.
     *
     * @param  bool  $withDescription  Si la vue reprend la description du compte.
     */
    private function createAccountReports(bool $withDescription): void
    {
        $description = $withDescription ? 'a.description,' : '';

        DB::statement(
            <<<SQL
            CREATE OR REPLACE VIEW account_reports AS
            SELECT ALL
                a.id, a.name,
                {$description}
                a.portfolio_id,
                a.deleted_at,
                COUNT(o.id) AS operation_count,
                MIN(o.delivery_at) AS operation_first_at,
                MAX(o.delivery_at) AS operation_last_at,
                COALESCE(SUM(o.amount), 0) AS operation_balance
            FROM accounts AS a
            LEFT OUTER JOIN operations AS o ON a.id = o.account_id
            GROUP BY 1, 2
            SQL
        );
    }
};
