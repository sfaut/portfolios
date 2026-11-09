<?php

namespace App\Support;

use App\Models\Operation;
use App\Models\Portfolio;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ventes d'un portfolio, lues sur ses opérations.
 *
 * Convention de signe du métier : un montant négatif est une vente, un montant positif un paiement,
 * et un compte soldé à zéro a toutes ses ventes payées. Une vente se compte en valeur absolue.
 *
 * Les opérations des comptes archivés comptent : leurs ventes ont eu lieu. Les périodes portent sur la
 * date effective (delivery_at) et s'écrivent en intervalles semi-ouverts [début, fin[.
 */
final readonly class PortfolioSales
{
    /**
     * @param  Portfolio  $portfolio  Portfolio dont les ventes sont lues.
     */
    public function __construct(
        private Portfolio $portfolio,
    ) {
        //
    }

    /**
     * Opérations du portfolio, comptes archivés compris, entre deux dates.
     *
     * @param  CarbonInterface  $from  Premier jour compris.
     * @param  CarbonInterface  $until  Premier jour exclu.
     * @return Builder<Operation>
     */
    public function operations(CarbonInterface $from, CarbonInterface $until): Builder
    {
        return Operation::query()
            ->whereIn('operations.account_id', $this->portfolio->accounts()->withTrashed()->select('id'))
            ->where('operations.delivery_at', '>=', $from->toDateString())
            ->where('operations.delivery_at', '<', $until->toDateString());
    }

    /**
     * Total des ventes entre deux dates, en valeur absolue, ou null sans aucune vente.
     *
     * @param  CarbonInterface  $from  Premier jour compris.
     * @param  CarbonInterface  $until  Premier jour exclu.
     */
    public function total(CarbonInterface $from, CarbonInterface $until): ?float
    {
        $total = $this->operations($from, $until)
            ->where('operations.amount', '<', 0)
            ->toBase()
            ->selectRaw('-SUM(operations.amount) AS sales')
            ->value('sales');

        return $total === null ? null : (float) $total;
    }
}
