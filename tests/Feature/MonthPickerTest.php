<?php

use App\View\Components\MonthPicker;

test('the month picker sends its selection in the fields of its mode', function (string $mode, string $value, array $fields) {
    $view = $this->blade("<x-month-picker name=\"period\" label=\"Période\" mode=\"{$mode}\" :value=\"{$value}\"/>");

    $view->assertSeeInOrder($fields, escape: false);
})->with([
    'single' => ['single', "'2026-11'", ['<input type="hidden" name="period" value="2026-11"']],
    'multiple' => ['multiple', "['2026-11', '2026-10']", ['<template x-for="month in value"', '<input type="hidden" name="period[]"']],
    'range' => ['range', "['start' => '2026-10', 'end' => '2026-11']", [
        '<input type="hidden" name="period[start]" value="2026-10"',
        '<input type="hidden" name="period[end]" value="2026-11"',
    ]],
]);

test('the month picker normalizes its selection and labels it', function (string $mode, mixed $value, mixed $selection, string $label) {
    $picker = new MonthPicker(name: 'period', label: 'Période', mode: $mode, value: $value, months: null, min: '2025-01', max: '2026-12');

    expect($picker->selection)->toBe($selection)
        ->and($picker->triggerLabel)->toBe($label)
        ->and($picker->config['value'])->toBe($selection);
})->with([
    'single' => ['single', '2026-11', '2026-11', 'Novembre 2026'],
    'single, invalid' => ['single', '2026-13', '', ''],
    'single, out of bounds' => ['single', '2027-01', '', ''],
    'multiple, sorted' => ['multiple', ['2026-11', 'x', '2026-02', '2026-11'], ['2026-02', '2026-11'], '2 mois'],
    'multiple, one month' => ['multiple', ['2026-02'], ['2026-02'], 'Février 2026'],
    'range, reordered' => ['range', ['start' => '2026-11', 'end' => '2025-10'], ['start' => '2025-10', 'end' => '2026-11'], 'Octobre 2025 – novembre 2026'],
    'range, start only' => ['range', ['start' => '2026-11'], ['start' => '2026-11', 'end' => ''], 'Depuis novembre 2026'],
    'range, one month' => ['range', ['start' => '2026-11', 'end' => '2026-11'], ['start' => '2026-11', 'end' => '2026-11'], 'Novembre 2026'],
    'range, empty' => ['range', null, ['start' => '', 'end' => ''], ''],
]);

test('the month picker keeps only the given months, and takes its bounds from them', function () {
    $picker = new MonthPicker(name: 'month', label: 'Mois', value: '2026-10', months: ['2026-11', '2025-03', 'x']);

    expect($picker->config)->toMatchArray(['months' => ['2025-03', '2026-11'], 'min' => '2025-03', 'max' => '2026-11', 'value' => '']);
});

test('the month picker falls back on the single mode', function () {
    $picker = new MonthPicker(name: 'month', label: 'Mois', mode: 'unknown', value: '2026-11');

    expect($picker->mode)->toBe('single')
        ->and($picker->selection)->toBe('2026-11');
});

test('the month picker shows its placeholder without selection, and opens its panel from its trigger', function () {
    $view = $this->blade('<x-month-picker name="month" label="Mois d\'opération" placeholder="Tous les mois" x-model="month"/>');

    $view->assertSeeInOrder([
        'x-modelable="value"',
        'x-model="month"',
        'x-bind:popovertarget="$id(\'month-picker\')"',
        'aria-label="Mois d&#039;opération : Tous les mois"',
        'Tous les mois',
        'x-bind:id="$id(\'month-picker\')"',
        'popover="auto"',
        'role="dialog"',
    ], escape: false);
});
