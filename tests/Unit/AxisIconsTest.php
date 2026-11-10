<?php

use App\Support\AxisIcons;

test('every icon of the selection exists in Lucide', function () {
    $directory = dirname(__DIR__, 2).'/vendor/mallardduck/blade-lucide-icons/resources/svg/icons';

    $missing = array_filter(AxisIcons::names(), fn (string $icon): bool => ! is_file("{$directory}/{$icon}.svg"));

    expect($missing)->toBe([]);
});

test('the selection has no duplicate, and holds the default icon', function () {
    expect(AxisIcons::names())->toBe(array_values(array_unique(AxisIcons::names())))
        ->and(AxisIcons::names())->toContain(AxisIcons::DEFAULT);
});

test('the selection holds every Lucide icon of music and games', function () {
    expect(AxisIcons::names())->toContain(
        'piano', 'keyboard-music', 'guitar', 'guitar-electric', 'drum', 'mic-vocal', 'music', 'music-2', 'music-3', 'music-4', 'headphones', 'radio', 'disc-3',
        'chess-bishop', 'chess-king', 'chess-knight', 'chess-pawn', 'chess-queen', 'chess-rook',
        'dices', 'dice-1', 'dice-2', 'dice-3', 'dice-4', 'dice-5', 'dice-6', 'gamepad', 'gamepad-2', 'gamepad-directional', 'joystick', 'puzzle', 'spade', 'club', 'diamond', 'toy-brick',
    );
});

test('each icon has a French name', function () {
    expect(AxisIcons::label('piano'))->toBe(AxisIcons::THEMES['Musique']['piano'][0])
        ->and(collect(AxisIcons::names())->every(fn (string $icon): bool => AxisIcons::label($icon) !== ''))->toBeTrue();
});
