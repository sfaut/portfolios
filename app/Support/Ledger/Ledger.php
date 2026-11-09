<?php

namespace App\Support\Ledger;

use App\Models\Operation;
use App\Models\Portfolio;
use App\Support\DateRestriction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Grand livre d'un portfolio : toutes les opérations de ses comptes, archivés compris, filtrées et
 * triées (LedgerFilters).
 *
 * Les opérations se lisent par le modèle Operation, dont la portée globale garde le cloisonnement
 * entre utilisateurs. La classe donne aussi ce que les filtres proposent : comptes, axes analytiques
 * et bornes des dates, tirés des données du portfolio.
 *
 * Tri : chaque colonne se départage par l'identifiant de l'opération, dans le même sens, pour un
 * ordre stable d'une page à l'autre. Le compte se trie par son nom, les axes par le nom du premier
 * axe de l'opération dans l'ordre alphabétique, les opérations non réparties en dernier.
 */
class Ledger
{
    /**
     * Nombre d'opérations par page.
     */
    public const int PER_PAGE = 50;

    /**
     * Options du filtre des comptes, calculées une fois.
     *
     * @var list<array{value: string, label: string, hint?: string}>|null
     */
    private ?array $accountOptions = null;

    /**
     * Options du filtre des axes, calculées une fois.
     *
     * @var list<array{value: string, label: string, hint?: string}>|null
     */
    private ?array $axisOptions = null;

    /**
     * Premier et dernier jours d'écriture et dates effectives des opérations, calculés une fois.
     *
     * @var array{written_min: string, written_max: string, effective_min: string, effective_max: string}|null
     */
    private ?array $bounds = null;

    /**
     * @param  Portfolio  $portfolio  Portfolio dont on lit le grand livre.
     */
    public function __construct(
        private readonly Portfolio $portfolio,
    ) {
        //
    }

    /**
     * Comptes du portfolio proposés au filtre, par nom, les archivés signalés.
     *
     * @return list<array{value: string, label: string, hint?: string}>
     */
    public function accountOptions(): array
    {
        return $this->accountOptions ??= $this->portfolio->accounts()->withTrashed()->toBase()
            ->get(['accounts.id', 'accounts.name', 'accounts.deleted_at'])
            ->map(fn (object $account): array => [
                'value' => (string) $account->id,
                'label' => $account->name,
                ...($account->deleted_at === null ? [] : ['hint' => 'archivé']),
            ])
            ->all();
    }

    /**
     * Axes analytiques proposés au filtre : « Non réparti », puis les axes du portfolio par nom, un
     * axe archivé n'étant proposé que si une opération l'utilise.
     *
     * @return list<array{value: string, label: string, hint?: string}>
     */
    public function axisOptions(): array
    {
        return $this->axisOptions ??= [
            ['value' => LedgerFilters::NO_AXIS, 'label' => 'Non réparti'],
            ...$this->portfolio->analyticAxes()->withTrashed()
                ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereHas('analyticLines'))
                ->toBase()
                ->get(['analytic_axes.id', 'analytic_axes.name', 'analytic_axes.deleted_at'])
                ->map(fn (object $axis): array => [
                    'value' => (string) $axis->id,
                    'label' => $axis->name,
                    ...($axis->deleted_at === null ? [] : ['hint' => 'archivé']),
                ])
                ->all(),
        ];
    }

    /**
     * Restriction des jours d'écriture : du premier au dernier jour où une opération a été écrite ;
     * libre sans opération.
     */
    public function writtenRestriction(): DateRestriction
    {
        $bounds = $this->bounds();

        return new DateRestriction(min: $bounds['written_min'], max: $bounds['written_max']);
    }

    /**
     * Restriction des dates effectives : de la première à la dernière date effective des
     * opérations ; libre sans opération.
     */
    public function effectiveRestriction(): DateRestriction
    {
        $bounds = $this->bounds();

        return new DateRestriction(min: $bounds['effective_min'], max: $bounds['effective_max']);
    }

    /**
     * Nombre d'opérations du portfolio, sans filtre.
     */
    public function count(): int
    {
        return $this->operations()->count();
    }

    /**
     * Page d'opérations filtrées et triées, dont les liens gardent les paramètres de l'adresse.
     *
     * @param  LedgerFilters  $filters  Filtres et tri.
     * @return LengthAwarePaginator<int, Operation>
     */
    public function paginate(LedgerFilters $filters): LengthAwarePaginator
    {
        return $this->sorted($filters)->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Toutes les opérations filtrées et triées.
     *
     * @param  LedgerFilters  $filters  Filtres et tri.
     * @return Collection<int, Operation>
     */
    public function all(LedgerFilters $filters): Collection
    {
        return $this->sorted($filters)->get();
    }

    /**
     * Opérations filtrées et triées, lues par lots, pour l'export.
     *
     * @param  LedgerFilters  $filters  Filtres et tri.
     * @return LazyCollection<int, Operation>
     */
    public function rows(LedgerFilters $filters): LazyCollection
    {
        return $this->sorted($filters)->lazy(500);
    }

    /**
     * Totaux des opérations filtrées : leur nombre, les ventes (montants négatifs, en valeur
     * absolue), les paiements (montants positifs) et le solde (somme des montants).
     *
     * @param  LedgerFilters  $filters  Filtres.
     * @return array{count: int, sales: string, payments: string, balance: string}
     */
    public function totals(LedgerFilters $filters): array
    {
        $totals = $this->filtered($filters)->toBase()
            ->selectRaw('COUNT(*) AS operation_count')
            ->selectRaw('COALESCE(-SUM(CASE WHEN operations.amount < 0 THEN operations.amount END), 0) AS sales')
            ->selectRaw('COALESCE(SUM(CASE WHEN operations.amount > 0 THEN operations.amount END), 0) AS payments')
            ->selectRaw('COALESCE(SUM(operations.amount), 0) AS balance')
            ->first();

        return [
            'count' => (int) $totals->operation_count,
            'sales' => (string) $totals->sales,
            'payments' => (string) $totals->payments,
            'balance' => (string) $totals->balance,
        ];
    }

    /**
     * Opérations des comptes du portfolio, archivés compris.
     *
     * @return Builder<Operation>
     */
    private function operations(): Builder
    {
        return Operation::query()
            ->whereIn('operations.account_id', $this->portfolio->accounts()->withTrashed()->reorder()->select('id'));
    }

    /**
     * Premier et dernier jours d'écriture et dates effectives des opérations, '' sans opération.
     *
     * @return array{written_min: string, written_max: string, effective_min: string, effective_max: string}
     */
    private function bounds(): array
    {
        if ($this->bounds === null) {
            $bounds = $this->operations()->toBase()
                ->selectRaw('DATE(MIN(operations.created_at)) AS written_min, DATE(MAX(operations.created_at)) AS written_max')
                ->selectRaw('MIN(operations.delivery_at) AS effective_min, MAX(operations.delivery_at) AS effective_max')
                ->first();

            $this->bounds = array_map(fn (?string $day): string => $day ?? '', (array) $bounds);
        }

        return $this->bounds;
    }

    /**
     * Opérations que les filtres gardent.
     *
     * @param  LedgerFilters  $filters  Filtres.
     * @return Builder<Operation>
     */
    private function filtered(LedgerFilters $filters): Builder
    {
        $axis_ids = array_values(array_diff($filters->axes, [LedgerFilters::NO_AXIS]));
        $escaped_label = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters->label);

        return $this->operations()
            ->when($filters->accounts !== [], fn (Builder $query) => $query->whereIn('operations.account_id', $filters->accounts))
            ->when($filters->label !== '', fn (Builder $query) => $query->whereLike('operations.label', "%{$escaped_label}%"))
            ->when($filters->effective['start'] !== '', fn (Builder $query) => $query->where('operations.delivery_at', '>=', $filters->effective['start']))
            ->when($filters->effective['end'] !== '', fn (Builder $query) => $query->where('operations.delivery_at', '<=', $filters->effective['end']))
            ->when($filters->written['start'] !== '', fn (Builder $query) => $query->where('operations.created_at', '>=', "{$filters->written['start']} 00:00:00"))
            ->when($filters->written['end'] !== '', fn (Builder $query) => $query->where(
                'operations.created_at',
                '<',
                CarbonImmutable::createFromFormat('!Y-m-d', $filters->written['end'])->addDay()->format('Y-m-d H:i:s'),
            ))
            ->when($filters->flow === 'sales', fn (Builder $query) => $query->where('operations.amount', '<', 0))
            ->when($filters->flow === 'payments', fn (Builder $query) => $query->where('operations.amount', '>', 0))
            ->when($filters->amountMin !== '', fn (Builder $query) => $query->whereRaw('ABS(operations.amount) >= ?', [$filters->amountMin]))
            ->when($filters->amountMax !== '', fn (Builder $query) => $query->whereRaw('ABS(operations.amount) <= ?', [$filters->amountMax]))
            ->when($filters->axes !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($filters, $axis_ids): void {
                // Les identifiants viennent des axes du portfolio (LedgerFilters) : les tables se lisent
                // sans les portées des modèles.
                if ($axis_ids !== []) {
                    $query->whereIn('operations.id', DB::table('analytic_lines')->select('operation_id')->whereIn('analytic_axis_id', $axis_ids));
                }

                if (in_array(LedgerFilters::NO_AXIS, $filters->axes, strict: true)) {
                    $query->orWhereNotIn('operations.id', DB::table('analytic_lines')->select('operation_id'));
                }
            }));
    }

    /**
     * Opérations filtrées, triées, avec leurs lignes analytiques.
     *
     * @param  LedgerFilters  $filters  Filtres et tri.
     * @return Builder<Operation>
     */
    private function sorted(LedgerFilters $filters): Builder
    {
        $query = $this->filtered($filters)->with('analyticLines.analyticAxis');
        $direction = $filters->direction;

        match ($filters->sort) {
            'account' => $query->orderBy(
                DB::table('accounts')->select('name')->whereColumn('accounts.id', 'operations.account_id'),
                $direction,
            ),
            'id' => null,
            'effective' => $query->orderBy('operations.delivery_at', $direction),
            'label' => $query->orderBy('operations.label', $direction),
            'amount' => $query->orderBy('operations.amount', $direction),
            'axes' => $this->orderByFirstAxis($query, $direction),
            default => $query->orderBy('operations.created_at', $direction),
        };

        return $query->orderBy('operations.id', $direction);
    }

    /**
     * Trie par le nom du premier axe de chaque opération, dans l'ordre alphabétique, les opérations
     * non réparties en dernier quel que soit le sens.
     *
     * @param  Builder<Operation>  $query  Requête à trier.
     * @param  string  $direction  Sens du tri : asc ou desc.
     */
    private function orderByFirstAxis(Builder $query, string $direction): void
    {
        $first_axis = fn () => DB::table('analytic_lines')
            ->join('analytic_axes', 'analytic_axes.id', '=', 'analytic_lines.analytic_axis_id')
            ->whereColumn('analytic_lines.operation_id', 'operations.id')
            ->selectRaw('MIN(analytic_axes.name)');

        $query->orderByRaw("({$first_axis()->toSql()}) IS NULL")->orderBy($first_axis(), $direction);
    }
}
