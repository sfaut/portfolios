<?php

use App\Rules\DateSelection;
use App\Support\DateRestriction;
use Illuminate\Support\Facades\Validator;

/**
 * Errors of the period field once validated by the DateSelection rule, [] when accepted.
 *
 * @param  string  $mode  Mode of the date picker.
 * @param  array<string, mixed>  $data  Data sent.
 * @return list<string>
 */
function dateSelectionErrors(string $mode, array $data): array
{
    $restriction = new DateRestriction(
        min: '2026-01-01',
        max: '2026-12-31',
        allow: ['2026-01-01/2026-06-30', '2026-09-01/2026-12-31'],
        deny: ['2026-03-15', '2026-11-11'],
    );

    return Validator::make($data, ['period' => [new DateSelection($mode, $restriction)]], attributes: ['period' => 'période'])
        ->errors()->get('period');
}

test('the single date selection accepts a choosable day', function () {
    expect(dateSelectionErrors('single', ['period' => '2026-02-14']))->toBe([]);
});

test('the single date selection refuses a forbidden or malformed day', function (mixed $value) {
    expect(dateSelectionErrors('single', ['period' => $value]))->toBe(['Le champ « période » doit être une date autorisée.']);
})->with([
    'black list' => ['2026-03-15'],
    'out of the white list' => ['2026-07-14'],
    'out of the bounds' => ['2027-01-01'],
    'malformed' => ['2026-2-14'],
    'not a real day' => ['2026-02-30'],
    'list' => [['2026-02-14']],
    'range' => [['start' => '2026-02-14', 'end' => '2026-04-01']],
    'number' => [20260214],
]);

test('the multiple date selection accepts a list of choosable days', function () {
    expect(dateSelectionErrors('multiple', ['period' => ['2026-11-09', '2026-02-14', '2026-01-01']]))->toBe([]);
});

test('the multiple date selection refuses a value that is not a list of choosable days', function (mixed $value, string $message) {
    expect(dateSelectionErrors('multiple', ['period' => $value]))->toBe([$message]);
})->with([
    'single day' => ['2026-02-14', 'Le champ « période » doit être une liste de dates.'],
    'keyed array' => [['first' => '2026-02-14'], 'Le champ « période » doit être une liste de dates.'],
    'black list' => [['2026-02-14', '2026-11-11'], 'Le champ « période » ne doit contenir que des dates autorisées.'],
    'out of the white list' => [['2026-07-14'], 'Le champ « période » ne doit contenir que des dates autorisées.'],
    'malformed' => [['2026-02-14', 'x'], 'Le champ « période » ne doit contenir que des dates autorisées.'],
    'nested list' => [[['2026-02-14']], 'Le champ « période » ne doit contenir que des dates autorisées.'],
    'duplicate' => [['2026-02-14', '2026-02-14'], 'Le champ « période » contient une date en double.'],
]);

test('the date range selection accepts choosable ends, even around forbidden days', function (array $value) {
    expect(dateSelectionErrors('range', ['period' => $value]))->toBe([]);
})->with([
    'around the black list and out of the white list' => [['start' => '2026-02-14', 'end' => '2026-09-30']],
    'one day' => [['start' => '2026-04-01', 'end' => '2026-04-01']],
]);

test('the date range selection refuses a value that is not a range of choosable days', function (mixed $value, string $message) {
    expect(dateSelectionErrors('range', ['period' => $value]))->toBe([$message]);
})->with([
    'single day' => ['2026-02-14', 'Le champ « période » doit être une plage de dates (début et fin).'],
    'list' => [['2026-02-14', '2026-04-01'], 'Le champ « période » doit être une plage de dates (début et fin).'],
    'unknown key' => [['start' => '2026-02-14', 'end' => '2026-04-01', 'step' => '1'], 'Le champ « période » doit être une plage de dates (début et fin).'],
    'start only' => [['start' => '2026-02-14', 'end' => ''], 'Le début et la fin du champ « période » doivent être des dates autorisées.'],
    'end only' => [['end' => '2026-04-01'], 'Le début et la fin du champ « période » doivent être des dates autorisées.'],
    'forbidden start' => [['start' => '2026-03-15', 'end' => '2026-04-01'], 'Le début et la fin du champ « période » doivent être des dates autorisées.'],
    'forbidden end' => [['start' => '2026-02-14', 'end' => '2026-11-11'], 'Le début et la fin du champ « période » doivent être des dates autorisées.'],
    'list as end' => [['start' => '2026-02-14', 'end' => ['2026-04-01']], 'Le début et la fin du champ « période » doivent être des dates autorisées.'],
    'reversed' => [['start' => '2026-04-01', 'end' => '2026-02-14'], 'Le début du champ « période » doit précéder sa fin.'],
]);

test('the date selection leaves an empty value to the required rule', function (string $mode, mixed $value) {
    expect(dateSelectionErrors($mode, ['period' => $value]))->toBe([]);

    $errors = Validator::make(['period' => $value], ['period' => ['required', new DateSelection($mode)]])->errors();

    expect($errors->has('period'))->toBe($mode !== 'range');
})->with([
    'single' => ['single', ''],
    'multiple' => ['multiple', []],
    'range' => ['range', ['start' => '', 'end' => '']],
]);

test('the date selection is free without restriction', function () {
    $errors = Validator::make(['period' => '1999-01-01'], ['period' => [new DateSelection('single')]])->errors();

    expect($errors->isEmpty())->toBeTrue();
});

test('the date selection rejects an unknown mode', function () {
    expect(fn () => new DateSelection('weekly'))->toThrow(InvalidArgumentException::class, '« weekly »');
});
