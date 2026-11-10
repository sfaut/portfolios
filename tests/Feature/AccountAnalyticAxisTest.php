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
 * Identifiants des axes affectés au compte, dans l'ordre croissant.
 *
 * @return list<int>
 */
function assignedAxisIds(int $account_id): array
{
    return DB::table('account_analytic_axis')->where('account_id', $account_id)->orderBy('analytic_axis_id')->pluck('analytic_axis_id')->all();
}

test('an account is created with its analytic axes', function () {
    $this->actingAs($this->owner)->post(route('account.store', $this->portfolio), [
        'name' => 'Livret',
        'description' => '',
        'analytic_axes' => [$this->piano->id, $this->chess->id],
    ])->assertSessionHasNoErrors();

    $account_id = DB::table('accounts')->where('name', 'Livret')->value('id');

    expect(assignedAxisIds($account_id))->toBe([$this->piano->id, $this->chess->id]);
});

test('an account update replaces its analytic axes, and none checked removes them all', function () {
    $this->account->analyticAxes()->attach($this->piano);
    $update = fn (array $data) => $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        ...$data,
    ])->assertSessionHasNoErrors();

    $update(['analytic_axes' => [$this->chess->id]]);
    expect(assignedAxisIds($this->account->id))->toBe([$this->chess->id]);

    $update([]);
    expect(assignedAxisIds($this->account->id))->toBe([]);
});

test('an account refuses an axis of another portfolio, an archived one it has not, a duplicate or a non integer', function (Closure $axes, string $field) {
    $response = $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        'analytic_axes' => $axes->call($this),
    ]);

    $response->assertSessionHasErrors($field);

    expect(assignedAxisIds($this->account->id))->toBe([]);
})->with([
    'axis of another portfolio' => [fn () => [$this->owner->portfolios()->create(['name' => 'Autre'])->analyticAxes()->create(['name' => 'Ailleurs'])->id], 'analytic_axes.0'],
    'archived axis not assigned' => [function () {
        $this->piano->delete();

        return [$this->piano->id];
    }, 'analytic_axes.0'],
    'duplicate' => [fn () => [$this->piano->id, $this->piano->id], 'analytic_axes.0'],
    'not an integer' => [fn () => ['piano'], 'analytic_axes.0'],
    'not a list' => [fn () => 'piano', 'analytic_axes'],
]);

test('an archived axis already assigned stays checked and kept, but is offered to no other account', function () {
    $this->account->analyticAxes()->attach($this->piano);
    $this->piano->delete();

    $this->actingAs($this->owner)->get(route('account.edit', $this->account))
        ->assertOk()
        ->assertSee('(archivé)');

    $this->actingAs($this->owner)->put(route('account.update', $this->account), [
        'name' => 'Compte courant',
        'description' => '',
        'analytic_axes' => [$this->piano->id, $this->chess->id],
    ])->assertSessionHasNoErrors();

    expect(assignedAxisIds($this->account->id))->toBe([$this->piano->id, $this->chess->id]);

    $this->actingAs($this->owner)->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertDontSee('value="'.$this->piano->id.'"', escape: false);
});

test('the account form offers the axes of the portfolio as checkboxes, the assigned ones checked', function () {
    $this->account->analyticAxes()->attach($this->chess);

    $html = (string) $this->actingAs($this->owner)->get(route('account.edit', $this->account))->assertOk()->getContent();

    expect($html)->toMatch('/name="analytic_axes\[\]" value="'.$this->chess->id.'"\s+checked/')
        ->and($html)->not->toMatch('/name="analytic_axes\[\]" value="'.$this->piano->id.'"\s+checked/')
        ->and($html)->toContain(AxisColor::Amber->badgeClasses());
});

test('the account form links to the axes page when the portfolio has none', function () {
    $this->piano->delete();
    $this->chess->delete();

    $this->actingAs($this->owner)->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertSee(route('analytic-axis.index', $this->portfolio))
        ->assertDontSee('name="analytic_axes[]"', escape: false);
});

test('the account form keeps the checked axes after a refused validation', function () {
    $this->account->analyticAxes()->attach($this->chess);

    $this->actingAs($this->owner)
        ->from(route('account.edit', $this->account))
        ->put(route('account.update', $this->account), ['name' => '', 'description' => '', 'analytic_axes' => [$this->piano->id]])
        ->assertSessionHasErrors('name');

    $html = (string) $this->actingAs($this->owner)->get(route('account.edit', $this->account))->getContent();

    expect($html)->toMatch('/value="'.$this->piano->id.'"\s+checked/')
        ->and($html)->not->toMatch('/value="'.$this->chess->id.'"\s+checked/');
});

test('the icons of the axes of an account follow its name, with their name in a tooltip', function (string $route, string $model) {
    $this->account->analyticAxes()->attach([$this->piano->id, $this->chess->id]);

    $this->actingAs($this->owner)->get(route($route, [$this->{$model}, 'page' => 1]))
        ->assertOk()
        ->assertSeeInOrder(['Compte courant', AxisColor::Teal->textClasses(), 'Échecs', AxisColor::Amber->textClasses(), 'Piano'], escape: false);
})->with([
    'portfolio' => ['portfolio.show', 'portfolio'],
    'account' => ['account.show', 'account'],
]);

test('the account list loads the axes of all its accounts at once', function () {
    foreach (range(1, 5) as $index) {
        $this->portfolio->accounts()->create(['name' => "Compte {$index}"])->analyticAxes()->attach($this->piano);
    }

    DB::enableQueryLog();
    $this->actingAs($this->owner)->get(route('portfolio.show', $this->portfolio))->assertOk();
    $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $query): bool => str_contains($query, 'account_analytic_axis'));

    expect($queries->count())->toBeLessThanOrEqual(2);
});
