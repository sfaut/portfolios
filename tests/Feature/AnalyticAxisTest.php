<?php

use App\Enums\AxisColor;
use App\Models\User;
use App\Support\AxisIcons;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();

    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->axis = $this->portfolio->analyticAxes()->create(['name' => 'Cours piano']);
});

test('the analytic axes of a portfolio are listed, archived ones in their own section', function () {
    $this->portfolio->analyticAxes()->create(['name' => 'Cours échecs'])->delete();

    $response = $this->actingAs($this->owner)->get(route('analytic-axis.index', $this->portfolio));

    $response->assertOk()
        ->assertSeeInOrder(['Cours piano', 'Axes archivés', '>1</span>', 'Cours échecs'], escape: false);
});

test('the portfolio page links to its analytic axes', function () {
    $response = $this->actingAs($this->owner)->get(route('portfolio.show', $this->portfolio));

    $response->assertOk()->assertSee(route('analytic-axis.index', $this->portfolio));
});

test('an analytic axis can be created', function () {
    $response = $this->actingAs($this->owner)->post(route('analytic-axis.store', $this->portfolio), [
        'name' => 'Cours échecs',
        'description' => 'Club du mercredi',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('analytic-axis.index', $this->portfolio))
        ->assertSessionHas('message', "L'axe analytique « Cours échecs » a été créé.");

    $this->assertDatabaseHas('analytic_axes', [
        'portfolio_id' => $this->portfolio->id,
        'name' => 'Cours échecs',
        'description' => 'Club du mercredi',
    ]);
});

test('an analytic axis can be updated', function () {
    $response = $this->actingAs($this->owner)->put(route('analytic-axis.update', $this->axis), [
        'name' => 'Cours de piano',
        'description' => '',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('analytic-axis.index', $this->portfolio));

    expect($this->axis->fresh()->name)->toBe('Cours de piano');
});

test('the name of an analytic axis is unique within its portfolio, archived axes included', function () {
    $this->axis->delete();

    $response = $this->actingAs($this->owner)->post(route('analytic-axis.store', $this->portfolio), [
        'name' => 'Cours piano',
        'description' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('the name of an analytic axis can be reused in another portfolio', function () {
    $other_portfolio = $this->owner->portfolios()->create(['name' => 'Autre portfolio']);

    $response = $this->actingAs($this->owner)->post(route('analytic-axis.store', $other_portfolio), [
        'name' => 'Cours piano',
        'description' => '',
    ]);

    $response->assertSessionHasNoErrors();

    expect($other_portfolio->analyticAxes()->count())->toBe(1);
});

test('an analytic axis keeps its own name on update', function () {
    $response = $this->actingAs($this->owner)->put(route('analytic-axis.update', $this->axis), [
        'name' => 'Cours piano',
        'description' => 'Nouvelle description',
    ]);

    $response->assertSessionHasNoErrors();
});

test('an analytic axis can be archived then restored', function () {
    $this->actingAs($this->owner)
        ->delete(route('analytic-axis.destroy', $this->axis))
        ->assertRedirect(route('analytic-axis.index', $this->portfolio))
        ->assertSessionHas('message', "L'axe analytique « Cours piano » a été archivé.");

    expect($this->axis->fresh()->trashed())->toBeTrue();

    $this->actingAs($this->owner)
        ->get(route('analytic-axis.edit', $this->axis))
        ->assertOk()
        ->assertSee(route('analytic-axis.restore', $this->axis));

    $this->actingAs($this->owner)
        ->patch(route('analytic-axis.restore', $this->axis))
        ->assertRedirect(route('analytic-axis.index', $this->portfolio))
        ->assertSessionHas('message', "L'axe analytique « Cours piano » a été désarchivé.");

    expect($this->axis->fresh()->trashed())->toBeFalse();
});

test('an analytic axis takes the default icon and color when none is sent', function () {
    expect($this->axis->fresh())
        ->icon->toBe(AxisIcons::DEFAULT)
        ->color->toBe(AxisColor::DEFAULT);
});

test('an analytic axis is created and updated with its icon and color', function () {
    $this->actingAs($this->owner)->post(route('analytic-axis.store', $this->portfolio), [
        'name' => 'Cours échecs',
        'description' => '',
        'icon' => 'chess-knight',
        'color' => 'amber',
    ])->assertSessionHasNoErrors();

    $axis = $this->portfolio->analyticAxes()->where('name', 'Cours échecs')->sole();

    expect($axis)->icon->toBe('chess-knight')->color->toBe(AxisColor::Amber);

    $this->actingAs($this->owner)->put(route('analytic-axis.update', $axis), [
        'name' => 'Cours échecs',
        'description' => '',
        'icon' => 'piano',
        'color' => 'gray',
    ])->assertSessionHasNoErrors();

    expect($axis->fresh())->icon->toBe('piano')->color->toBe(AxisColor::Gray);
});

test('an icon outside the selection or an unknown color is refused', function (array $data, string $field) {
    $response = $this->actingAs($this->owner)->put(route('analytic-axis.update', $this->axis), [
        'name' => 'Cours piano',
        'description' => '',
        ...$data,
    ]);

    $response->assertSessionHasErrors($field);

    expect($this->axis->fresh())->icon->toBe(AxisIcons::DEFAULT)->color->toBe(AxisColor::DEFAULT);
})->with([
    'icon outside the selection' => [['icon' => 'skull'], 'icon'],
    'empty icon' => [['icon' => ''], 'icon'],
    'icon as an array' => [['icon' => ['piano']], 'icon'],
    'unknown color' => [['color' => 'slate'], 'color'],
    'empty color' => [['color' => ''], 'color'],
]);

test('the axis form offers the colors and the icons, the current ones checked', function () {
    $this->axis->update(['icon' => 'chess-rook', 'color' => 'teal']);

    $response = $this->actingAs($this->owner)->get(route('analytic-axis.edit', $this->axis));
    $html = (string) $response->getContent();

    $response->assertOk()
        ->assertSee('x-data="axisAppearance(', escape: false)
        ->assertSee('Sarcelle');

    expect(substr_count($html, 'name="color"'))->toBe(count(AxisColor::cases()))
        ->and(substr_count($html, 'name="icon"'))->toBe(count(AxisIcons::names()))
        ->and($html)->toMatch('/value="teal"\s+checked/')
        ->and($html)->toMatch('/value="chess-rook"\s+checked/');
});

test('the list of axes shows each axis in its colors', function () {
    $this->axis->update(['icon' => 'piano', 'color' => 'rose']);

    $this->actingAs($this->owner)->get(route('analytic-axis.index', $this->portfolio))
        ->assertOk()
        ->assertSee(AxisColor::Rose->badgeClasses());
});
