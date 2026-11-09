<?php

use App\View\Components\Chart\StepLine;
use Carbon\CarbonImmutable;

/**
 * Courbe d'une ou plusieurs séries sur une période.
 *
 * @param  list<array<string, mixed>>  $series  Séries, sans les classes, ajoutées ici.
 */
function stepLine(string $from, int $days, array $series): StepLine
{
    return new StepLine(
        from: CarbonImmutable::parse($from),
        days: $days,
        series: array_map(fn (array $serie): array => [...$serie, 'line' => '', 'text' => ''], $series),
        label: 'Courbe',
    );
}

test('a series steps up from zero on each point, then runs flat to its end, its label kept inside the chart', function () {
    $chart = stepLine('2026-03-01', 31, [['points' => [[0, 15.0], [10, 35.0]], 'end' => 20, 'caption' => '2026']]);

    expect($chart->polylines[0]['points'])->toBe('0,300 0,300 0,180.95 322.58,180.95 322.58,22.22 645.16,22.22')
        ->and($chart->polylines[0]['leader'])->toBe('645.16,22.22 1000,22.22')
        ->and($chart->labels[0])->toMatchArray(['top' => 8.0, 'caption' => '2026']);
});

test('the end labels of close series are moved apart', function () {
    $chart = stepLine('2026-03-01', 31, [
        ['points' => [[0, 100.0]], 'end' => 31, 'caption' => '2026'],
        ['points' => [[0, 98.0]], 'end' => 31, 'caption' => '2025'],
    ]);

    expect($chart->labels[1]['top'] - $chart->labels[0]['top'])->toBeGreaterThanOrEqual(StepLine::LABEL_GAP)
        ->and(array_column($chart->labels, 'caption'))->toBe(['2026', '2025']);
});

test('the months under the chart give the year on January and the first month, and mark the minor ones', function () {
    $chart = stepLine('2025-11-01', 151, []);

    expect(array_column($chart->ticks, 'text'))->toBe(['nov.', 'déc.', 'janv.', 'févr.', 'mars'])
        ->and(array_column($chart->ticks, 'year'))->toBe(['2025', '', '2026', '', ''])
        ->and(array_column($chart->ticks, 'minor'))->toBe([false, true, false, true, true]);
});

test('the months under a chart of more than a year are quarterly', function () {
    $chart = stepLine('2025-01-01', 730, []);

    expect(array_column($chart->ticks, 'text'))->toBe(['janv.', 'avr.', 'juil.', 'oct.', 'janv.', 'avr.', 'juil.', 'oct.'])
        ->and(array_column($chart->ticks, 'minor'))->toBe([false, true, true, true, false, true, true, true]);
});
