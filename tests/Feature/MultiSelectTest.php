<?php

use App\View\Components\MultiSelect;

/**
 * Options of the tests: three accounts, the last one archived.
 *
 * @return list<array{value: int|string, label: string, hint?: string}>
 */
function multiSelectOptions(): array
{
    return [
        ['value' => 3, 'label' => 'Compte courant'],
        ['value' => 7, 'label' => 'Épargne'],
        ['value' => 9, 'label' => 'Ancien compte', 'hint' => 'archivé'],
    ];
}

test('the multiple selection keeps the values of its options, in their order, and labels them', function (mixed $value, array $selection, string $label) {
    $select = new MultiSelect(name: 'accounts', label: 'Comptes', options: multiSelectOptions(), value: $value, noun: 'comptes');

    expect($select->selection)->toBe($selection)
        ->and($select->triggerLabel)->toBe($label)
        ->and($select->config['value'])->toBe($selection);
})->with([
    'nothing' => [[], [], ''],
    'one option' => [['7'], ['7'], 'Épargne'],
    'numbers, unordered' => [[9, 3], ['3', '9'], '2 comptes'],
    'unknown and duplicate values' => [['7', '8', '7', 'x'], ['7'], 'Épargne'],
    'nested values' => [[['3'], null, '9'], ['9'], 'Ancien compte'],
    'not a list' => ['3', [], ''],
]);

test('the multiple selection sends its checked options as native checkboxes', function () {
    $view = $this->blade(
        '<x-multi-select name="accounts" label="Comptes" :options="$options" :value="[7]" placeholder="Tous les comptes" noun="comptes" x-model="accounts"/>',
        ['options' => multiSelectOptions()],
    );

    $view->assertSeeInOrder([
        'x-modelable="value"',
        'x-model="accounts"',
        'style="anchor-name: --multi-select-accounts"',
        'popovertarget="multi-select-accounts"',
        'aria-label="Comptes : Épargne"',
        'id="multi-select-accounts"',
        'popover="auto"',
        'name="accounts[]"',
        'value="3"',
        'Compte courant',
        'value="7"',
        'checked',
        'Épargne',
        'value="9"',
        'Ancien compte',
        'archivé',
    ], escape: false);

    expect(substr_count((string) $view, ' checked'))->toBe(1);
});

test('the multiple selection shows its placeholder without checked option', function () {
    $view = $this->blade('<x-multi-select name="accounts" label="Comptes" :options="$options" placeholder="Tous les comptes"/>', ['options' => multiSelectOptions()]);

    $view->assertSee('aria-label="Comptes : Tous les comptes"', escape: false);
});

test('the multiple selection offers a search field beyond its threshold of options', function () {
    $options = fn (int $count): array => array_map(fn (int $index): array => ['value' => $index, 'label' => "Compte {$index}"], range(1, $count));

    $this->blade('<x-multi-select name="accounts" label="Comptes" :options="$options"/>', ['options' => $options(MultiSelect::SEARCH_THRESHOLD)])
        ->assertDontSee('x-ref="search"', escape: false);

    $this->blade('<x-multi-select name="accounts" label="Comptes" :options="$options"/>', ['options' => $options(MultiSelect::SEARCH_THRESHOLD + 1)])
        ->assertSee('x-ref="search"', escape: false)
        ->assertSee('aria-label="Rechercher dans : Comptes"', escape: false);
});

test('the multiple selection escapes the labels of its options', function () {
    $view = $this->blade('<x-multi-select name="accounts" label="Comptes" :options="$options" :value="[1]"/>', [
        'options' => [['value' => 1, 'label' => '<script>alert("xss")</script>', 'hint' => '<b>x</b>']],
    ]);

    $view->assertDontSee('<script>alert("xss")</script>', escape: false)
        ->assertDontSee('<b>x</b>', escape: false)
        ->assertSee('&lt;script&gt;alert(', escape: false);
});
