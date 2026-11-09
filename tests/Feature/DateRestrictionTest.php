<?php

use App\Support\DateRestriction;

/*
 * The cases come from tests/fixtures/date-restrictions.json, which the JavaScript tests read too
 * (tests/js/date-picker-rules.test.js): the browser and the server must agree on them.
 */
dataset('date restrictions', fn (): array => array_map(
    fn (array $case): array => [$case],
    json_decode(file_get_contents(dirname(__DIR__).'/fixtures/date-restrictions.json'), associative: true, flags: JSON_THROW_ON_ERROR),
));

test('a date restriction normalizes its lists and bounds', function (array $case) {
    $restriction = new DateRestriction(...$case['input']);

    expect($restriction->toArray())->toBe($case['normalized']);
})->with('date restrictions');

test('a date restriction accepts its choosable days and refuses the others', function (array $case) {
    $restriction = new DateRestriction(...$case['input']);

    foreach ($case['choosable'] as $day) {
        expect($restriction->isChoosable($day))->toBeTrue("{$day} should be choosable");
    }

    foreach ($case['refused'] as $day) {
        expect($restriction->isChoosable($day))->toBeFalse("{$day} should be refused");
    }
})->with('date restrictions');

test('a date restriction refuses any value that is not a real day', function (mixed $value) {
    expect((new DateRestriction)->isChoosable($value))->toBeFalse();
})->with([
    'empty' => [''],
    'month 13' => ['2026-13-01'],
    'day 0' => ['2026-11-00'],
    'day 32' => ['2026-01-32'],
    'February 30' => ['2026-02-30'],
    'February 29 of a common year' => ['2026-02-29'],
    'one-digit day' => ['2026-11-9'],
    'two-digit year' => ['26-11-09'],
    'month' => ['2026-11'],
    'date and time' => ['2026-11-09 10:00:00'],
    'range' => ['2026-01-01/2026-01-31'],
    'trailing line feed' => ["2026-11-09\n"],
    'null' => [null],
    'number' => [20261109],
    'list' => [['2026-11-09']],
]);

test('a date restriction rejects a malformed entry or bound', function (array $arguments, string $message) {
    expect(fn () => new DateRestriction(...$arguments))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'malformed day' => [['allow' => ['2026-02-30']], '« 2026-02-30 »'],
    'malformed range' => [['deny' => ['2026-01-01/2026-01-32']], '« 2026-01-01/2026-01-32 »'],
    'range of three days' => [['allow' => ['2026-01-01/2026-01-02/2026-01-03']], '« 2026-01-01/2026-01-02/2026-01-03 »'],
    'open range' => [['deny' => ['2026-01-01/']], '« 2026-01-01/ »'],
    'not a string' => [['allow' => [20261109]], '« int »'],
    'malformed bound' => [['min' => '2026-11'], 'min « 2026-11 »'],
]);
