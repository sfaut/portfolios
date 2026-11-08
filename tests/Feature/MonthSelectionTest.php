<?php

use App\Rules\MonthSelection;
use App\Support\MonthRestriction;
use Illuminate\Support\Facades\Validator;

/**
 * Errors of the period field once validated by the MonthSelection rule, [] when accepted.
 *
 * @param  string  $mode  Mode of the month picker.
 * @param  array<string, mixed>  $data  Data sent.
 * @return list<string>
 */
function monthSelectionErrors(string $mode, array $data): array
{
    $restriction = new MonthRestriction(min: '2025-01', max: '2026-12', allow: ['2025-01/2025-06', '2026-01/2026-12'], deny: ['2025-03', '2026-08']);

    return Validator::make($data, ['period' => [new MonthSelection($mode, $restriction)]], attributes: ['period' => 'période'])
        ->errors()->get('period');
}

test('the single month selection accepts a choosable month', function () {
    expect(monthSelectionErrors('single', ['period' => '2025-02']))->toBe([]);
});

test('the single month selection refuses a forbidden or malformed month', function (mixed $value) {
    expect(monthSelectionErrors('single', ['period' => $value]))->toBe(['Le champ « période » doit être un mois autorisé.']);
})->with([
    'black list' => ['2025-03'],
    'out of the white list' => ['2025-09'],
    'out of the bounds' => ['2027-01'],
    'malformed' => ['2025-2'],
    'list' => [['2025-02']],
    'range' => [['start' => '2025-02', 'end' => '2025-04']],
    'number' => [202502],
]);

test('the multiple month selection accepts a list of choosable months', function () {
    expect(monthSelectionErrors('multiple', ['period' => ['2026-11', '2025-02', '2026-01']]))->toBe([]);
});

test('the multiple month selection refuses a value that is not a list of choosable months', function (mixed $value, string $message) {
    expect(monthSelectionErrors('multiple', ['period' => $value]))->toBe([$message]);
})->with([
    'single month' => ['2025-02', 'Le champ « période » doit être une liste de mois.'],
    'keyed array' => [['first' => '2025-02'], 'Le champ « période » doit être une liste de mois.'],
    'black list' => [['2025-02', '2026-08'], 'Le champ « période » ne doit contenir que des mois autorisés.'],
    'out of the white list' => [['2025-07'], 'Le champ « période » ne doit contenir que des mois autorisés.'],
    'malformed' => [['2025-02', 'x'], 'Le champ « période » ne doit contenir que des mois autorisés.'],
    'nested list' => [[['2025-02']], 'Le champ « période » ne doit contenir que des mois autorisés.'],
    'duplicate' => [['2025-02', '2025-02'], 'Le champ « période » contient un mois en double.'],
]);

test('the month range selection accepts choosable ends, even around forbidden months', function (array $value) {
    expect(monthSelectionErrors('range', ['period' => $value]))->toBe([]);
})->with([
    'around the black list and out of the white list' => [['start' => '2025-02', 'end' => '2026-09']],
    'one month' => [['start' => '2025-04', 'end' => '2025-04']],
]);

test('the month range selection refuses a value that is not a range of choosable months', function (mixed $value, string $message) {
    expect(monthSelectionErrors('range', ['period' => $value]))->toBe([$message]);
})->with([
    'single month' => ['2025-02', 'Le champ « période » doit être une plage de mois (début et fin).'],
    'list' => [['2025-02', '2025-04'], 'Le champ « période » doit être une plage de mois (début et fin).'],
    'unknown key' => [['start' => '2025-02', 'end' => '2025-04', 'step' => '1'], 'Le champ « période » doit être une plage de mois (début et fin).'],
    'start only' => [['start' => '2025-02', 'end' => ''], 'Le début et la fin du champ « période » doivent être des mois autorisés.'],
    'end only' => [['end' => '2025-04'], 'Le début et la fin du champ « période » doivent être des mois autorisés.'],
    'forbidden start' => [['start' => '2025-03', 'end' => '2025-04'], 'Le début et la fin du champ « période » doivent être des mois autorisés.'],
    'forbidden end' => [['start' => '2025-02', 'end' => '2026-08'], 'Le début et la fin du champ « période » doivent être des mois autorisés.'],
    'list as end' => [['start' => '2025-02', 'end' => ['2025-04']], 'Le début et la fin du champ « période » doivent être des mois autorisés.'],
    'reversed' => [['start' => '2025-04', 'end' => '2025-02'], 'Le début du champ « période » doit précéder sa fin.'],
]);

test('the month selection leaves an empty value to the required rule', function (string $mode, mixed $value) {
    expect(monthSelectionErrors($mode, ['period' => $value]))->toBe([]);

    $errors = Validator::make(['period' => $value], ['period' => ['required', new MonthSelection($mode)]])->errors();

    expect($errors->has('period'))->toBe($mode !== 'range');
})->with([
    'single' => ['single', ''],
    'multiple' => ['multiple', []],
    'range' => ['range', ['start' => '', 'end' => '']],
]);

test('the month selection is free without restriction', function () {
    $errors = Validator::make(['period' => '1999-01'], ['period' => [new MonthSelection('single')]])->errors();

    expect($errors->isEmpty())->toBeTrue();
});

test('the month selection rejects an unknown mode', function () {
    expect(fn () => new MonthSelection('weekly'))->toThrow(InvalidArgumentException::class, '« weekly »');
});
