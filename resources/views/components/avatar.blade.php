@props([
    'user',
    'size' => 'sm',
])

{{--
    Avatar d'un utilisateur : une pastille ronde, sa photo s'il en a une, sinon ses initiales (« SF »)
    sur fond indigo pâle. La photo ne se sert qu'à l'utilisateur connecté (route profile.avatar).

    Il double le nom, toujours écrit à côté ou dans le titre de la carte : il est donc masqué aux
    lecteurs d'écran (aria-hidden).

    @var \App\Models\User $user Utilisateur représenté.
    @var string $size Taille : sm (32 px, barre du haut), md (48 px) ou lg (128 px, profil).
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs de la pastille.
--}}
<span
    aria-hidden="true"
    {{
        $attributes->class([
            'inline-flex', 'shrink-0', 'select-none', 'items-center', 'justify-center', 'overflow-hidden',
            'rounded-full', 'bg-indigo-100', 'dark:bg-indigo-900/60',
            'font-semibold', 'tracking-normal', 'text-indigo-800', 'dark:text-indigo-200',
            'ring-1', 'ring-black/5', 'dark:ring-white/10',
            'size-8 text-xs' => $size === 'sm',
            'size-12 text-base' => $size === 'md',
            'size-32 text-4xl' => $size === 'lg',
        ])
    }}
>@if ($user->avatarUrl() !== '')<img src="{{ $user->avatarUrl() }}" alt="" class="size-full object-cover">@else{{ $user->initials() }}@endif</span>
