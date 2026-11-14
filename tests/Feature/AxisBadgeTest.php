<?php

test('an axis icon alone measures size-4, unless the caller gives another size', function () {
    $axis = ['name' => 'Cours piano', 'icon' => 'piano', 'color' => 'rose'];

    $this->blade('<x-axis-badge :axis="$axis" icon-only/>', ['axis' => $axis])
        ->assertSee('size-4', escape: false);

    $this->blade('<x-axis-badge :axis="$axis" icon-only class="size-3.5"/>', ['axis' => $axis])
        ->assertSee('size-3.5', escape: false)
        ->assertDontSee('size-4', escape: false);
});
