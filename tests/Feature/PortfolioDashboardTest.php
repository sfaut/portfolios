<?php

use App\Models\Account;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\SalesDashboard;
use App\Support\SalesFigures;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;

beforeEach(function () {
    Mail::fake();

    $this->travelTo('2026-11-09 10:00:00');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->portfolio = $this->user->portfolios()->create(['name' => 'Famille']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte']);
});

/**
 * Enregistre des opérations sur un compte, chacune donnée par sa date effective et son montant.
 *
 * @param  array<int, array{0: string, 1: string}>  $operations  Paires [date, montant].
 */
function operations(Account $account, array $operations): void
{
    foreach ($operations as [$delivery_at, $amount]) {
        $account->operations()->create(['label' => 'Opération', 'amount' => $amount, 'delivery_at' => $delivery_at]);
    }
}

/**
 * Chiffres clefs du portfolio à la date du jour (celle du test).
 */
function figures(Portfolio $portfolio): SalesFigures
{
    return new SalesFigures($portfolio, CarbonImmutable::today());
}

test('the figures count the active and the archived accounts', function () {
    $this->portfolio->accounts()->create(['name' => 'Autre']);
    $this->portfolio->accounts()->create(['name' => 'Clos'])->delete();

    expect(figures($this->portfolio)->activeAccountCount())->toBe(2)
        ->and(figures($this->portfolio)->archivedAccountCount())->toBe(1);
});

test('the sales of the current month run to date, compared with the same days a year earlier', function () {
    operations($this->account, [
        ['2026-11-03', '-100.00'],
        ['2026-11-05', '100.00'],
        ['2026-11-20', '-50.00'],
        ['2025-11-04', '-80.00'],
        ['2025-11-15', '-999.00'],
    ]);

    $current_month = figures($this->portfolio)->currentMonth();

    expect($current_month->label)->toBe('ventes de novembre')
        ->and($current_month->amount)->toBe(100.0)
        ->and($current_month->previous)->toBe(80.0)
        ->and($current_month->sentence())->toBe("+25\u{202F}% sur novembre 2025 à date");
});

test('the sales of the previous month in January are those of December', function () {
    $this->travelTo('2027-01-10 10:00:00');
    operations($this->account, [
        ['2026-12-31', '-40.00'],
        ['2027-01-02', '-500.00'],
        ['2025-12-01', '-50.00'],
    ]);

    $previous_month = figures($this->portfolio)->previousMonth();

    expect($previous_month->label)->toBe('ventes de décembre')
        ->and($previous_month->amount)->toBe(40.0)
        ->and($previous_month->sentence())->toBe("\u{2212}20\u{202F}% sur décembre 2025");
});

test('the sales of the last twelve months are those of the complete months before the current one', function () {
    operations($this->account, [
        ['2025-11-01', '-10.00'],
        ['2026-10-31', '-20.00'],
        ['2026-11-01', '-500.00'],
        ['2025-10-31', '-30.00'],
        ['2024-10-31', '-700.00'],
    ]);

    $last_twelve_months = figures($this->portfolio)->lastTwelveMonths();

    expect($last_twelve_months->amount)->toBe(30.0)
        ->and($last_twelve_months->previous)->toBe(30.0)
        ->and($last_twelve_months->sentence())->toBe("0\u{202F}% sur les 12 mois précédents");
});

test('a figure without sales a year earlier says so', function () {
    operations($this->account, [['2026-10-15', '-10.00']]);

    $previous_month = figures($this->portfolio)->previousMonth();

    expect($previous_month->label)->toBe("ventes d'octobre")
        ->and($previous_month->previous)->toBeNull()
        ->and($previous_month->sentence())->toBe('aucune vente sur octobre 2025');
});

test('the outstanding amount adds up the negative balances, archived accounts included', function () {
    operations($this->account, [['2026-10-01', '-100.00'], ['2026-10-05', '30.00']]);
    operations($this->portfolio->accounts()->create(['name' => 'Payé d\'avance']), [['2026-10-01', '20.00']]);
    $archived = $this->portfolio->accounts()->create(['name' => 'Clos']);
    operations($archived, [['2026-09-01', '-50.00']]);
    $archived->delete();

    expect(figures($this->portfolio)->outstanding())->toBe(['amount' => 120.0, 'accounts' => 2]);
});

test('the sales of a period count the negative amounts of the portfolio by effective date', function () {
    operations($this->account, [
        ['2026-02-28', '-1.00'],
        ['2026-03-01', '-10.00'],
        ['2026-04-10', '500.00'],
        ['2026-05-31', '-20.00'],
        ['2026-06-01', '-40.00'],
        ['2025-04-01', '-14.00'],
    ]);
    $archived = $this->portfolio->accounts()->create(['name' => 'Clos']);
    operations($archived, [['2026-04-01', '-5.00']]);
    $archived->delete();
    operations($this->user->portfolios()->create(['name' => 'Autre'])->accounts()->create(['name' => 'Compte']), [['2026-04-01', '-7.00']]);

    $comparison = (new SalesDashboard($this->portfolio, '2026-03', '2026-05', CarbonImmutable::today()))->comparison();

    expect($comparison->label)->toBe('de mars à mai 2026')
        ->and($comparison->amount)->toBe(35.0)
        ->and($comparison->previous)->toBe(14.0)
        ->and($comparison->reference)->toBe('la même période de 2025');
});

test('a period running past today stops at today, and so does its comparison', function () {
    operations($this->account, [
        ['2026-11-09', '-10.00'],
        ['2026-11-10', '-500.00'],
        ['2025-11-09', '-20.00'],
        ['2025-11-10', '-700.00'],
    ]);

    $comparison = (new SalesDashboard($this->portfolio, '2025-12', '2026-11', CarbonImmutable::today()))->comparison();

    expect($comparison->label)->toBe('de décembre 2025 à novembre 2026')
        ->and($comparison->amount)->toBe(10.0)
        ->and($comparison->previous)->toBe(20.0)
        ->and($comparison->reference)->toBe('la même période un an plus tôt, à date');
});

test('the monthly sales list every month of the period with its sales, count, payments and running total', function () {
    operations($this->account, [
        ['2026-03-05', '-10.00'],
        ['2026-03-20', '-30.00'],
        ['2026-03-25', '15.00'],
        ['2026-05-02', '-20.00'],
    ]);

    $months = (new SalesDashboard($this->portfolio, '2026-03', '2026-05', CarbonImmutable::today()))->monthly();

    expect(array_column($months, 'month'))->toBe(['2026-03', '2026-04', '2026-05'])
        ->and(array_column($months, 'sales'))->toBe([40.0, 0.0, 20.0])
        ->and(array_column($months, 'count'))->toBe([2, 0, 1])
        ->and(array_column($months, 'payments'))->toBe([15.0, 0.0, 0.0])
        ->and(array_column($months, 'cumulative'))->toBe([40.0, 40.0, 60.0])
        ->and(array_column($months, 'ratio'))->toBe([1.0, 0.0, 0.5]);
});

test('the cumulative sales step up on each day of sale', function () {
    operations($this->account, [
        ['2026-03-01', '-10.00'],
        ['2026-03-01', '-5.00'],
        ['2026-03-11', '-20.00'],
        ['2025-03-02', '-7.00'],
    ]);

    $cumulative = (new SalesDashboard($this->portfolio, '2026-03', '2026-03', CarbonImmutable::today()))->cumulative();

    expect($cumulative['current'])->toBe(['points' => [[0, 15.0], [10, 35.0]], 'end' => 31])
        ->and($cumulative['previous'])->toBe(['points' => [[1, 7.0]], 'end' => 31]);
});

test('the analytic split shares the sales by axis, archived axes included, the unallocated sales last', function () {
    $piano = $this->portfolio->analyticAxes()->create(['name' => 'Piano']);
    $chess = $this->portfolio->analyticAxes()->create(['name' => 'Échecs']);
    $former = $this->portfolio->analyticAxes()->create(['name' => 'Ancien']);
    $split = function (string $amount, array $lines): void {
        $operation = $this->account->operations()->create(['label' => 'Opération', 'amount' => $amount, 'delivery_at' => '2026-03-10']);

        foreach ($lines as [$axis, $percentage]) {
            $operation->analyticLines()->create(['analytic_axis_id' => $axis->id, 'percentage' => $percentage]);
        }
    };
    $split('-100.00', [[$piano, '65.00'], [$chess, '35.00']]);
    $split('-10.05', [[$piano, '50.00'], [$chess, '50.00']]);
    $split('-1.00', [[$former, '100.00']]);
    $split('-30.00', []);
    $split('50.00', [[$piano, '100.00']]);
    $former->delete();

    $axes = (new SalesDashboard($this->portfolio, '2026-03', '2026-03', CarbonImmutable::today()))->byAnalyticAxis();

    expect(array_column($axes, 'name'))->toBe(['Piano', 'Échecs', 'Ancien', 'Non réparti'])
        ->and(array_column($axes, 'amount'))->toBe([70.03, 40.03, 1.0, 30.0])
        ->and(array_column($axes, 'archived'))->toBe([false, false, true, false])
        ->and(array_column($axes, 'unallocated'))->toBe([false, false, false, true])
        ->and(array_column($axes, 'percent'))->toBe(["50\u{202F}%", "28\u{202F}%", "1\u{202F}%", "21\u{202F}%"]);
});

test('the dashboard period defaults to January up to the month of the last operation, not before the first one', function (array $operations, array $period) {
    operations($this->account, $operations);

    $response = $this->get(route('portfolio.dashboard', $this->portfolio));

    $response->assertOk()->assertViewHas('period', $period);
})->with([
    'whole year' => [[['2025-06-01', '-1.00'], ['2026-10-15', '-1.00']], ['start' => '2026-01', 'end' => '2026-10']],
    'first operation in the year' => [[['2026-03-01', '-1.00'], ['2026-10-15', '-1.00']], ['start' => '2026-03', 'end' => '2026-10']],
]);

test('the dashboard keeps a chosen period, in order, and ignores one outside the operations', function (array $query, array $period) {
    operations($this->account, [['2025-06-01', '-1.00'], ['2026-10-15', '-1.00']]);

    $response = $this->get(route('portfolio.dashboard', [$this->portfolio, ...$query]));

    $response->assertOk()->assertViewHas('period', $period);
})->with([
    'chosen' => [['period' => ['start' => '2025-07', 'end' => '2025-12']], ['start' => '2025-07', 'end' => '2025-12']],
    'reversed' => [['period' => ['start' => '2025-12', 'end' => '2025-07']], ['start' => '2025-07', 'end' => '2025-12']],
    'before the first operation' => [['period' => ['start' => '2025-05', 'end' => '2025-12']], ['start' => '2026-01', 'end' => '2026-10']],
    'incomplete' => [['period' => ['start' => '2025-07']], ['start' => '2026-01', 'end' => '2026-10']],
    'not a range' => [['period' => '2025-07'], ['start' => '2026-01', 'end' => '2026-10']],
]);

test('the dashboard shows the figures and the sales of the period', function () {
    operations($this->account, [['2026-03-01', '-10.00'], ['2026-05-31', '-25.00']]);

    $response = $this->get(route('portfolio.dashboard', [$this->portfolio, 'period' => ['start' => '2026-03', 'end' => '2026-05']]));

    $response->assertOk()
        ->assertSeeText('Chiffres au 9 novembre 2026')
        ->assertSeeText('reste à encaisser')
        ->assertSeeText(Number::amount(35, currency: 'EUR'))
        ->assertSeeText('de ventes de mars à mai 2026')
        ->assertSeeText('Aucune vente sur la même période de 2025.')
        ->assertSeeText('Ventes par mois')
        ->assertSeeText('Non réparti');
});

test('the dashboard of a portfolio without operations invites to fill its accounts', function () {
    $response = $this->get(route('portfolio.dashboard', $this->portfolio));

    $response->assertOk()
        ->assertSeeText('Pas encore de vente')
        ->assertSee('href="'.route('portfolio.show', $this->portfolio).'"', escape: false)
        ->assertDontSeeText('Ventes par mois');
});

test('the dashboard of a period without sales says so', function () {
    operations($this->account, [['2026-03-01', '10.00']]);

    $response = $this->get(route('portfolio.dashboard', $this->portfolio));

    $response->assertOk()
        ->assertSeeText('Aucune vente en mars 2026.')
        ->assertDontSeeText('Ventes par mois');
});

test('the portfolio list leads to the dashboard of each portfolio', function () {
    $response = $this->get(route('portfolio.index'));

    $response->assertSee('href="'.route('portfolio.dashboard', $this->portfolio).'"', escape: false);
});
