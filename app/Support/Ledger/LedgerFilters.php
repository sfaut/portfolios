<?php

namespace App\Support\Ledger;

use App\Support\DateRestriction;
use Illuminate\Http\Request;

/**
 * Filtres et tri du grand livre d'un portfolio, lus dans les paramètres de la requête.
 *
 * La lecture est tolérante, comme pour les filtres des comptes : une valeur refusée (compte ou axe
 * d'un autre portfolio, date hors des bornes, tri inconnu, tableau à la place d'un texte…) est
 * ignorée, sans erreur. Les valeurs gardées sont donc sûres pour la requête SQL comme pour
 * l'affichage.
 *
 * Paramètres :
 * - accounts[] : comptes du portfolio, archivés compris ;
 * - written[start], written[end] : jours d'écriture (created_at), bornes comprises ;
 * - effective[start], effective[end] : dates effectives (delivery_at), bornes comprises ;
 * - label : texte cherché dans le libellé ;
 * - axes[] : axes analytiques du portfolio, ou NO_AXIS pour les opérations non réparties ; une
 *   opération est gardée si elle répond à au moins une des valeurs ;
 * - flow : sales (montants négatifs) ou payments (montants positifs) ;
 * - amount[min], amount[max] : bornes de la valeur absolue du montant ;
 * - sort, dir : colonne de tri (SORTS) et sens (asc, desc).
 *
 * Une plage de dates sans fin reste ouverte : toutes les opérations depuis son début.
 */
final readonly class LedgerFilters
{
    /**
     * Colonnes triables, la première par défaut.
     *
     * @var list<string>
     */
    public const array SORTS = ['written', 'id', 'account', 'effective', 'label', 'amount', 'axes'];

    /**
     * Sens du tri par défaut : les dernières écritures en tête.
     */
    public const string DEFAULT_DIRECTION = 'desc';

    /**
     * Valeur du filtre des axes qui garde les opérations sans ligne analytique.
     */
    public const string NO_AXIS = 'none';

    /**
     * Sens des montants : ventes (négatifs) et paiements (positifs).
     *
     * @var list<string>
     */
    public const array FLOWS = ['sales', 'payments'];

    /**
     * Longueur maximale du texte cherché, celle de la colonne operations.label.
     */
    public const int LABEL_LENGTH = 150;

    /**
     * @param  list<string>  $accounts  Identifiants des comptes gardés, vide pour tous.
     * @param  array{start: string, end: string}  $written  Jours d'écriture, '' sans borne.
     * @param  array{start: string, end: string}  $effective  Dates effectives, '' sans borne.
     * @param  string  $label  Texte cherché dans le libellé, vide sans recherche.
     * @param  list<string>  $axes  Identifiants des axes gardés et NO_AXIS, vide pour tous.
     * @param  string  $flow  Sens des montants (FLOWS), vide pour tous.
     * @param  string  $amountMin  Valeur absolue minimale du montant, vide sans borne.
     * @param  string  $amountMax  Valeur absolue maximale du montant, vide sans borne.
     * @param  string  $sort  Colonne de tri (SORTS).
     * @param  string  $direction  Sens du tri : asc ou desc.
     */
    public function __construct(
        public array $accounts = [],
        public array $written = ['start' => '', 'end' => ''],
        public array $effective = ['start' => '', 'end' => ''],
        public string $label = '',
        public array $axes = [],
        public string $flow = '',
        public string $amountMin = '',
        public string $amountMax = '',
        public string $sort = self::SORTS[0],
        public string $direction = self::DEFAULT_DIRECTION,
    ) {
        //
    }

    /**
     * Filtres d'une requête, réduits aux valeurs que le grand livre accepte.
     *
     * @param  Request  $request  Requête de la page ou de l'export.
     * @param  Ledger  $ledger  Grand livre, qui donne les comptes, les axes et les bornes de dates admis.
     */
    public static function fromRequest(Request $request, Ledger $ledger): self
    {
        $label = $request->query('label');
        $flow = $request->query('flow');
        $amount = $request->query('amount');
        $sort = $request->query('sort');
        $direction = $request->query('dir');

        return new self(
            accounts: self::among($request->query('accounts'), array_column($ledger->accountOptions(), 'value')),
            written: self::range($request->query('written'), $ledger->writtenRestriction()),
            effective: self::range($request->query('effective'), $ledger->effectiveRestriction()),
            label: is_string($label) ? mb_substr(trim($label), 0, self::LABEL_LENGTH) : '',
            axes: self::among($request->query('axes'), array_column($ledger->axisOptions(), 'value')),
            flow: in_array($flow, self::FLOWS, strict: true) ? $flow : '',
            amountMin: self::amount(is_array($amount) ? ($amount['min'] ?? null) : null),
            amountMax: self::amount(is_array($amount) ? ($amount['max'] ?? null) : null),
            sort: in_array($sort, self::SORTS, strict: true) ? $sort : self::SORTS[0],
            direction: in_array($direction, ['asc', 'desc'], strict: true) ? $direction : self::DEFAULT_DIRECTION,
        );
    }

    /**
     * Si au moins un filtre restreint les opérations (le tri n'en est pas un).
     */
    public function isFiltered(): bool
    {
        return $this->filterQuery() !== [];
    }

    /**
     * Si le tri est celui par défaut.
     */
    public function isDefaultSort(): bool
    {
        return $this->sort === self::SORTS[0] && $this->direction === self::DEFAULT_DIRECTION;
    }

    /**
     * Paramètres d'adresse des filtres en cours, sans le tri ni les filtres vides.
     *
     * @return array<string, string|list<string>|array<string, string>>
     */
    public function filterQuery(): array
    {
        return array_filter([
            'label' => $this->label,
            'accounts' => $this->accounts,
            'axes' => $this->axes,
            'effective' => array_filter($this->effective, fn (string $day): bool => $day !== ''),
            'written' => array_filter($this->written, fn (string $day): bool => $day !== ''),
            'flow' => $this->flow,
            'amount' => array_filter(['min' => $this->amountMin, 'max' => $this->amountMax], fn (string $bound): bool => $bound !== ''),
        ], fn (string|array $value): bool => $value !== '' && $value !== []);
    }

    /**
     * Paramètres d'adresse des filtres et du tri en cours, sans le tri par défaut.
     *
     * @return array<string, string|list<string>|array<string, string>>
     */
    public function query(): array
    {
        return $this->filterQuery() + ($this->isDefaultSort() ? [] : ['sort' => $this->sort, 'dir' => $this->direction]);
    }

    /**
     * État des filtres pour le composant Alpine ledger.
     *
     * @return array{label: string, accounts: list<string>, axes: list<string>, effective: array{start: string, end: string}, written: array{start: string, end: string}, flow: string, amountMin: string, amountMax: string}
     */
    public function state(): array
    {
        return [
            'label' => $this->label,
            'accounts' => $this->accounts,
            'axes' => $this->axes,
            'effective' => $this->effective,
            'written' => $this->written,
            'flow' => $this->flow,
            'amountMin' => $this->amountMin,
            'amountMax' => $this->amountMax,
        ];
    }

    /**
     * Valeurs reçues qui figurent parmi les valeurs admises, sans doublon, dans l'ordre de celles-ci.
     *
     * @param  mixed  $value  Valeur reçue, de n'importe quel type.
     * @param  list<string>  $allowed  Valeurs admises.
     * @return list<string>
     */
    private static function among(mixed $value, array $allowed): array
    {
        $given = array_filter(is_array($value) ? $value : [], is_string(...));

        return array_values(array_filter($allowed, fn (string $candidate): bool => in_array($candidate, $given, strict: true)));
    }

    /**
     * Plage de jours reçue, faite des seules bornes choisissables, remises dans l'ordre, comme le fait
     * <x-date-picker> pour sa valeur initiale.
     *
     * @param  mixed  $value  Valeur reçue, de n'importe quel type.
     * @param  DateRestriction  $restriction  Restriction des jours admis.
     * @return array{start: string, end: string}
     */
    private static function range(mixed $value, DateRestriction $restriction): array
    {
        $bounds = collect(is_array($value) ? [$value['start'] ?? null, $value['end'] ?? null] : [])
            ->filter($restriction->isChoosable(...))->sort()->values();

        return ['start' => $bounds->first() ?? '', 'end' => $bounds->count() > 1 ? $bounds->last() : ''];
    }

    /**
     * Borne de montant reçue : un nombre positif d'au plus deux décimales, sinon ''.
     *
     * @param  mixed  $value  Valeur reçue, de n'importe quel type.
     */
    private static function amount(mixed $value): string
    {
        return is_string($value) && preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $value) === 1 ? $value : '';
    }
}
