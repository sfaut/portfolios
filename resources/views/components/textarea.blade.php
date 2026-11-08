@props([
    'disabled' => false,
])

{{-- Multi-line text field, styled like x-text-input; the slot gives its content. --}}
<textarea

    @disabled($disabled)

    {{
        $attributes->merge(['rows' => 4])->class([
            'resize-none',
            'border-gray-300',
            'dark:border-gray-700',
            'rounded-md',
            'dark:bg-gray-900',
            'dark:text-gray-300',
            'focus:border-indigo-500',
            'dark:focus:border-indigo-600',
            'focus:shadow-md',
        ])
    }}
>{{ $slot }}</textarea>
