<?php

test('a released toggle button sends the value that presses it', function () {
    $view = $this->blade('<x-toggle-button name="all" aria-label="Toutes les opérations">Tout</x-toggle-button>');

    $view->assertSeeInOrder(['<button', 'type="submit"', 'name="all"', 'value="1"', 'aria-pressed="false"', 'aria-label="Toutes les opérations"', '>Tout</button>'], escape: false);
});

test('a pressed toggle button sends the value that releases it, and looks pushed in', function () {
    $view = $this->blade('<x-toggle-button name="all" :pressed="true">Tout</x-toggle-button>');

    $view->assertSeeInOrder(['<button', 'type="submit"', 'name="all"', 'value="0"', 'aria-pressed="true"'], escape: false)
        ->assertSee('aria-pressed:bg-indigo-700 aria-pressed:text-white aria-pressed:shadow-[inset_0_2px_4px_rgb(0_0_0/0.35)]', escape: false);
});
