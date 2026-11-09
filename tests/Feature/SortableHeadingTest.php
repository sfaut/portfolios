<?php

test('a sortable heading sorts its column in ascending order when the table is sorted on another', function () {
    $view = $this->blade('<x-sortable-heading column="amount" sort="label" direction="asc">Montant</x-sortable-heading>');

    $view->assertSeeInOrder([
        '<th scope="col" aria-sort="none"',
        'sort=amount&amp;dir=asc"',
        'data-fragment-link',
        'Montant',
        ', trier par ordre croissant',
    ], escape: false);
});

test('a sortable heading reverses the sort of its own column', function (string $direction, string $aria_sort, string $next, string $hint) {
    $view = $this->blade("<x-sortable-heading column=\"amount\" sort=\"amount\" direction=\"{$direction}\">Montant</x-sortable-heading>");

    $view->assertSeeInOrder(["aria-sort=\"{$aria_sort}\"", "sort=amount&amp;dir={$next}\"", 'text-indigo-700', $hint], escape: false);
})->with([
    'ascending' => ['asc', 'ascending', 'desc', ', trier par ordre décroissant'],
    'descending' => ['desc', 'descending', 'asc', ', trier par ordre croissant'],
]);
