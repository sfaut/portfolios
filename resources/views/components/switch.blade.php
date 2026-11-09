{{--
    Interrupteur à 2 ou 3 états (App\View\Components\ChoiceSwitch) : une piste en pilule, un curseur
    qui glisse sous l'option cochée, des radios natifs masqués.

    Le curseur se place par la variable --switch-index, que la piste reçoit de l'option cochée
    (:has(), sans JavaScript). L'option cochée passe en indigo, l'option focalisée au clavier porte
    un anneau.

    @var string $label Nom accessible du groupe.
    @var string $name Nom des radios, '' hors d'un formulaire.
    @var array<string, array{0: string, 1: string|null}> $choices Options : valeur => [libellé, icône].
    @var string $value Valeur cochée au rendu.
    @var bool $labels Si les libellés s'affichent à côté des icônes.
    @var string $model Expression Alpine liée aux radios, '' sans liaison.
    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du groupe (x-on:change…).
--}}
<fieldset
    aria-label="{{ $label }}"
    {{
        $attributes->class([
            'group/switch', 'relative', 'inline-grid', 'min-w-0', 'grid-flow-col', 'auto-cols-fr',
            'rounded-full', 'bg-gray-100', 'dark:bg-gray-700', 'p-0.5',
            'ring-1', 'ring-inset', 'ring-gray-200', 'dark:ring-gray-600',
            'has-[>label:nth-of-type(2)>input:checked]:[--switch-index:1]',
            'has-[>label:nth-of-type(3)>input:checked]:[--switch-index:2]',
        ])
    }}
>
    <span
        aria-hidden="true"
        @class([
            'absolute', 'inset-y-0.5', 'left-0.5',
            'rounded-full', 'bg-white', 'dark:bg-gray-600', 'shadow-sm', 'ring-1', 'ring-black/5',
            '[translate:calc(var(--switch-index,0)*100%)_0]',
            'transition-[translate]', 'duration-200', 'ease-out', 'motion-reduce:transition-none',
            'hidden', 'group-has-checked/switch:block',
            'w-[calc((100%-0.25rem)/2)]' => count($choices) === 2,
            'w-[calc((100%-0.25rem)/3)]' => count($choices) === 3,
        ])
    ></span>

    @foreach ($choices as $choice => [$text, $icon])
        <label
            @class([
                'relative', 'flex', 'cursor-pointer', 'items-center', 'justify-center', 'gap-1.5',
                'rounded-full', 'px-2.5', 'py-1.5',
                'text-sm', 'font-medium', 'whitespace-nowrap',
                'text-gray-500', 'hover:text-gray-800', 'dark:text-gray-400', 'dark:hover:text-gray-100',
                'has-checked:text-indigo-600', 'dark:has-checked:text-indigo-300',
                'has-focus-visible:ring-2', 'has-focus-visible:ring-indigo-500',
            ])
        >
            <input
                type="radio"
                value="{{ $choice }}"
                @if ($name !== '') name="{{ $name }}" @endif
                @checked($choice === $value)
                @if ($model !== '') x-model="{{ $model }}" @endif
                class="sr-only"
            >

            @if ($icon !== null)
                <x-dynamic-component :component="'lucide-'.$icon" class="size-4 shrink-0" aria-hidden="true"/>
            @endif

            <span @class(['sr-only' => $icon !== null && ! $labels])>{{ $text }}</span>
        </label>
    @endforeach
</fieldset>
