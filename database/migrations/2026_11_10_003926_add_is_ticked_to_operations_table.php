<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le pointage des opérations, une coche dont l'utilisateur choisit le sens, et le reprend
     * dans la vue operation_reports.
     */
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_ticked')->default(0)->after('amount');
        });

        $this->createOperationReports(withTick: true);
    }

    /**
     * Rend à la vue operation_reports sa définition sans pointage, puis retire la colonne.
     */
    public function down(): void
    {
        $this->createOperationReports(withTick: false);

        Schema::table('operations', function (Blueprint $table) {
            $table->dropColumn('is_ticked');
        });
    }

    /**
     * Crée ou remplace la vue operation_reports : les opérations et leur cumul par compte, ordonné
     * par date effective puis identifiant.
     *
     * @param  bool  $withTick  Si la vue reprend le pointage.
     */
    private function createOperationReports(bool $withTick): void
    {
        $tick = $withTick ? 'is_ticked,' : '';

        DB::statement(
            <<<SQL
            CREATE OR REPLACE VIEW operation_reports AS
            WITH dataset AS (
                -- CTE pour calcul du cumul global avant filtrage
                SELECT ALL
                    id, label,
                    amount, {$tick} delivery_at,
                    account_id,
                    created_at, updated_at,
                    SUM(amount) OVER (
                        PARTITION BY account_id
                        ORDER BY delivery_at ASC, id ASC
                    ) AS amount_running_sum
                FROM operations
            )
            SELECT ALL *
            FROM dataset
            SQL
        );
    }
};
