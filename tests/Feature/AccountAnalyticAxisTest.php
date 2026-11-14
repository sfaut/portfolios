<?php

use App\Enums\AxisColor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();
    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->piano = $this->portfolio->analyticAxes()->create(['name' => 'Piano', 'icon' => 'piano', 'color' => 'amber']);
    $this->chess = $this->portfolio->analyticAxes()->create(['name' => 'Échecs', 'icon' => 'chess-knight', 'color' => 'teal']);
});

/**
 * Répartition par défaut enregistrée pour le compte, dans l'ordre de ses positions : axe,
 * pourcentage et position de chaque ligne.
 *
 * @return list<array{int, string, int}>
 */
function defaultBreakdown(int $account_id): array
{
    return DB::table('account_analytic_axis')
        ->where('account_id', $account_id)
        ->orderBy('position')
        ->get()
        ->map(fn (object $link): array => [$link->analytic_axis_id, $link->percentage, $link->position])
        ->all();
}

/**
 * Ligne de la répartition par défaut, telle que le formulaire l'envoie.
 *
 * @return array{analytic_axis_id: int|string, percentage: string}
 */
function breakdownLine(int|string $axis_id, string $percentage): array
{
    return ['analytic_axis_id' => $axis_id, 'percentage' => $percentage];
}

test('an account is created with its default breakdown, in the order sent', function () {
    $this->actingAs($this->owner)->post(route('account.store', $this->portfolio), [
        'name' => 'Grjebine, Arthur',
        'description' => '',
        'analytic_axes' => [breakdownLine($this->piano->id, '80'), breakdownLine($this->chess->id, '20')],
    ])->assertSessionHasNoErrors();

    $account_id = DB::table('accounts')->where('name', 'Grjebine, Arthur')->value('id');

    expect(defaultBreakdown($account_id))->toBe([
        [$this->piano->id, '80.00', 0],
        [$this->chess->id, '20.00', 1],
    ]);
});

test('an account update replaces its default breakdown and its order, and no line removes it', function () {
    $this->account->analyticAxes()->attach($this->piano, ['percentage' => '100', 'position' => 0]);
    $update = fn (array $data) => $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        ...$data,
    ])->assertSessionHasNoErrors();

    $update(['analytic_axes' => [breakdownLine($this->chess->id, '33.33'), breakdownLine($this->piano->id, '66.67')]]);
    expect(defaultBreakdown($this->account->id))->toBe([
        [$this->chess->id, '33.33', 0],
        [$this->piano->id, '66.67', 1],
    ]);

    $update([]);
    expect(defaultBreakdown($this->account->id))->toBe([]);
});

test('an account refuses a default breakdown whose total is not 100 %', function (array $percentages, string $current) {
    $response = $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        'analytic_axes' => [breakdownLine($this->piano->id, $percentages[0]), breakdownLine($this->chess->id, $percentages[1])],
    ]);

    $response->assertSessionHasErrors(['analytic_axes' => "Le total de la répartition analytique doit faire 100 % (actuellement {$current})."]);

    expect(defaultBreakdown($this->account->id))->toBe([]);
})->with([
    'below' => [['80', '19.99'], "99,99\u{a0}%"],
    'above' => [['80', '20.01'], "100,01\u{a0}%"],
]);

test('an account refuses an unavailable axis or an invalid percentage', function (Closure $line, string $field) {
    $response = $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        'analytic_axes' => $line->call($this),
    ]);

    $response->assertSessionHasErrors($field);

    expect(defaultBreakdown($this->account->id))->toBe([]);
})->with([
    'axis of another portfolio' => [fn () => [breakdownLine($this->owner->portfolios()->create(['name' => 'Autre'])->analyticAxes()->create(['name' => 'Ailleurs'])->id, '100')], 'analytic_axes.0.analytic_axis_id'],
    'archived axis not assigned' => [function () {
        $this->piano->delete();

        return [breakdownLine($this->piano->id, '100')];
    }, 'analytic_axes.0.analytic_axis_id'],
    'duplicate axis' => [fn () => [breakdownLine($this->piano->id, '50'), breakdownLine($this->piano->id, '50')], 'analytic_axes.0.analytic_axis_id'],
    'axis not an integer' => [fn () => [breakdownLine('piano', '100')], 'analytic_axes.0.analytic_axis_id'],
    'missing axis' => [fn () => [breakdownLine('', '100')], 'analytic_axes.0.analytic_axis_id'],
    'zero percentage' => [fn () => [breakdownLine($this->piano->id, '0'), breakdownLine($this->chess->id, '100')], 'analytic_axes.0.percentage'],
    'percentage above 100' => [fn () => [breakdownLine($this->piano->id, '100.01')], 'analytic_axes.0.percentage'],
    'three decimals' => [fn () => [breakdownLine($this->piano->id, '99.999'), breakdownLine($this->chess->id, '0.001')], 'analytic_axes.0.percentage'],
    'line not a list' => [fn () => ['piano'], 'analytic_axes.0'],
    'not a list' => [fn () => 'piano', 'analytic_axes'],
]);

test('an archived axis of the breakdown stays offered and kept, but is offered to no other account', function () {
    $this->account->analyticAxes()->attach($this->piano, ['percentage' => '100', 'position' => 0]);
    $this->piano->delete();

    $this->actingAs($this->owner)->get(route('account.edit', $this->account))
        ->assertOk()
        ->assertSee('Piano', escape: false);

    $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        'analytic_axes' => [breakdownLine($this->piano->id, '60'), breakdownLine($this->chess->id, '40')],
    ])->assertSessionHasNoErrors();

    expect(defaultBreakdown($this->account->id))->toBe([
        [$this->piano->id, '60.00', 0],
        [$this->chess->id, '40.00', 1],
    ]);

    $this->actingAs($this->owner)->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertDontSee('Piano');
});

test('the account form gives the lines of the account to its editor, in their order', function () {
    $this->account->analyticAxes()->attach([
        $this->piano->id => ['percentage' => '20', 'position' => 1],
        $this->chess->id => ['percentage' => '80', 'position' => 0],
    ]);

    $html = (string) $this->actingAs($this->owner)->get(route('account.edit', $this->account))->assertOk()->getContent();

    expect(editorLines($html))->toBe([
        ['analytic_axis_id' => (string) $this->chess->id, 'percentage' => '80.00'],
        ['analytic_axis_id' => (string) $this->piano->id, 'percentage' => '20.00'],
    ])->and($html)->toContain('x-sort="move($item, $position)"');
});

test('the account form gives back the lines sent after a refused validation', function () {
    $this->account->analyticAxes()->attach($this->chess, ['percentage' => '100', 'position' => 0]);

    $this->actingAs($this->owner)
        ->from(route('account.edit', $this->account))
        ->put(route('account.update', $this->account), [
            'name' => '',
            'description' => '',
            'analytic_axes' => [breakdownLine($this->piano->id, '70')],
        ])
        ->assertSessionHasErrors('name');

    $html = (string) $this->actingAs($this->owner)->get(route('account.edit', $this->account))->getContent();

    expect(editorLines($html))->toBe([['analytic_axis_id' => $this->piano->id, 'percentage' => '70']]);
});

test('the account form links to the axes page when the portfolio has none', function () {
    $this->piano->delete();
    $this->chess->delete();

    $this->actingAs($this->owner)->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertSee(route('analytic-axis.index', $this->portfolio))
        ->assertDontSee('x-data="analyticLines(', escape: false);
});

test('the icons of the axes of an account follow its name, in the order of the account', function (string $route, string $model) {
    $this->account->analyticAxes()->attach([
        $this->piano->id => ['percentage' => '80', 'position' => 0],
        $this->chess->id => ['percentage' => '20', 'position' => 1],
    ]);

    $this->actingAs($this->owner)->get(route($route, [$this->{$model}, 'page' => 1]))
        ->assertOk()
        ->assertSeeInOrder(['Compte courant', AxisColor::Amber->textClasses(), 'Piano', AxisColor::Teal->textClasses(), 'Échecs'], escape: false);
})->with([
    'portfolio' => ['portfolio.show', 'portfolio'],
    'account' => ['account.show', 'account'],
]);

test('the account list loads the axes of all its accounts at once', function () {
    foreach (range(1, 5) as $index) {
        $this->portfolio->accounts()->create(['name' => "Compte {$index}"])->analyticAxes()->attach($this->piano, ['percentage' => '100', 'position' => 0]);
    }

    DB::enableQueryLog();
    $this->actingAs($this->owner)->get(route('portfolio.show', $this->portfolio))->assertOk();
    $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains($query, 'account_analytic_axis'));

    expect($queries->count())->toBeLessThanOrEqual(2);
});
