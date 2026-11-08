@props([
    'size' => 'normal',
])
<button
    type="button"

    {{
        $attributes->class([
            'rounded-md',
            'leading-none',
            'bg-indigo-800', 'dark:bg-indigo-600', 'text-white', 'font-medium', 'uppercase',
            'tracking-wider',

            'px-2' => ($size === 'tiny'),
            'py-1.5' => ($size === 'tiny'),
            'text-xs' => ($size === 'tiny'),

            'px-3' => ($size === 'small'),
            'py-2' => ($size === 'small'),
            'text-sm' => ($size === 'small'),

            'px-3.5' => ($size === 'normal'),
            'py-2.5' => ($size === 'normal'),
            'text-base' => ($size === 'normal'),

            'px-4' => ($size === 'large'),
            'py-3' => ($size === 'large'),
            'text-lg' => ($size === 'large'),
        ])
    }}
>
    {{ $slot }}
</button>
<!--
<button type="button" class="px-3 py-2 text-xs">Extra small</button>
<button type="button" class="px-3 py-2 text-sm">Small</button>
<button type="button" class="px-5 py-2.5 text-sm">Base</button>
<button type="button" class="px-5 py-3 text-base">Large</button>
<button type="button" class="px-6 py-3.5 text-base">Extra large</button>

Attributs communs :
text-white
text-center
font-medium
bg-blue-700
hover:bg-blue-800
dark:bg-blue-600
dark:hover:bg-blue-700
rounded-lg
focus:ring-4
focus:outline-hidden
focus:ring-blue-300
dark:focus:ring-blue-800
-->
