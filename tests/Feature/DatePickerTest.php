<?php

use App\Enums\DatePreset;
use App\Support\DateRestriction;
use App\View\Components\DatePicker;
use Carbon\CarbonImmutable;

test('the date picker sends its selection in the fields of its mode', function (string $mode, string $value, array $fields) {
    $view = $this->blade("<x-date-picker name=\"period\" label=\"Période\" mode=\"{$mode}\" :value=\"{$value}\"/>");

    $view->assertSeeInOrder($fields, escape: false);
})->with([
    'single' => ['single', "'2026-11-09'", ['<input type="hidden" name="period" value="2026-11-09"']],
    'multiple' => ['multiple', "['2026-11-09', '2026-10-28']", ['<template x-for="day in value"', '<input type="hidden" name="period[]"']],
    'range' => ['range', "['start' => '2026-10-28', 'end' => '2026-11-09']", [
        '<input type="hidden" name="period[start]" value="2026-10-28"',
        '<input type="hidden" name="period[end]" value="2026-11-09"',
    ]],
]);

test('the date picker normalizes its selection and labels it', function (string $mode, mixed $value, mixed $selection, string $label) {
    $picker = new DatePicker(name: 'period', label: 'Période', mode: $mode, value: $value, restriction: new DateRestriction(min: '2025-01-01', max: '2026-12-31'));

    expect($picker->selection)->toBe($selection)
        ->and($picker->triggerLabel)->toBe($label)
        ->and($picker->config['value'])->toBe($selection);
})->with([
    'single' => ['single', '2026-11-09', '2026-11-09', '9 nov. 2026'],
    'single, not a real day' => ['single', '2026-02-30', '', ''],
    'single, out of bounds' => ['single', '2027-01-01', '', ''],
    'multiple, sorted' => ['multiple', ['2026-11-09', 'x', '2026-02-14', '2026-11-09'], ['2026-02-14', '2026-11-09'], '2 jours'],
    'multiple, one day' => ['multiple', ['2026-02-14'], ['2026-02-14'], '14 févr. 2026'],
    'range, reordered' => ['range', ['start' => '2026-11-09', 'end' => '2025-10-28'], ['start' => '2025-10-28', 'end' => '2026-11-09'], '28 oct. 2025 – 9 nov. 2026'],
    'range, start only' => ['range', ['start' => '2026-11-09'], ['start' => '2026-11-09', 'end' => ''], 'Depuis le 9 nov. 2026'],
    'range, one day' => ['range', ['start' => '2026-11-09', 'end' => '2026-11-09'], ['start' => '2026-11-09', 'end' => '2026-11-09'], '9 nov. 2026'],
    'range, empty' => ['range', null, ['start' => '', 'end' => ''], ''],
]);

test('the date picker labels a selection as the JavaScript component does', function (string $mode, string|array $value, string $label) {
    $picker = new DatePicker(name: 'period', label: 'Période', mode: $mode, value: $value);

    expect($picker->triggerLabel)->toBe($label);
})->with(fn (): array => array_map(
    fn (array $case): array => [$case['mode'], $case['value'], $case['label']],
    json_decode(file_get_contents(dirname(__DIR__).'/fixtures/date-picker-labels.json'), associative: true, flags: JSON_THROW_ON_ERROR),
));

test('the date picker drops the initial days its restriction forbids', function (string $mode, mixed $value, mixed $selection) {
    $restriction = new DateRestriction(allow: ['2026-01-01/2026-06-30', '2026-11-09'], deny: ['2026-03-15']);
    $picker = new DatePicker(name: 'period', label: 'Période', mode: $mode, value: $value, restriction: $restriction);

    expect($picker->selection)->toBe($selection);
})->with([
    'single, black list' => ['single', '2026-03-15', ''],
    'single, out of the white list' => ['single', '2026-10-01', ''],
    'multiple' => ['multiple', ['2026-11-09', '2026-03-15', '2026-07-14', '2026-02-14'], ['2026-02-14', '2026-11-09']],
    'range, forbidden end' => ['range', ['start' => '2026-02-14', 'end' => '2026-03-15'], ['start' => '2026-02-14', 'end' => '']],
    'range, around a forbidden day' => ['range', ['start' => '2026-02-14', 'end' => '2026-04-01'], ['start' => '2026-02-14', 'end' => '2026-04-01']],
]);

test('the date picker sends a copy of its restriction to the browser, free without restriction', function () {
    $restriction = new DateRestriction(allow: ['2026-01-01/2026-06-30'], deny: ['2026-03-15']);

    expect((new DatePicker(name: 'day', label: 'Jour', restriction: $restriction))->config['restriction'])->toBe($restriction->toArray())
        ->and((new DatePicker(name: 'day', label: 'Jour'))->config['restriction'])->toBe(['min' => '', 'max' => '', 'allow' => null, 'deny' => []]);
});

test('the date picker falls back on the single mode', function () {
    $picker = new DatePicker(name: 'day', label: 'Jour', mode: 'unknown', value: '2026-11-09');

    expect($picker->mode)->toBe('single')
        ->and($picker->selection)->toBe('2026-11-09');
});

test('the date picker shows its placeholder without selection, and opens its panel from its trigger', function () {
    $view = $this->blade('<x-date-picker name="day" label="Date d\'écriture" placeholder="Toutes les dates" x-model="day"/>');

    $view->assertSeeInOrder([
        'x-modelable="value"',
        'x-model="day"',
        'x-bind:popovertarget="$id(\'date-picker\')"',
        'aria-label="Date d&#039;écriture : Toutes les dates"',
        'Toutes les dates',
        'x-bind:id="$id(\'date-picker\')"',
        'popover="auto"',
        'role="dialog"',
    ], escape: false);
});

test('the date presets are ranges around the current day', function (DatePreset $preset, string $start, string $end) {
    expect($preset->range(CarbonImmutable::parse('2026-03-31')))->toBe(['start' => $start, 'end' => $end]);
})->with([
    'today' => [DatePreset::Today, '2026-03-31', '2026-03-31'],
    'this month' => [DatePreset::ThisMonth, '2026-03-01', '2026-03-31'],
    'last month, shorter' => [DatePreset::LastMonth, '2026-02-01', '2026-02-28'],
    'this year' => [DatePreset::ThisYear, '2026-01-01', '2026-12-31'],
    'last twelve months' => [DatePreset::LastTwelveMonths, '2025-04-01', '2026-03-31'],
]);

test('the date picker offers its presets in range mode, kept within the bounds of its restriction', function () {
    $this->travelTo('2026-11-09 10:00:00');

    $picker = new DatePicker(
        name: 'period',
        label: 'Période',
        mode: 'range',
        restriction: new DateRestriction(min: '2026-10-15', max: '2026-11-05'),
        presets: DatePreset::cases(),
    );

    // Today is after the bounds, hence dropped; the other ranges are cut to the bounds.
    expect($picker->ranges)->toBe([
        ['label' => 'Ce mois', 'start' => '2026-11-01', 'end' => '2026-11-05'],
        ['label' => 'Mois précédent', 'start' => '2026-10-15', 'end' => '2026-10-31'],
        ['label' => 'Cette année', 'start' => '2026-10-15', 'end' => '2026-11-05'],
        ['label' => '12 derniers mois', 'start' => '2026-10-15', 'end' => '2026-11-05'],
    ])->and($picker->config['presets'])->toBe($picker->ranges);
});

test('the date picker drops a preset whose cut ends are forbidden, and all presets out of the range mode', function () {
    $this->travelTo('2026-11-09 10:00:00');

    $forbidden = new DatePicker(name: 'period', label: 'Période', mode: 'range', restriction: new DateRestriction(deny: ['2026-11-09']), presets: [DatePreset::Today, DatePreset::ThisMonth]);
    $single = new DatePicker(name: 'day', label: 'Jour', presets: DatePreset::cases());

    expect(array_column($forbidden->ranges, 'label'))->toBe(['Ce mois'])
        ->and($single->ranges)->toBe([]);
});

test('the date picker lists its presets before its panels', function () {
    $view = $this->blade('<x-date-picker name="period" label="Période" mode="range" :presets="$presets"/>', ['presets' => DatePreset::cases()]);

    $view->assertSeeInOrder(['aria-label="Plages préréglées"', 'x-on:click="choosePreset(preset)"', 'x-for="panel in panels"'], escape: false);

    $this->blade('<x-date-picker name="period" label="Période" mode="range"/>')->assertDontSee('Plages préréglées');
});
