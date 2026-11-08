<?php

use App\Models\User;
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
