<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('a page header puts the breadcrumb above the title, the description below it and the actions after', function () {
    $this->blade(
        '<x-page-header :breadcrumb="[\'/comptes\' => \'Comptes\']">Compte courant'
        .'<x-slot:description>Les opérations du compte.</x-slot:description>'
        .'<x-slot:actions><a href="/modifier">Modifier le compte</a></x-slot:actions>'
        .'</x-page-header>'
    )->assertSeeInOrder([
        "aria-label=\"Fil d'Ariane\"", 'href="'.route('portfolio.index').'"', 'Portfolios', 'href="/comptes"', 'Comptes',
        '<h2', 'Compte courant', '</h2>',
        '<p', 'Les opérations du compte.', '</p>',
        'href="/modifier"',
    ], escape: false);
});

test('a page header leaves out the rows it is not given', function () {
    $this->blade('<x-page-header>Grand livre</x-page-header>')
        ->assertSee('Grand livre</h2>', escape: false)
        ->assertDontSee('<p', escape: false)
        ->assertDontSee('gap-3', escape: false);
});

/**
 * Liens du fil d'Ariane d'une page, dans l'ordre : adresse => texte.
 *
 * @return array<string, string>
 */
function breadcrumbLinks(string $html): array
{
    if (preg_match('/<nav aria-label="Fil d(?:\'|&#039;)Ariane".*?<\/nav>/s', $html, $nav) !== 1) {
        return [];
    }

    preg_match_all('/<a href="([^"]+)"[^>]*>([^<]*)<\/a>/', $nav[0], $links, PREG_SET_ORDER);

    return array_column(array_map(fn (array $link): array => [$link[1], html_entity_decode($link[2])], $links), 1, 0);
}

describe('the breadcrumb of a page', function () {
    beforeEach(function () {
        Mail::fake();

        $this->user = User::factory()->create();
        $this->portfolio = $this->user->portfolios()->create(['name' => 'Famille']);
        $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
        $this->actingAs($this->user);
    });

    test('starts from the portfolio list, then passes through the portfolio of the page', function () {
        $html = $this->get(route('account.show', [$this->account, 'page' => 1]))->getContent();

        expect(breadcrumbLinks($html))->toBe([
            route('portfolio.index') => 'Portfolios',
            route('portfolio.dashboard', $this->portfolio) => 'Famille',
            route('portfolio.show', $this->portfolio) => 'Comptes',
        ]);
    });

    test('leads a first level page of a portfolio back to its dashboard', function () {
        $html = $this->get(route('ledger.index', $this->portfolio))->getContent();

        expect(breadcrumbLinks($html))->toBe([
            route('portfolio.index') => 'Portfolios',
            route('portfolio.dashboard', $this->portfolio) => 'Famille',
        ]);
    });

    test('leads the dashboard, home of its portfolio, back to the portfolio list only', function () {
        $html = $this->get(route('portfolio.dashboard', $this->portfolio))->getContent();

        expect(breadcrumbLinks($html))->toBe([route('portfolio.index') => 'Portfolios']);
    });

    test('leads a page outside a portfolio back to the portfolio list', function (string $route) {
        $html = $this->withSession(['auth.password_confirmed_at' => time()])->get(route($route))->getContent();

        expect(breadcrumbLinks($html))->toBe([route('portfolio.index') => 'Portfolios']);
    })->with(['portfolio.create', 'profile.edit', 'profile.preferences', 'profile.password', 'profile.account']);

    test('is absent from the portfolio list, its root', function () {
        $this->get(route('portfolio.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Fil d', escape: false);
    });
});
