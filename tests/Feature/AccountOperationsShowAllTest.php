<?php

use App\Http\Controllers\AccountController;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
    $this->account = $this->user->portfolios()->create(['name' => 'Portfolio'])
        ->accounts()->create(['name' => 'Account']);

    foreach (range(1, 25) as $number) {
        $this->account->operations()->create([
            'label' => sprintf('Operation %02d', $number),
            'amount' => '-10.00',
            'delivery_at' => sprintf('2026-10-%02d', $number),
        ]);
    }
});

test('the operations of an account are paginated by default, from the last page', function () {
    $this->actingAs($this->user)->get(route('account.show', $this->account))
        ->assertRedirect(route('account.show', [$this->account, 'page' => 2]));

    $this->actingAs($this->user)->get(route('account.show', [$this->account, 'page' => 2]))
        ->assertOk()
        ->assertSee('Page 2')
        ->assertSee('Operation 25')
        ->assertDontSee('Operation 05')
        ->assertSeeInOrder(['name="all"', 'value="1"', 'aria-pressed="false"', '>Tout</button>'], escape: false);
});

test('the all button keeps the preference in session and reloads the page without it', function (string $all, bool $show_all) {
    $this->actingAs($this->user)
        ->withSession(['alert' => 'Opération modifiée avec succès.', '_flash' => ['old' => [], 'new' => ['alert']]])
        ->get(route('account.show', [$this->account, 'all' => $all]))
        ->assertRedirect(route('account.show', $this->account))
        ->assertSessionHas(AccountController::SHOW_ALL_OPERATIONS, $show_all)
        ->assertSessionHas('alert', 'Opération modifiée avec succès.');
})->with([
    'pressed' => ['1', true],
    'released' => ['0', false],
]);

test('all the operations are shown at once while the all button is pressed', function () {
    $response = $this->actingAs($this->user)
        ->withSession([AccountController::SHOW_ALL_OPERATIONS => true])
        ->get(route('account.show', $this->account));

    $response->assertOk()
        ->assertSeeInOrder(['Operation 01', 'Operation 25'])
        ->assertSee('25 opérations')
        ->assertDontSee('Page 1')
        ->assertSeeInOrder(['name="all"', 'value="0"', 'aria-pressed="true"', '>Tout</button>'], escape: false)
        ->assertSee('scrollIntoView', escape: false);
});

test('the pagination comes back, from the last page, once the all button is released', function () {
    $this->actingAs($this->user)
        ->withSession([AccountController::SHOW_ALL_OPERATIONS => false])
        ->get(route('account.show', $this->account))
        ->assertRedirect(route('account.show', [$this->account, 'page' => 2]));
});
