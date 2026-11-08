<?php

use Illuminate\Support\Facades\Blade;

test('the alert takes the colors, the icon and the role of its type', function (string $type, string $color, string $icon, string $role) {
    $view = $this->blade("<x-alert type=\"{$type}\">Message</x-alert>");

    $view->assertSeeInOrder([
        "role=\"{$role}\"",
        $color,
        Blade::render("<x-{$icon} class=\"size-5\" aria-hidden=\"true\"/>"),
        'Message',
    ], escape: false);
})->with([
    'info' => ['info', 'text-info-700', 'lucide-info', 'status'],
    'success' => ['success', 'text-success-700', 'lucide-circle-check', 'status'],
    'warning' => ['warning', 'text-warning-700', 'lucide-triangle-alert', 'status'],
    'danger' => ['danger', 'text-danger-700', 'lucide-circle-alert', 'alert'],
]);

test('the alert falls back on the info type', function () {
    $view = $this->blade('<x-alert type="unknown">Message</x-alert>');

    $view->assertSee('text-info-700')->assertDontSee('unknown');
});

test('the alert keeps the line breaks of its message, without its surrounding blanks', function () {
    $view = $this->blade(<<<'BLADE'
        <x-alert type="warning">
            Première ligne.
            Seconde ligne.
        </x-alert>
        BLADE);

    $view->assertSee('whitespace-pre-line">Première ligne.', escape: false)
        ->assertSee('Seconde ligne.</div>', escape: false);
});
