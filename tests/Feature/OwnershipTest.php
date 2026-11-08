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
    ['delete', 'operation.destroy', 'operation'],
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
    ['portfolio.show', 'portfolio', []],
    ['portfolio.edit', 'portfolio', []],
    ['account.create', 'portfolio', []],
    ['account.show', 'account', ['page' => 1]],
    ['account.edit', 'account', []],
    ['operation.create', 'account', []],
    ['operation.edit', 'operation', []],
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
