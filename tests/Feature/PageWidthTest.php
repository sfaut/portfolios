<?php

use App\Models\User;
use App\View\Components\AppLayout;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->admin()->create();
    $this->portfolio = $this->user->portfolios()->create(['name' => 'Portfolio']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);

    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()]);
});

test('every page shares the width and the margins of the bar and the header', function (Closure $url) {
    $html = (string) $this->get($url->call($this))->assertOk()->getContent();

    expect(substr_count($html, AppLayout::CONTAINER))->toBe(3)
        ->and($html)->not->toContain(AppLayout::WIDE_CONTAINER);
})->with([
    'portfolios' => [fn () => route('portfolio.index')],
    'dashboard' => [fn () => route('portfolio.dashboard', $this->portfolio)],
    'accounts' => [fn () => route('portfolio.show', $this->portfolio)],
    'account' => [fn () => route('account.show', [$this->account, 'page' => 1])],
    'account creation' => [fn () => route('account.create', $this->portfolio)],
    'analytic axes' => [fn () => route('analytic-axis.index', $this->portfolio)],
    'profile' => [fn () => route('profile.edit')],
    'server status' => [fn () => route('admin.server')],
]);

test('a form keeps a reading width, aligned on the left of the page', function () {
    $this->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertSee('<div class="max-w-2xl flex flex-col gap-6">', escape: false)
        ->assertDontSee('mx-auto flex flex-col', escape: false);
});
