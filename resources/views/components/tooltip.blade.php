{{--
    Infobulle affichée au survol ou au focus de son déclencheur, et masquée par Échap.

    L'infobulle est un popover manuel, ouvert et fermé par Alpine : placée dans la couche supérieure
    (top layer), elle échappe au rognage d'un parent en overflow-hidden (tableau arrondi…). Le
    positionnement d'ancre CSS la place au-dessus du déclencheur, ou en dessous faute de place. Le
    déclencheur se focalise au clavier et se décrit par l'infobulle (aria-describedby).

    @var \Illuminate\View\ComponentSlot $slot Déclencheur, contenu en ligne (pastille, icône, texte…).
    @var \Illuminate\View\ComponentSlot $content Contenu de l'infobulle, en ligne (span…).
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du déclencheur.
--}}
<span
    x-data
    x-id="['tooltip']"
    x-on:mouseenter="$refs.tooltip.togglePopover(true)"
    x-on:mouseleave="$refs.tooltip.togglePopover(false)"
    x-on:focusin="$refs.tooltip.togglePopover(true)"
    x-on:focusout="$refs.tooltip.togglePopover(false)"
    x-on:keydown.escape="$refs.tooltip.togglePopover(false)"
    x-bind:style="{ anchorName: `--${$id('tooltip')}` }"
    x-bind:aria-describedby="$id('tooltip')"
    tabindex="0"
    {{
        $attributes->class([
            'inline-flex', 'rounded-full',
            'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-indigo-500',
        ])
    }}
>
    {{ $slot }}

    <span
        x-ref="tooltip"
        x-bind:id="$id('tooltip')"
        x-bind:style="{ positionAnchor: `--${$id('tooltip')}` }"
        popover="manual"
        role="tooltip"
        @class([
            '[position-area:top]', '[position-try-fallbacks:flip-block]',
            'inset-auto', 'mx-0', 'my-2', 'overflow-visible',
            'w-max', 'max-w-xs',
            'rounded-md', 'border-0', 'bg-gray-950', 'dark:ring-1', 'dark:ring-gray-700', 'shadow-lg',
            'px-3', 'py-2',
            'text-left', 'text-xs', 'font-normal', 'normal-case', 'tracking-normal', 'whitespace-normal', 'text-gray-100',
            'opacity-0', 'open:opacity-100', 'starting:open:opacity-0',
            'transition-all', 'transition-discrete', 'duration-150', 'ease-out',
            'motion-reduce:transition-none',
        ])
    >{{ $content }}</span>
</span>
