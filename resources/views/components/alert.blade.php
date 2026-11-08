{{--
    Message d'alerte, précédé d'une icône d'information, aux couleurs de son type.

    @var string $type Type du message : info (par défaut), success, warning, danger.
    @var \Illuminate\View\ComponentSlot $slot Message.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bloc.
--}}
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
        <x-lucide-info class="size-6" aria-hidden="true"/>
    </div>
    <div>{{ $slot }}</div>
</div>
