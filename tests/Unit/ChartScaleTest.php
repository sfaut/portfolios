<?php

use App\Support\ChartScale;

test('the scale takes the smallest round step that covers the maximum in four steps', function (float $value, float $step, float $max) {
    $scale = new ChartScale($value);

    expect($scale->step)->toBe($step)
        ->and($scale->max)->toBe($max);
})->with([
    'hundreds' => [1234.0, 500.0, 2000.0],
    'exact' => [6000.0, 2000.0, 8000.0],
    'two and a half' => [37500.0, 10000.0, 40000.0],
    'between two and two and a half' => [9000.0, 2500.0, 10000.0],
    'cents' => [0.3, 0.1, 0.4],
    'nothing' => [0.0, 1.0, 4.0],
]);

test('the ticks run from zero to the top of the axis', function () {
    $scale = new ChartScale(6000.0);

    expect($scale->ticks())->toBe([
        ['value' => 0.0, 'percent' => 0.0],
        ['value' => 2000.0, 'percent' => 25.0],
        ['value' => 4000.0, 'percent' => 50.0],
        ['value' => 6000.0, 'percent' => 75.0],
        ['value' => 8000.0, 'percent' => 100.0],
    ])
        ->and($scale->percent(3000.0))->toBe(37.5)
        ->and(ChartScale::format(8000.0))->toBe("8\u{202F}000");
});
