<?php

use App\Support\MonthRestriction;

/*
 * The cases come from tests/fixtures/month-restrictions.json, which the JavaScript tests read too
 * (tests/js/month-picker-rules.test.js): the browser and the server must agree on them.
 */
dataset('month restrictions', fn (): array => array_map(
    fn (array $case): array => [$case],
    json_decode(file_get_contents(dirname(__DIR__).'/fixtures/month-restrictions.json'), associative: true, flags: JSON_THROW_ON_ERROR),
));

test('a month restriction normalizes its lists and bounds', function (array $case) {
    $restriction = new MonthRestriction(...$case['input']);

    expect($restriction->toArray())->toBe($case['normalized']);
})->with('month restrictions');

test('a month restriction accepts its choosable months and refuses the others', function (array $case) {
    $restriction = new MonthRestriction(...$case['input']);

    foreach ($case['choosable'] as $month) {
        expect($restriction->isChoosable($month))->toBeTrue("{$month} should be choosable");
    }

    foreach ($case['refused'] as $month) {
        expect($restriction->isChoosable($month))->toBeFalse("{$month} should be refused");
    }
})->with('month restrictions');

test('a month restriction refuses any value that is not a month', function (mixed $value) {
    expect((new MonthRestriction)->isChoosable($value))->toBeFalse();
})->with([
    'empty' => [''],
    'month 13' => ['2026-13'],
    'month 0' => ['2026-00'],
    'one-digit month' => ['2026-1'],
    'two-digit year' => ['26-11'],
    'day' => ['2026-11-01'],
    'range' => ['2025-01/2025-06'],
    'trailing line feed' => ["2026-11\n"],
    'null' => [null],
    'number' => [202611],
    'list' => [['2026-11']],
]);

test('a month restriction rejects a malformed entry or bound', function (array $arguments, string $message) {
    expect(fn () => new MonthRestriction(...$arguments))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'malformed month' => [['allow' => ['2026-13']], '« 2026-13 »'],
    'malformed range' => [['deny' => ['2025-01/2025-13']], '« 2025-01/2025-13 »'],
    'range of three months' => [['allow' => ['2025-01/2025-02/2025-03']], '« 2025-01/2025-02/2025-03 »'],
    'open range' => [['deny' => ['2025-01/']], '« 2025-01/ »'],
    'not a string' => [['allow' => [202611]], '« int »'],
    'malformed bound' => [['min' => '2025-1'], 'min « 2025-1 »'],
]);
