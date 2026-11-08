@props([
    'id',
    'danger' => false,
    'show' => false,
])

{{--
    Modal dialog box, native <dialog> laid out as an <x-card>: "title", "description", body (default
    slot) and "footer" slots, "danger" for the red ring and title. A button opens it without script
    through the Invoker Commands API (commandfor="<id>" command="show-modal"), and a button of the
    dialog closes it (commandfor="<id>" command="close"), like Escape or a click on the backdrop.
    "show" opens it as soon as the page is loaded, to show the validation errors of its form.
--}}
<dialog
    id="{{ $id }}"
    closedby="any"
    aria-labelledby="{{ $id }}-title"
    @isset($description) aria-describedby="{{ $id }}-description" @endisset
    @if ($show) x-data x-init="$el.showModal()" @endif
    {{
        $attributes->class([
            'm-auto', 'w-[calc(100%-2rem)]', 'max-w-lg', 'bg-transparent', 'p-0',
            'text-gray-700', 'dark:text-gray-300',
            'backdrop:bg-gray-900/60', 'backdrop:backdrop-blur-xs',
            'scale-95', 'opacity-0', 'open:scale-100', 'open:opacity-100',
            'starting:open:scale-95', 'starting:open:opacity-0',
            'transition-all', 'transition-discrete', 'duration-150', 'ease-out',
            'motion-reduce:transition-none',
        ])
    }}
>
    <x-card :danger="$danger" class="shadow-xl">
        @isset($title)
            <x-slot:title id="{{ $id }}-title">{{ $title }}</x-slot:title>
        @endisset

        @isset($description)
            <x-slot:description id="{{ $id }}-description">{{ $description }}</x-slot:description>
        @endisset

        {{ $slot }}

        @isset($footer)
            <x-slot:footer>{{ $footer }}</x-slot:footer>
        @endisset
    </x-card>
</dialog>
