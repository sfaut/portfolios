@props([
    'type' => 'info',
])

@php

    // https://laravel.com/api/11.x/Illuminate/View/ComponentAttributeBag.html

    $styles = [
        'info' => ['text-sky-700', 'bg-sky-200', 'ring-sky-300', 'dark:text-sky-200', 'dark:bg-sky-900/60', 'dark:ring-sky-700'],
        'success' => ['text-success-700', 'bg-success-200', 'ring-success-300', 'dark:text-success-200', 'dark:bg-success-900/60', 'dark:ring-success-700'],
        'warning' => ['text-amber-700', 'bg-amber-200', 'ring-amber-300', 'dark:text-amber-200', 'dark:bg-amber-900/60', 'dark:ring-amber-700'],
        'danger' => ['text-rose-700', 'bg-rose-200', 'ring-rose-300', 'dark:text-rose-200', 'dark:bg-rose-900/60', 'dark:ring-rose-700'],
    ];

    $style = $styles[$type] ?? $styles['info'];

    $attributes = $attributes
        ->class([
            'whitespace-pre-line',
            'shadow-md', 'rounded-md',
            'flex', 'items-center',
            'px-5', 'gap-5',
            'ring-1',
        ])->merge(['class' => implode(' ', $style)])
    ;

@endphp

<div {{ $attributes }}>
    <div>
        <svg width="25" height="25" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
        </svg>
    </div>
    <div>{{ $slot }}</div>
</div>
