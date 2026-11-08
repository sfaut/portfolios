{{--
    Message de réussite des pages d'auth (lien envoyé, mot de passe réinitialisé…), omis s'il est vide.

    @var string|null $status Message à afficher, lu dans la clef de session status.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du bloc.
--}}
@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-success-600 dark:text-success-400']) }}>
        {{ $status }}
    </div>
@endif
