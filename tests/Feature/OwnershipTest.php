<?php

use App\Models\Account;
use App\Models\AnalyticAxis;
use App\Models\AnalyticLine;
use App\Models\Operation;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();
    $this->stranger = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Account']);
    $this->operation = $this->account->operations()->create([
        'label' => 'Operation',
        'amount' => '10.00',
        'delivery_at' => '2026-01-15',
    ]);
    $this->analytic_axis = $this->portfolio->analyticAxes()->create(['name' => 'Axis']);
    $this->operation->analyticLines()->create([
        'analytic_axis_id' => $this->analytic_axis->id,
        'percentage' => '100.00',
    ]);
});

test('users cannot reach the data of another user', function (string $method, string $route, string $model) {
    $response = $this
        ->actingAs($this->stranger)
        ->{$method}(route($route, $this->{$model}));

    $response->assertNotFound();
})->with([
    ['get', 'portfolio.dashboard', 'portfolio'],
    ['get', 'portfolio.show', 'portfolio'],
    ['get', 'portfolio.edit', 'portfolio'],
    ['put', 'portfolio.update', 'portfolio'],
    ['delete', 'portfolio.destroy', 'portfolio'],
    ['get', 'account.create', 'portfolio'],
    ['post', 'account.store', 'portfolio'],
    ['get', 'account.show', 'account'],
    ['get', 'account.edit', 'account'],
    ['put', 'account.update', 'account'],
    ['patch', 'account.restore', 'account'],
    ['delete', 'account.destroy', 'account'],
    ['get', 'operation.create', 'account'],
    ['post', 'operation.store', 'account'],
    ['get', 'operation.edit', 'operation'],
    ['put', 'operation.update', 'operation'],
    ['patch', 'operation.tick', 'operation'],
    ['delete', 'operation.destroy', 'operation'],
    ['get', 'ledger.index', 'portfolio'],
    ['get', 'ledger.export', 'portfolio'],
    ['get', 'analytic-axis.index', 'portfolio'],
    ['get', 'analytic-axis.create', 'portfolio'],
    ['post', 'analytic-axis.store', 'portfolio'],
    ['get', 'analytic-axis.edit', 'analytic_axis'],
    ['put', 'analytic-axis.update', 'analytic_axis'],
    ['patch', 'analytic-axis.restore', 'analytic_axis'],
    ['delete', 'analytic-axis.destroy', 'analytic_axis'],
]);

test('users can reach their own data', function (string $route, string $model, array $query) {
    $response = $this
        ->actingAs($this->owner)
        ->get(route($route, [$this->{$model}, ...$query]));

    $response->assertOk();
})->with([
    ['portfolio.dashboard', 'portfolio', []],
    ['portfolio.show', 'portfolio', []],
    ['portfolio.edit', 'portfolio', []],
    ['account.create', 'portfolio', []],
    ['account.show', 'account', ['page' => 1]],
    ['account.edit', 'account', []],
    ['operation.create', 'account', []],
    ['operation.edit', 'operation', []],
    ['ledger.index', 'portfolio', []],
    ['ledger.export', 'portfolio', []],
    ['analytic-axis.index', 'portfolio', []],
    ['analytic-axis.create', 'portfolio', []],
    ['analytic-axis.edit', 'analytic_axis', []],
]);

test('models are hidden without an authenticated user', function () {
    expect(Portfolio::count())->toBe(0)
        ->and(Account::count())->toBe(0)
        ->and(Operation::count())->toBe(0)
        ->and(AnalyticAxis::count())->toBe(0)
        ->and(AnalyticLine::count())->toBe(0);
});

test('models are hidden from another user', function () {
    $this->actingAs($this->stranger);

    expect(AnalyticAxis::count())->toBe(0)
        ->and(AnalyticLine::count())->toBe(0);
});

test('guests are redirected to the login page', function (string $method, string $route, string $model) {
    $response = $this->{$method}(route($route, $this->{$model}));

    $response->assertRedirect(route('login'));
})->with([
    ['get', 'portfolio.dashboard', 'portfolio'],
    ['get', 'portfolio.show', 'portfolio'],
    ['get', 'portfolio.edit', 'portfolio'],
    ['put', 'portfolio.update', 'portfolio'],
    ['delete', 'portfolio.destroy', 'portfolio'],
    ['get', 'account.create', 'portfolio'],
    ['post', 'account.store', 'portfolio'],
    ['get', 'account.show', 'account'],
    ['get', 'account.edit', 'account'],
    ['put', 'account.update', 'account'],
    ['patch', 'account.restore', 'account'],
    ['delete', 'account.destroy', 'account'],
    ['get', 'operation.create', 'account'],
    ['post', 'operation.store', 'account'],
    ['get', 'operation.edit', 'operation'],
    ['put', 'operation.update', 'operation'],
    ['patch', 'operation.tick', 'operation'],
    ['delete', 'operation.destroy', 'operation'],
    ['get', 'ledger.index', 'portfolio'],
    ['get', 'ledger.export', 'portfolio'],
    ['get', 'analytic-axis.index', 'portfolio'],
    ['get', 'analytic-axis.create', 'portfolio'],
    ['post', 'analytic-axis.store', 'portfolio'],
    ['get', 'analytic-axis.edit', 'analytic_axis'],
    ['put', 'analytic-axis.update', 'analytic_axis'],
    ['patch', 'analytic-axis.restore', 'analytic_axis'],
    ['delete', 'analytic-axis.destroy', 'analytic_axis'],
]);

test('guests are redirected to the login page from the pages without model', function (string $method, string $route) {
    $response = $this->{$method}(route($route));

    $response->assertRedirect(route('login'));
})->with([
    ['get', 'portfolio.index'],
    ['get', 'portfolio.create'],
    ['post', 'portfolio.store'],
    ['get', 'profile.edit'],
    ['patch', 'profile.update'],
    ['delete', 'profile.destroy'],
    ['get', 'profile.export'],
]);

test('users cannot reach the archived analytic axis of another user', function (string $method, string $route) {
    $this->analytic_axis->delete();

    $response = $this->actingAs($this->stranger)->{$method}(route($route, $this->analytic_axis));

    $response->assertNotFound();

    expect(AnalyticAxis::withoutGlobalScopes()->find($this->analytic_axis->id)->trashed())->toBeTrue();
})->with([
    ['get', 'analytic-axis.edit'],
    ['put', 'analytic-axis.update'],
    ['patch', 'analytic-axis.restore'],
]);

test('the portfolio list shows the portfolios of the user only', function () {
    $this->stranger->portfolios()->create(['name' => 'Portfolio du voisin']);

    $response = $this->actingAs($this->owner)->get(route('portfolio.index'));

    $response->assertViewHas('portfolios', fn ($portfolios) => $portfolios->modelKeys() === [$this->portfolio->id]);
});

test('a new portfolio belongs to its creator, whatever user the form sends', function () {
    $response = $this->actingAs($this->owner)->post(route('portfolio.store'), [
        'name' => 'Portfolio saisi',
        'description' => '',
        'user_id' => $this->stranger->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('portfolio.index'));

    expect($this->owner->portfolios()->where('name', 'Portfolio saisi')->exists())->toBeTrue()
        ->and(Portfolio::withoutGlobalScopes()->where('user_id', $this->stranger->id)->exists())->toBeFalse();
});

test('a portfolio cannot be given to another user', function () {
    $response = $this->actingAs($this->owner)->put(route('portfolio.update', $this->portfolio), [
        'name' => 'Portfolio saisi',
        'description' => '',
        'user_id' => $this->stranger->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('portfolio.show', $this->portfolio));

    expect(Portfolio::withoutGlobalScopes()->find($this->portfolio->id))
        ->name->toBe('Portfolio saisi')
        ->user_id->toBe($this->owner->id);
});

test('an account cannot be put in the portfolio of another user', function (string $method, string $route, string $model) {
    $stranger_portfolio = $this->stranger->portfolios()->create(['name' => 'Portfolio du voisin']);

    $response = $this->actingAs($this->owner)->{$method}(route($route, $this->{$model}), [
        'name' => 'Compte saisi',
        'description' => '',
        'portfolio_id' => $stranger_portfolio->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    expect($this->portfolio->accounts()->where('name', 'Compte saisi')->exists())->toBeTrue()
        ->and(Account::withoutGlobalScopes()->where('portfolio_id', $stranger_portfolio->id)->exists())->toBeFalse();
})->with([
    'creation' => ['post', 'account.store', 'portfolio'],
    'update' => ['put', 'account.update', 'account'],
]);

test('an operation cannot be put in the account of another user', function (string $method, string $route, string $model) {
    $stranger_account = $this->stranger->portfolios()->create(['name' => 'Portfolio du voisin'])
        ->accounts()->create(['name' => 'Compte du voisin']);

    $response = $this->actingAs($this->owner)->{$method}(route($route, $this->{$model}), [
        'label' => 'Opération saisie',
        'amount' => '20.00',
        'delivery_at' => '2026-02-15',
        'account_id' => $stranger_account->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    expect($this->account->operations()->where('label', 'Opération saisie')->exists())->toBeTrue()
        ->and(Operation::withoutGlobalScopes()->where('account_id', $stranger_account->id)->exists())->toBeFalse();
})->with([
    'creation' => ['post', 'operation.store', 'account'],
    'update' => ['put', 'operation.update', 'operation'],
]);

test('the operation of another user can be neither ticked nor unticked', function (bool $saved, bool $sent) {
    $this->operation->forceFill(['is_ticked' => $saved])->save();

    $this->actingAs($this->stranger)->patchJson(route('operation.tick', $this->operation), ['is_ticked' => $sent])->assertNotFound();
    $this->actingAs($this->stranger)->put(route('operation.update', $this->operation), [
        'label' => 'Operation',
        'amount' => '10.00',
        'delivery_at' => '2026-01-15',
        'is_ticked' => $sent ? '1' : '0',
    ])->assertNotFound();

    expect((bool) Operation::withoutGlobalScopes()->whereKey($this->operation->id)->value('is_ticked'))->toBe($saved);
})->with([
    'tick' => [false, true],
    'untick' => [true, false],
]);

test('a guest cannot tick an operation', function () {
    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => true])->assertUnauthorized();

    expect((bool) Operation::withoutGlobalScopes()->whereKey($this->operation->id)->value('is_ticked'))->toBeFalse();
});

test('the tick filter of the ledger keeps to the operations of its portfolio', function () {
    $this->operation->forceFill(['is_ticked' => true])->save();
    $stranger_account = $this->stranger->portfolios()->create(['name' => 'Portfolio du voisin'])
        ->accounts()->create(['name' => 'Compte du voisin']);
    $stranger_account->operations()->create(['label' => 'Opération du voisin', 'amount' => '5.00', 'delivery_at' => '2026-01-15'])
        ->forceFill(['is_ticked' => true])->save();

    $this->actingAs($this->owner)->get(route('ledger.index', [$this->portfolio, 'ticked' => 'yes']))
        ->assertOk()
        ->assertSee('Pointée : Operation')
        ->assertDontSee('Opération du voisin');
});

test('an analytic axis cannot be put in the portfolio of another user', function (string $method, string $route, string $model) {
    $stranger_portfolio = $this->stranger->portfolios()->create(['name' => 'Portfolio du voisin']);

    $response = $this->actingAs($this->owner)->{$method}(route($route, $this->{$model}), [
        'name' => 'Axe saisi',
        'description' => '',
        'portfolio_id' => $stranger_portfolio->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('analytic-axis.index', $this->portfolio));

    expect($this->portfolio->analyticAxes()->where('name', 'Axe saisi')->exists())->toBeTrue()
        ->and(AnalyticAxis::withoutGlobalScopes()->where('portfolio_id', $stranger_portfolio->id)->exists())->toBeFalse();
})->with([
    'creation' => ['post', 'analytic-axis.store', 'portfolio'],
    'update' => ['put', 'analytic-axis.update', 'analytic_axis'],
]);
