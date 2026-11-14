<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->piano = $this->portfolio->analyticAxes()->create(['name' => 'Cours piano']);
    $this->chess = $this->portfolio->analyticAxes()->create(['name' => 'Cours échecs']);
});

/**
 * Champs d'une opération de 30 €, complétés des lignes analytiques données.
 *
 * @param  list<array{analytic_axis_id: int, percentage: string}>  $analytic_lines  Lignes analytiques.
 * @return array<string, mixed>
 */
function lessonOperation(array $analytic_lines): array
{
    return [
        'label' => 'Cours 1h piano/échecs',
        'amount' => '30.00',
        'delivery_at' => '2026-11-08',
        'analytic_lines' => $analytic_lines,
    ];
}

test('an operation can be split between analytic axes', function () {
    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '50'],
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '50'],
    ]));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.show', $this->account));

    $operation = $this->account->operations()->sole();

    expect($operation->analyticLines()->orderBy('analytic_axis_id')->get(['analytic_axis_id', 'percentage'])->toArray())
        ->toBe([
            ['analytic_axis_id' => $this->piano->id, 'percentage' => '50.00'],
            ['analytic_axis_id' => $this->chess->id, 'percentage' => '50.00'],
        ]);
});

test('an operation can be saved without analytic lines', function () {
    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([]));

    $response->assertSessionHasNoErrors();

    expect($this->account->operations()->sole()->analyticLines()->count())->toBe(0);
});

test('the analytic lines must total 100 percent', function (array $percentages) {
    $axes = [$this->piano, $this->chess];
    $lines = array_map(
        fn (string $percentage, int $index): array => ['analytic_axis_id' => $axes[$index]->id, 'percentage' => $percentage],
        $percentages,
        array_keys($percentages),
    );

    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation($lines));

    $response->assertSessionHasErrors('analytic_lines');

    expect($this->account->operations()->count())->toBe(0);
})->with([
    'below' => [['50', '40']],
    'above' => [['60', '40.01']],
    'a single partial line' => [['99.99']],
]);

test('the analytic lines accept two decimal places', function () {
    $third = $this->portfolio->analyticAxes()->create(['name' => 'Cours chant']);

    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '33.33'],
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '33.33'],
        ['analytic_axis_id' => $third->id, 'percentage' => '33.34'],
    ]));

    $response->assertSessionHasNoErrors();
});

test('an analytic axis cannot be used twice in an operation', function () {
    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '50'],
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '50'],
    ]));

    $response->assertSessionHasErrors(['analytic_lines.0.analytic_axis_id', 'analytic_lines.1.analytic_axis_id']);
});

test('an analytic line needs an axis and a positive percentage', function (array $line, string $field) {
    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([
        [...['analytic_axis_id' => $this->piano->id, 'percentage' => '100'], ...$line],
    ]));

    $response->assertSessionHasErrors("analytic_lines.0.{$field}");
})->with([
    'no axis' => [['analytic_axis_id' => ''], 'analytic_axis_id'],
    'no percentage' => [['percentage' => ''], 'percentage'],
    'zero percentage' => [['percentage' => '0'], 'percentage'],
    'three decimal places' => [['percentage' => '99.999'], 'percentage'],
]);

test('an operation cannot use the analytic axis of another portfolio', function () {
    $other_axis = $this->owner->portfolios()->create(['name' => 'Autre portfolio'])
        ->analyticAxes()->create(['name' => 'Ailleurs']);

    $response = $this->actingAs($this->owner)->post(route('operation.store', $this->account), lessonOperation([
        ['analytic_axis_id' => $other_axis->id, 'percentage' => '100'],
    ]));

    $response->assertSessionHasErrors('analytic_lines.0.analytic_axis_id');
});

test('an archived analytic axis cannot be chosen for a new operation', function () {
    $this->chess->delete();

    $this->actingAs($this->owner)
        ->get(route('operation.create', $this->account))
        ->assertOk()
        ->assertSee('Cours piano')
        ->assertDontSee('Cours échecs');

    $this->actingAs($this->owner)
        ->post(route('operation.store', $this->account), lessonOperation([
            ['analytic_axis_id' => $this->chess->id, 'percentage' => '100'],
        ]))
        ->assertSessionHasErrors('analytic_lines.0.analytic_axis_id');
});

test('an operation keeps an archived analytic axis it already uses', function () {
    $operation = $this->account->operations()->create(lessonOperation([]));
    $operation->analyticLines()->create(['analytic_axis_id' => $this->chess->id, 'percentage' => '100']);
    $this->chess->delete();

    $this->actingAs($this->owner)
        ->get(route('operation.edit', $operation))
        ->assertOk()
        ->assertSee('Cours échecs');

    $this->actingAs($this->owner)
        ->put(route('operation.update', $operation), lessonOperation([
            ['analytic_axis_id' => $this->chess->id, 'percentage' => '100'],
        ]))
        ->assertSessionHasNoErrors();
});

test('updating an operation replaces its analytic lines', function () {
    $operation = $this->account->operations()->create(lessonOperation([]));
    $operation->analyticLines()->create(['analytic_axis_id' => $this->piano->id, 'percentage' => '100']);

    $response = $this->actingAs($this->owner)->put(route('operation.update', $operation), lessonOperation([
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '100'],
    ]));

    $response->assertSessionHasNoErrors();

    expect($operation->analyticLines()->pluck('analytic_axis_id')->all())->toBe([$this->chess->id]);

    $this->actingAs($this->owner)
        ->put(route('operation.update', $operation), lessonOperation([]))
        ->assertSessionHasNoErrors();

    expect($operation->analyticLines()->count())->toBe(0);
});

test('the operation form offers to create an analytic axis when the portfolio has none', function () {
    $account = $this->owner->portfolios()->create(['name' => 'Sans axe'])
        ->accounts()->create(['name' => 'Compte']);

    $this->actingAs($this->owner)
        ->get(route('operation.create', $account))
        ->assertOk()
        ->assertSee("Ce portfolio n'a pas encore d'axe analytique", escape: false)
        ->assertSee(route('analytic-axis.create', $account->portfolio));
});

test('the operation form gives back the posted analytic lines after a failed validation', function () {
    $this->actingAs($this->owner)
        ->from(route('operation.create', $this->account))
        ->followingRedirects()
        ->post(route('operation.store', $this->account), [
            ...lessonOperation([['analytic_axis_id' => $this->chess->id, 'percentage' => '40']]),
            'label' => '',
        ])
        ->assertOk()
        ->assertSee(
            // Js::from() writes each double quote as its JSON escape sequence.
            str_replace('"', '\\'.'u0022', '"lines":[{"analytic_axis_id":'.$this->chess->id.',"percentage":"40"}]'),
            escape: false,
        );
});

test('the operations of an account write each of their analytic lines in their row, without tooltip', function () {
    $split = $this->account->operations()->create([...lessonOperation([]), 'label' => 'Cours réparti']);
    $split->analyticLines()->createMany([
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '50'],
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '50'],
    ]);
    $this->account->operations()->create([...lessonOperation([]), 'label' => 'Cours non réparti']);

    $response = $this->actingAs($this->owner)->get(route('account.show', [$this->account, 'page' => 1]));

    $response->assertOk()
        ->assertSeeInOrder([
            'Cours réparti',
            'Cours échecs', "50\u{a0}%", "15,00\u{a0}€", 'Cours piano', "50\u{a0}%", "15,00\u{a0}€",
            'Cours non réparti',
        ], escape: false);

    expect($response->getContent())->not->toContain('role="tooltip"')
        ->and($response->getContent())->not->toContain('✓');
});

test('the analytic breakdown of an account rounds the part of the amount half away from zero', function () {
    $operation = $this->account->operations()->create([...lessonOperation([]), 'amount' => '-0.05']);
    $operation->analyticLines()->createMany([
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '50'],
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '50'],
    ]);

    $response = $this->actingAs($this->owner)->get(route('account.show', [$this->account, 'page' => 1]));

    $response->assertOk()->assertSeeInOrder(['Cours échecs', "−0,03\u{a0}€", 'Cours piano', "−0,03\u{a0}€"]);
});

test('a new operation starts from the default breakdown of its account, without its archived axes', function () {
    $singing = $this->portfolio->analyticAxes()->create(['name' => 'Cours chant']);
    $this->account->analyticAxes()->attach([
        $this->chess->id => ['percentage' => '20', 'position' => 0],
        $this->piano->id => ['percentage' => '70', 'position' => 1],
        $singing->id => ['percentage' => '10', 'position' => 2],
    ]);
    $singing->delete();

    $html = (string) $this->actingAs($this->owner)->get(route('operation.create', $this->account))->assertOk()->getContent();

    expect(editorLines($html))->toBe([
        ['analytic_axis_id' => (string) $this->piano->id, 'percentage' => '70.00'],
        ['analytic_axis_id' => (string) $this->chess->id, 'percentage' => '20.00'],
    ]);
});

test('an operation edited keeps its own lines, whatever the breakdown of its account', function () {
    $this->account->analyticAxes()->attach($this->piano, ['percentage' => '100', 'position' => 0]);
    $operation = $this->account->operations()->create(lessonOperation([]));
    $operation->analyticLines()->create(['analytic_axis_id' => $this->chess->id, 'percentage' => '100']);
    $unsplit = $this->account->operations()->create(lessonOperation([]));

    $edit = fn ($operation) => editorLines((string) $this->actingAs($this->owner)->get(route('operation.edit', $operation))->assertOk()->getContent());

    expect($edit($operation))->toBe([['analytic_axis_id' => (string) $this->chess->id, 'percentage' => '100.00']])
        ->and($edit($unsplit))->toBe([]);
});

test('the lines of an operation show from the heaviest, then in the order of the account, then by name', function (string $route, string $model) {
    $singing = $this->portfolio->analyticAxes()->create(['name' => 'Cours chant']);
    $drawing = $this->portfolio->analyticAxes()->create(['name' => 'Cours dessin']);
    $this->account->analyticAxes()->attach([
        $this->piano->id => ['percentage' => '50', 'position' => 0],
        $this->chess->id => ['percentage' => '50', 'position' => 1],
    ]);
    $operation = $this->account->operations()->create([...lessonOperation([]), 'label' => 'Cours collectif']);
    $operation->analyticLines()->createMany([
        ['analytic_axis_id' => $drawing->id, 'percentage' => '10'],
        ['analytic_axis_id' => $this->chess->id, 'percentage' => '20'],
        ['analytic_axis_id' => $singing->id, 'percentage' => '30'],
        ['analytic_axis_id' => $this->piano->id, 'percentage' => '20'],
        ['analytic_axis_id' => $this->portfolio->analyticAxes()->create(['name' => 'Cours violon'])->id, 'percentage' => '20'],
    ]);

    $this->actingAs($this->owner)->get(route($route, [$this->{$model}, 'page' => 1]))
        ->assertOk()
        ->assertSeeInOrder(['Cours collectif', 'Cours chant', 'Cours piano', 'Cours échecs', 'Cours violon', 'Cours dessin'], escape: false);
})->with([
    'account' => ['account.show', 'account'],
    'ledger' => ['ledger.index', 'portfolio'],
]);
