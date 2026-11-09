<?php

use App\Http\Controllers\LedgerController;
use App\Models\Account;
use App\Models\AnalyticAxis;
use App\Models\Operation;
use App\Models\User;
use App\Support\Ledger\Ledger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

/*
 * Five operations over three accounts, the last account archived. Their identifiers follow the
 * order below; their writing times and effective dates do not.
 */
beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();
    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Cabinet Martin']);

    $this->bank = $this->portfolio->accounts()->create(['name' => 'Banque']);
    $this->cash = $this->portfolio->accounts()->create(['name' => 'Caisse']);
    $this->former = $this->portfolio->accounts()->create(['name' => 'Ancien compte']);

    $this->consulting = $this->portfolio->analyticAxes()->create(['name' => 'Conseil']);
    $this->studies = $this->portfolio->analyticAxes()->create(['name' => 'Études']);
    $this->training = $this->portfolio->analyticAxes()->create(['name' => 'Formation']);

    $this->alpha = ledgerOperation($this->bank, 'Facture Alpha', '-100.00', '2026-01-10', '2026-01-11 09:00:00', [[$this->consulting, '100.00']]);
    $this->settlement = ledgerOperation($this->bank, 'Règlement Alpha', '100.00', '2026-02-05', '2026-02-05 10:00:00');
    $this->beta = ledgerOperation($this->cash, 'Facture Bêta', '-250.50', '2026-02-20', '2026-02-21 08:00:00', [[$this->training, '60.00'], [$this->studies, '40.00']]);
    $this->summer = ledgerOperation($this->former, 'Vente estivale', '-40.00', '2025-07-01', '2025-07-02 12:00:00');
    $this->deposit = ledgerOperation($this->cash, 'Acompte 100%', '30.00', '2026-03-01', '2026-03-01 23:59:59');

    $this->former->delete();

    $this->actingAs($this->owner);
});

/**
 * Writes an operation at a given time, with its analytic lines.
 *
 * @param  Account  $account  Account of the operation.
 * @param  string  $label  Label.
 * @param  string  $amount  Amount, negative for a sale.
 * @param  string  $delivery_at  Effective date.
 * @param  string  $created_at  Writing time.
 * @param  list<array{0: AnalyticAxis, 1: string}>  $lines  Axes and their percentages.
 */
function ledgerOperation(object $account, string $label, string $amount, string $delivery_at, string $created_at, array $lines = []): Operation
{
    $operation = $account->operations()->create(['label' => $label, 'amount' => $amount, 'delivery_at' => $delivery_at]);
    $operation->forceFill(['created_at' => $created_at])->save();

    foreach ($lines as [$axis, $percentage]) {
        $operation->analyticLines()->create(['analytic_axis_id' => $axis->id, 'percentage' => $percentage]);
    }

    return $operation;
}

/**
 * Asserts that the ledger lists exactly the given operations, in order.
 *
 * @param  TestResponse  $response  Response of the ledger page.
 * @param  list<string>  $labels  Labels expected, in order.
 */
function assertLedgerLists(TestResponse $response, array $labels): void
{
    $response->assertOk();

    preg_match_all('/<td class="border px-3 py-2">([^<]+)<\/td>/', $response->getContent(), $matches);

    // The cell of the axes has the same classes, and is empty without analytic line.
    $found = array_values(array_filter(array_map(fn (string $label): string => html_entity_decode(trim($label)), $matches[1]), fn (string $label): bool => $label !== ''));

    expect($found)->toBe($labels);
}

/**
 * Ledger page of the portfolio of the tests.
 *
 * @param  array<string, mixed>  $query  Filters, sort and page.
 */
function ledger(array $query = []): TestResponse
{
    return test()->get(route('ledger.index', [test()->portfolio, ...$query]));
}

test('the ledger lists the operations of every account, the last written first', function () {
    $response = ledger();

    assertLedgerLists($response, ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']);

    $response->assertSeeInOrder(['Caisse', '01/03/2026 23:59:59', '01/03/2026', 'Acompte 100%', '30,00'], escape: false)
        ->assertSeeInOrder(['Facture Bêta', '−250,50', 'Formation', 'Études'], escape: false);
});

test('the ledger shows the identifier of each operation', function () {
    ledger(['sort' => 'id', 'dir' => 'asc'])->assertSeeInOrder([
        'ID',
        "<td class=\"border px-3 py-2 text-right tabular-nums\">{$this->alpha->id}</td>",
        'Facture Alpha',
        "<td class=\"border px-3 py-2 text-right tabular-nums\">{$this->deposit->id}</td>",
        'Acompte 100%',
    ], escape: false);
});

test('the ledger marks the operations of an archived account and leaves them read only', function () {
    $response = ledger();

    $response->assertSeeInOrder(['Ancien compte', '(archivé)', 'Vente estivale'], escape: false)
        ->assertSee(route('operation.edit', $this->alpha), escape: false)
        ->assertDontSee(route('operation.edit', $this->summer), escape: false);
});

test('the ledger filters its operations', function (array $query, array $labels) {
    assertLedgerLists(ledger($query), $labels);
})->with([
    'effective range' => [['effective' => ['start' => '2026-02-01', 'end' => '2026-02-28']], ['Facture Bêta', 'Règlement Alpha']],
    'effective day' => [['effective' => ['start' => '2026-02-20', 'end' => '2026-02-20']], ['Facture Bêta']],
    'effective range without end' => [['effective' => ['start' => '2026-02-20']], ['Acompte 100%', 'Facture Bêta']],
    'effective range, reversed' => [['effective' => ['start' => '2026-02-28', 'end' => '2026-02-01']], ['Facture Bêta', 'Règlement Alpha']],
    'writing range' => [['written' => ['start' => '2026-01-11', 'end' => '2026-02-05']], ['Règlement Alpha', 'Facture Alpha']],
    'writing day, up to its last second' => [['written' => ['start' => '2026-03-01', 'end' => '2026-03-01']], ['Acompte 100%']],
    'label, whatever the case and the accents' => [['label' => 'beta'], ['Facture Bêta']],
    'label, several operations' => [['label' => 'ALPHA'], ['Règlement Alpha', 'Facture Alpha']],
    'label, literal percent sign' => [['label' => '100%'], ['Acompte 100%']],
    'label, literal underscore' => [['label' => '_'], []],
    'sales' => [['flow' => 'sales'], ['Facture Bêta', 'Facture Alpha', 'Vente estivale']],
    'payments' => [['flow' => 'payments'], ['Acompte 100%', 'Règlement Alpha']],
    'lowest amount, without its sign' => [['amount' => ['min' => '100']], ['Facture Bêta', 'Règlement Alpha', 'Facture Alpha']],
    'highest amount, without its sign' => [['amount' => ['max' => '40.00']], ['Acompte 100%', 'Vente estivale']],
    'amount range' => [['amount' => ['min' => '40', 'max' => '100']], ['Règlement Alpha', 'Facture Alpha', 'Vente estivale']],
    'operations without analytic line' => [['axes' => ['none']], ['Acompte 100%', 'Règlement Alpha', 'Vente estivale']],
    'several filters' => [['flow' => 'sales', 'effective' => ['start' => '2026-01-01', 'end' => '2026-12-31'], 'amount' => ['max' => '200']], ['Facture Alpha']],
]);

test('the ledger filters and sorts its operations on their tick', function () {
    $this->alpha->forceFill(['is_ticked' => true])->save();
    $this->deposit->forceFill(['is_ticked' => true])->save();

    assertLedgerLists(ledger(['ticked' => 'yes']), ['Acompte 100%', 'Facture Alpha']);
    assertLedgerLists(ledger(['ticked' => 'no']), ['Facture Bêta', 'Règlement Alpha', 'Vente estivale']);
    assertLedgerLists(ledger(['ticked' => 'maybe']), ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']);
    assertLedgerLists(ledger(['sort' => 'ticked', 'dir' => 'asc']), ['Règlement Alpha', 'Facture Bêta', 'Vente estivale', 'Facture Alpha', 'Acompte 100%']);
    assertLedgerLists(ledger(['sort' => 'ticked', 'dir' => 'desc']), ['Acompte 100%', 'Facture Alpha', 'Vente estivale', 'Facture Bêta', 'Règlement Alpha']);
});

test('the ledger shows the tick of each operation, which only the active accounts can change', function () {
    $this->alpha->forceFill(['is_ticked' => true])->save();

    $response = ledger(['sort' => 'id', 'dir' => 'asc'])->assertSee('Pointée ?');
    $html = $response->getContent();

    expect(substr_count($html, 'x-data="tickBox('))->toBe(4)
        ->and($html)->toContain(route('operation.tick', $this->alpha->id))
        ->and($html)->not->toContain(route('operation.tick', $this->summer->id))
        ->and($html)->toContain('aria-label="Pointée : Vente estivale"');

    expect(mb_strpos($html, 'Pointée : Facture Alpha'))->toBeGreaterThan(0)
        ->and(preg_match('/<input[^>]*\schecked\s[^>]*aria-label="Pointée : Facture Alpha"/s', $html))->toBe(1)
        ->and(preg_match('/<input[^>]*\schecked\s[^>]*aria-label="Pointée : Règlement Alpha"/s', $html))->toBe(0);
});

test('the ledger filters its operations by account, archived ones included', function () {
    assertLedgerLists(ledger(['accounts' => [(string) $this->cash->id]]), ['Acompte 100%', 'Facture Bêta']);
    assertLedgerLists(ledger(['accounts' => [(string) $this->bank->id, (string) $this->former->id]]), ['Règlement Alpha', 'Facture Alpha', 'Vente estivale']);
});

test('the ledger filters its operations by analytic axis, keeping those on at least one of the axes', function () {
    assertLedgerLists(ledger(['axes' => [(string) $this->studies->id]]), ['Facture Bêta']);
    assertLedgerLists(ledger(['axes' => [(string) $this->studies->id, (string) $this->consulting->id]]), ['Facture Bêta', 'Facture Alpha']);
    assertLedgerLists(ledger(['axes' => [(string) $this->consulting->id, 'none']]), ['Acompte 100%', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']);
});

test('the ledger offers an archived axis only while an operation uses it', function () {
    $this->consulting->delete();
    $this->portfolio->analyticAxes()->create(['name' => 'Axe abandonné'])->delete();

    $options = (new Ledger($this->portfolio))->axisOptions();

    expect(array_column($options, 'label'))->toBe(['Non réparti', 'Conseil', 'Études', 'Formation'])
        ->and(array_column($options, 'hint', 'label'))->toBe(['Conseil' => 'archivé']);

    assertLedgerLists(ledger(['axes' => [(string) $this->consulting->id]]), ['Facture Alpha']);
});

test('the ledger ignores the filters it refuses', function (array $query) {
    assertLedgerLists(ledger($query), ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']);
})->with([
    'unknown account' => [['accounts' => ['999999']]],
    'accounts as a text' => [['accounts' => '1']],
    'nested accounts' => [['accounts' => [['1']]]],
    'unknown axis' => [['axes' => ['999999', 'all']]],
    'malformed date' => [['effective' => ['start' => '2026-02-30', 'end' => 'x']]],
    'date out of the operations' => [['written' => ['start' => '2030-01-01', 'end' => '2030-12-31']]],
    'date as a text' => [['effective' => '2026-02-20']],
    'label as a list' => [['label' => ['Alpha']]],
    'unknown flow' => [['flow' => 'refunds']],
    'malformed amounts' => [['amount' => ['min' => '-5', 'max' => '1e3']]],
    'amount as a text' => [['amount' => '100']],
    'unknown sort' => [['sort' => 'id; DROP TABLE operations', 'dir' => 'sideways']],
]);

test('the ledger ignores the account and the axis of another portfolio', function () {
    $other = $this->owner->portfolios()->create(['name' => 'Autre']);
    $account = $other->accounts()->create(['name' => 'Ailleurs']);
    $axis = $other->analyticAxes()->create(['name' => 'Ailleurs']);
    ledgerOperation($account, 'Opération d\'ailleurs', '-5.00', '2026-02-01', '2026-02-01 08:00:00', [[$axis, '100.00']]);

    $response = ledger(['accounts' => [(string) $account->id], 'axes' => [(string) $axis->id]]);

    assertLedgerLists($response, ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']);
    $response->assertDontSee('Ailleurs');
});

test('the ledger sorts its operations on each column, both ways', function (string $sort, string $direction, array $labels) {
    $response = ledger(['sort' => $sort, 'dir' => $direction]);

    assertLedgerLists($response, $labels);
    $response->assertSee('aria-sort="'.($direction === 'asc' ? 'ascending' : 'descending').'"', escape: false);
    expect(substr_count($response->getContent(), 'aria-sort="none"'))->toBe(7);
})->with([
    ['id', 'asc', ['Facture Alpha', 'Règlement Alpha', 'Facture Bêta', 'Vente estivale', 'Acompte 100%']],
    ['id', 'desc', ['Acompte 100%', 'Vente estivale', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha']],
    ['account', 'asc', ['Vente estivale', 'Facture Alpha', 'Règlement Alpha', 'Facture Bêta', 'Acompte 100%']],
    ['account', 'desc', ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']],
    ['written', 'asc', ['Vente estivale', 'Facture Alpha', 'Règlement Alpha', 'Facture Bêta', 'Acompte 100%']],
    ['effective', 'asc', ['Vente estivale', 'Facture Alpha', 'Règlement Alpha', 'Facture Bêta', 'Acompte 100%']],
    ['effective', 'desc', ['Acompte 100%', 'Facture Bêta', 'Règlement Alpha', 'Facture Alpha', 'Vente estivale']],
    ['label', 'asc', ['Acompte 100%', 'Facture Alpha', 'Facture Bêta', 'Règlement Alpha', 'Vente estivale']],
    ['label', 'desc', ['Vente estivale', 'Règlement Alpha', 'Facture Bêta', 'Facture Alpha', 'Acompte 100%']],
    ['amount', 'asc', ['Facture Bêta', 'Facture Alpha', 'Vente estivale', 'Acompte 100%', 'Règlement Alpha']],
    ['amount', 'desc', ['Règlement Alpha', 'Acompte 100%', 'Vente estivale', 'Facture Alpha', 'Facture Bêta']],
    'axes, by their first name, operations without axis last' => ['axes', 'asc', ['Facture Alpha', 'Facture Bêta', 'Règlement Alpha', 'Vente estivale', 'Acompte 100%']],
    'axes, reversed, operations without axis still last' => ['axes', 'desc', ['Facture Bêta', 'Facture Alpha', 'Acompte 100%', 'Vente estivale', 'Règlement Alpha']],
]);

test('the sort headings keep the filters and reverse the current sort', function () {
    $response = ledger(['flow' => 'sales', 'sort' => 'amount', 'dir' => 'asc', 'page' => 1]);

    $response->assertOk()
        ->assertSee('flow=sales&amp;sort=amount&amp;dir=desc"', escape: false)
        ->assertSee('flow=sales&amp;sort=label&amp;dir=asc"', escape: false)
        ->assertSee('<input type="hidden" form="ledger-filters" name="sort" value="amount">', escape: false)
        ->assertSee('<input type="hidden" form="ledger-filters" name="dir" value="asc">', escape: false);
});

test('the ledger totals the operations its filters keep', function () {
    ledger()->assertSeeInOrder(['Opérations', '5', 'Ventes', '390,50', 'Paiements', '130,00', 'Solde', '−260,50'], escape: false)
        ->assertDontSee('sur 5');

    ledger(['accounts' => [(string) $this->bank->id]])
        ->assertSeeInOrder(['Opérations', '2', 'sur 5', 'Ventes', '100,00', 'Paiements', '100,00', 'Solde', '0,00'], escape: false);

    ledger(['label' => 'introuvable'])
        ->assertSeeInOrder(['Opérations', '0', 'sur 5', 'Aucune opération ne correspond aux filtres.'], escape: false)
        ->assertDontSee('Exporter en CSV');
});

test('the ledger of a portfolio without operation invites to write some', function () {
    $empty = $this->owner->portfolios()->create(['name' => 'Vide']);

    $this->get(route('ledger.index', $empty))
        ->assertOk()
        ->assertSee('Pas encore d\'opération dans ce portfolio', escape: false)
        ->assertDontSee('Aucune opération ne correspond aux filtres.');
});

test('the ledger shows its filters again in its fields', function () {
    $response = ledger([
        'label' => 'Facture',
        'accounts' => [(string) $this->cash->id],
        'effective' => ['start' => '2026-02-01', 'end' => '2026-02-28'],
        'flow' => 'sales',
        'amount' => ['min' => '10.5'],
    ]);

    $response->assertOk()
        ->assertSee('value="Facture"', escape: false)
        ->assertSee('aria-label="Comptes : Caisse"', escape: false)
        ->assertSee('aria-label="Date effective : 1 – 28 févr. 2026"', escape: false)
        ->assertSee('<input type="hidden" name="effective[start]" value="2026-02-01"', escape: false)
        ->assertSee('value="10.5"', escape: false)
        ->assertSee('Effacer les filtres');
});

test('the ledger bounds its date pickers to the dates of its operations', function () {
    $ledger = new Ledger($this->portfolio);

    expect($ledger->effectiveRestriction()->toArray())->toMatchArray(['min' => '2025-07-01', 'max' => '2026-03-01'])
        ->and($ledger->writtenRestriction()->toArray())->toMatchArray(['min' => '2025-07-02', 'max' => '2026-03-01']);
});

test('the ledger is paginated, its links keeping the filters and the sort', function () {
    foreach (range(1, Ledger::PER_PAGE + 5) as $index) {
        ledgerOperation($this->bank, "Lot {$index}", '-1.00', '2026-03-01', '2026-03-02 08:00:00');
    }

    $first = ledger(['label' => 'Lot', 'sort' => 'effective', 'dir' => 'asc']);

    $first->assertOk()
        ->assertSeeInOrder(['Page 1', '/ 2'], escape: false)
        ->assertSee('label=Lot&amp;sort=effective&amp;dir=asc&amp;page=2"', escape: false);
    expect(substr_count($first->getContent(), '<td class="border px-3 py-2">Lot '))->toBe(Ledger::PER_PAGE);

    $second = ledger(['label' => 'Lot', 'sort' => 'effective', 'dir' => 'asc', 'page' => 2]);

    expect(substr_count($second->getContent(), '<td class="border px-3 py-2">Lot '))->toBe(5);
});

test('the all toggle keeps its preference in the session and comes back with the filters', function () {
    ledger(['all' => '1', 'flow' => 'sales', 'sort' => 'amount', 'dir' => 'asc', 'page' => 3])
        ->assertRedirect(route('ledger.index', [$this->portfolio, 'flow' => 'sales', 'sort' => 'amount', 'dir' => 'asc']))
        ->assertSessionHas(LedgerController::SHOW_ALL_OPERATIONS, true);

    ledger(['all' => '0'])
        ->assertRedirect(route('ledger.index', $this->portfolio))
        ->assertSessionHas(LedgerController::SHOW_ALL_OPERATIONS, false);
});

test('the ledger shows every operation at once when asked', function () {
    foreach (range(1, Ledger::PER_PAGE + 5) as $index) {
        ledgerOperation($this->bank, "Lot {$index}", '-1.00', '2026-03-01', '2026-03-02 08:00:00');
    }

    $response = $this->withSession([LedgerController::SHOW_ALL_OPERATIONS => true])->get(route('ledger.index', [$this->portfolio, 'label' => 'Lot']));

    $response->assertOk()
        ->assertSee('aria-pressed="true"', escape: false)
        ->assertDontSee('Page 1');
    expect(substr_count($response->getContent(), '<td class="border px-3 py-2">Lot '))->toBe(Ledger::PER_PAGE + 5);
});

test('an ajax request receives the ledger fragment alone', function () {
    $response = $this->get(route('ledger.index', [$this->portfolio, 'flow' => 'payments']), ['X-Requested-With' => 'XMLHttpRequest']);

    assertLedgerLists($response, ['Acompte 100%', 'Règlement Alpha']);
    $response->assertHeader('Vary', 'X-Requested-With')
        ->assertDontSee('<html', escape: false)
        ->assertDontSee('id="ledger-filters"', escape: false)
        ->assertSee('Paiements');
});
