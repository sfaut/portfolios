@props([
    'disabled' => false,
])

{{-- The type defaults to "text"; a type given by the caller replaces it. --}}
<input

    @disabled($disabled)

    {{
        $attributes->merge(['type' => 'text'])->class([
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
>
