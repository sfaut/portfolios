@props([
    'type' => 'info',
])

@php

    // https://laravel.com/api/11.x/Illuminate/View/ComponentAttributeBag.html

    $styles = [
        'info' => ['text-info-700', 'bg-info-200', 'ring-info-300', 'dark:text-info-200', 'dark:bg-info-900/60', 'dark:ring-info-700'],
        'success' => ['text-success-700', 'bg-success-200', 'ring-success-300', 'dark:text-success-200', 'dark:bg-success-900/60', 'dark:ring-success-700'],
        'warning' => ['text-warning-700', 'bg-warning-200', 'ring-warning-300', 'dark:text-warning-200', 'dark:bg-warning-900/60', 'dark:ring-warning-700'],
        'danger' => ['text-danger-700', 'bg-danger-200', 'ring-danger-300', 'dark:text-danger-200', 'dark:bg-danger-900/60', 'dark:ring-danger-700'],
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
