<?php

use App\Models\Operation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->owner = User::factory()->create();
    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Portfolio']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->operation = $this->account->operations()->create(['label' => 'Loyer', 'amount' => '-750.50', 'delivery_at' => '2026-11-05']);

    $this->actingAs($this->owner);
});

/**
 * Pointage de l'opération en base, hors des portées globales.
 */
function storedTick(Operation $operation): bool
{
    return (bool) Operation::withoutGlobalScopes()->whereKey($operation->id)->value('is_ticked');
}

test('an operation is not ticked when written', function () {
    expect(storedTick($this->operation))->toBeFalse();
});

test('the tick box ticks and unticks an operation, answering in JSON', function () {
    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => true])
        ->assertOk()
        ->assertExactJson(['is_ticked' => true, 'message' => '« Loyer » pointée']);

    expect(storedTick($this->operation))->toBeTrue();

    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => false])
        ->assertOk()
        ->assertExactJson(['is_ticked' => false, 'message' => '« Loyer » dépointée']);

    expect(storedTick($this->operation))->toBeFalse();
});

test('the tick sets the state sent, whatever the state saved', function () {
    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => true]);
    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => true])->assertJson(['is_ticked' => true]);

    expect(storedTick($this->operation))->toBeTrue();
});

test('without JavaScript, the form of the tick box goes back to its page with a toast', function () {
    $this->from(route('account.show', $this->account))
        ->patch(route('operation.tick', $this->operation), ['is_ticked' => '1'])
        ->assertRedirect(route('account.show', $this->account))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => '« Loyer » pointée']);

    expect(storedTick($this->operation))->toBeTrue();

    $this->from(route('account.show', $this->account))
        ->patch(route('operation.tick', $this->operation), ['is_ticked' => '0']);

    expect(storedTick($this->operation))->toBeFalse();
});

test('the tick refuses a state that is not a boolean', function (mixed $state) {
    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => $state])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('is_ticked');

    expect(storedTick($this->operation))->toBeFalse();
})->with([
    'missing' => [null],
    'text' => ['yes'],
    'number' => [2],
]);

test('the operation of an archived account cannot be ticked', function () {
    $this->account->delete();

    $this->patchJson(route('operation.tick', $this->operation), ['is_ticked' => true])->assertNotFound();

    expect(storedTick($this->operation))->toBeFalse();
});

test('the tick changes no other field of the operation', function () {
    $this->patchJson(route('operation.tick', $this->operation), [
        'is_ticked' => true,
        'label' => 'Autre libellé',
        'amount' => '1.00',
        'delivery_at' => '2020-01-01',
    ])->assertOk();

    $operation = Operation::withoutGlobalScopes()->find($this->operation->id);

    expect($operation->label)->toBe('Loyer')
        ->and((string) $operation->amount)->toBe('-750.50')
        ->and($operation->delivery_at)->toStartWith('2026-11-05');
});

test('the operation form ticks and unticks the operation', function () {
    $fields = ['label' => 'Loyer', 'amount' => '-750.50', 'delivery_at' => '2026-11-05'];

    $this->put(route('operation.update', $this->operation), [...$fields, 'is_ticked' => '1'])->assertSessionHasNoErrors();
    expect(storedTick($this->operation))->toBeTrue();

    $this->put(route('operation.update', $this->operation), $fields)->assertSessionHasNoErrors();
    expect(storedTick($this->operation))->toBeTrue();

    $this->put(route('operation.update', $this->operation), [...$fields, 'is_ticked' => '0'])->assertSessionHasNoErrors();
    expect(storedTick($this->operation))->toBeFalse();

    $this->put(route('operation.update', $this->operation), [...$fields, 'is_ticked' => 'oui'])->assertSessionHasErrors('is_ticked');
});

test('a new operation can be written ticked', function () {
    $this->post(route('operation.store', $this->account), [
        'label' => 'Électricité',
        'amount' => '-60.00',
        'delivery_at' => '2026-11-06',
        'is_ticked' => '1',
    ])->assertSessionHasNoErrors();

    expect((bool) Operation::where('label', 'Électricité')->value('is_ticked'))->toBeTrue();
});

test('the operation form shows the tick of the operation', function () {
    $this->get(route('operation.edit', $this->operation))
        ->assertOk()
        ->assertSee('name="is_ticked"', escape: false);

    expect(preg_match('/id="is_ticked"[^>]*\schecked\s/s', $this->get(route('operation.edit', $this->operation))->getContent()))->toBe(0);

    $this->operation->forceFill(['is_ticked' => true])->save();

    expect(preg_match('/id="is_ticked"[^>]*\schecked\s/s', $this->get(route('operation.edit', $this->operation))->getContent()))->toBe(1);
});

test('the operations of an account show their tick box, read only once the account is archived', function () {
    $this->operation->forceFill(['is_ticked' => true])->save();

    $this->get(route('account.show', [$this->account, 'page' => 1]))
        ->assertOk()
        ->assertSee('Pointée ?')
        ->assertSee(route('operation.tick', $this->operation->id))
        ->assertSee('aria-label="Pointée : Loyer"', escape: false);

    $this->account->delete();

    $this->get(route('account.show', [$this->account, 'page' => 1]))
        ->assertOk()
        ->assertSee('aria-label="Pointée : Loyer"', escape: false)
        ->assertDontSee(route('operation.tick', $this->operation->id));
});
