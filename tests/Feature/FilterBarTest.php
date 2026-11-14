<?php

test('a filter field labels the input it names', function () {
    $this->blade('<x-filter-field label="Libellé" for="ledger-label"><input id="ledger-label"></x-filter-field>')
        ->assertSee('<label for="ledger-label"', escape: false)
        ->assertDontSee('aria-hidden', escape: false);
});

test('a filter field hides its label from screen readers when its control names itself', function () {
    $this->blade('<x-filter-field label="Comptes"><button aria-label="Comptes : tous"></button></x-filter-field>')
        ->assertSee('aria-hidden="true">Comptes</span>', escape: false)
        ->assertDontSee('<label', escape: false);
});

test('a filter bar shows its status only when given one', function () {
    $this->blade('<x-filter-bar method="get"><input name="q"></x-filter-bar>')
        ->assertSee('<form', escape: false)
        ->assertSee('role="search"', escape: false)
        ->assertDontSee('justify-end', escape: false);

    $this->blade('<x-filter-bar method="get"><x-slot:status>2 comptes</x-slot:status></x-filter-bar>')
        ->assertSeeInOrder(['justify-end', '2 comptes'], escape: false);
});
