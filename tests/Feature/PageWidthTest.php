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

test('every page shares the width of the header, under a bar as wide as the ledger', function (Closure $url) {
    $html = (string) $this->get($url->call($this))->assertOk()->getContent();

    preg_match('/<nav aria-label="Navigation principale".*?<\/nav>/s', $html, $bar);

    expect(substr_count($html, AppLayout::CONTAINER))->toBe(2)
        ->and(substr_count($html, AppLayout::WIDE_CONTAINER))->toBe(1)
        ->and($bar[0])->toContain(AppLayout::WIDE_CONTAINER);
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

test('a form keeps a reading width, centered in the page', function () {
    $this->get(route('account.create', $this->portfolio))
        ->assertOk()
        ->assertSee('<div class="max-w-2xl mx-auto flex flex-col gap-6">', escape: false);
});
