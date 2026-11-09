<?php

use App\View\Components\Chart\MonthlySales;

/**
 * Graphe des ventes par mois, à partir des ventes, ventes un an plus tôt et cumuls donnés.
 *
 * @param  list<array{0: float, 1: float, 2: float}>  $values  Triplets [ventes, un an plus tôt, cumul], un par mois.
 */
function monthlySales(array $values): MonthlySales
{
    return new MonthlySales(
        months: array_map(fn (int $index, array $value): array => [
            'month' => '2026-0'.($index + 1),
            'label' => 'Mois '.($index + 1),
            'initial' => 'M',
            'year' => '',
            'sales' => $value[0],
            'count' => 1,
            'payments' => 0.0,
            'cumulative' => $value[2],
            'previous' => $value[1],
        ], array_keys($values), $values),
        years: '2026',
        previousYears: '2025',
        label: 'Ventes',
    );
}

test('the two axes share their ticks, each with its round scale', function () {
    $chart = monthlySales([[3000.0, 6000.0, 3000.0], [1000.0, 0.0, 4000.0]]);

    expect(array_column($chart->ticks, 'percent'))->toBe([0.0, 25.0, 50.0, 75.0, 100.0])
        ->and(array_column($chart->ticks, 'left'))->toBe(['0', "2\u{202F}000", "4\u{202F}000", "6\u{202F}000", "8\u{202F}000"])
        ->and(array_column($chart->ticks, 'right'))->toBe(['0', "1\u{202F}000", "2\u{202F}000", "3\u{202F}000", "4\u{202F}000"]);
});

test('the bars and the ghosts rise on the left axis, the bars darker for the best months', function () {
    $chart = monthlySales([[3000.0, 6000.0, 3000.0], [1000.0, 0.0, 4000.0]]);

    expect(array_column($chart->columns, 'height'))->toBe([37.5, 12.5])
        ->and(array_column($chart->columns, 'ghost'))->toBe([75.0, 0.0])
        ->and(array_column($chart->columns, 'shade'))->toBe([100.0, 56.67]);
});

test('the running total starts from zero and goes through the middle of each month on the right axis', function () {
    $chart = monthlySales([[3000.0, 6000.0, 3000.0], [1000.0, 0.0, 4000.0]]);

    expect($chart->line)->toBe('0,100 25,25 75,0')
        ->and(array_column($chart->columns, 'x'))->toBe([25.0, 75.0])
        ->and(array_column($chart->columns, 'y'))->toBe([75.0, 100.0]);
});

test('a period without sales draws flat bars on an axis from zero', function () {
    $chart = monthlySales([[0.0, 0.0, 0.0]]);

    expect(array_column($chart->columns, 'height'))->toBe([0.0])
        ->and(array_column($chart->columns, 'shade'))->toBe([MonthlySales::MIN_SHADE])
        ->and($chart->ticks[0]['left'])->toBe('0');
});
