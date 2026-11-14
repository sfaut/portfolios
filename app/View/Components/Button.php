<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Bouton de l'application, ou lien à l'allure de bouton, dans l'une de ses trois variantes :
 * primary (action principale de la page ou de la carte), secondary (simple bouton, par défaut) ou
 * danger (archivage, suppression).
 *
 * Sans `href`, c'est un `<button>`, de type `button` par défaut : un bouton qui envoie son
 * formulaire le dit par `type="submit"`. Avec `href`, c'est un lien `<a>`, sans type. Les autres
 * attributs de l'appelant (form, commandfor, x-on:…, class…) passent tels quels.
 *
 * Les classes sont écrites en entier, pour que Tailwind les trouve.
 */
class Button extends Component
{
    /**
     * Classes communes aux trois variantes : forme, texte, transitions, état désactivé et anneau de
     * focus au clavier. La bordure, transparente hors de secondary, garde la même hauteur à toutes.
     *
     * @var list<string>
     */
    public const array BASE = [
        'inline-flex', 'items-center', 'justify-center',
        'rounded-md', 'border', 'px-4', 'py-2',
        'text-xs', 'font-semibold', 'uppercase', 'tracking-widest',
        'transition', 'ease-in-out', 'duration-150',
        'disabled:pointer-events-none', 'disabled:opacity-25',
        'focus:outline-hidden', 'focus-visible:ring-2', 'focus-visible:ring-offset-2',
        'dark:focus-visible:ring-offset-gray-800',
    ];

    /**
     * Couleurs de chaque variante, en clair et en sombre.
     *
     * @var array<string, list<string>>
     */
    public const array VARIANTS = [
        'primary' => [
            'border-transparent', 'bg-indigo-600', 'text-white',
            'hover:bg-indigo-500', 'active:bg-indigo-700', 'focus-visible:ring-indigo-500',
            'dark:bg-indigo-500', 'dark:hover:bg-indigo-400', 'dark:active:bg-indigo-600',
            'dark:focus-visible:ring-indigo-300',
        ],
        'secondary' => [
            'border-gray-300', 'bg-white', 'text-gray-700', 'shadow-xs',
            'hover:bg-gray-50', 'active:bg-gray-100', 'focus-visible:ring-indigo-500',
            'dark:border-gray-600', 'dark:bg-gray-700', 'dark:text-gray-200', 'dark:hover:bg-gray-600',
            'dark:focus-visible:ring-indigo-300',
        ],
        'danger' => [
            'border-transparent', 'bg-red-600', 'text-white',
            'hover:bg-red-500', 'active:bg-red-700', 'focus-visible:ring-red-500',
        ],
    ];

    /**
     * Classes du bouton : la base, celles de la variante, et l'écart de l'icône s'il y en a une.
     *
     * @var list<string>
     */
    public array $classes;

    /**
     * Vérifie la variante et en déduit les classes du bouton.
     *
     * @param  string  $variant  Variante : primary, secondary (par défaut) ou danger.
     * @param  string|null  $href  Adresse du lien, null pour un bouton.
     * @param  string  $type  Type du bouton (button par défaut, submit, reset), ignoré pour un lien.
     * @param  string|null  $icon  Nom de l'icône Lucide placée devant le texte, null sans icône.
     *
     * @throws InvalidArgumentException Si la variante est inconnue.
     */
    public function __construct(
        public string $variant = 'secondary',
        public ?string $href = null,
        public string $type = 'button',
        public ?string $icon = null,
    ) {
        if (! array_key_exists($this->variant, self::VARIANTS)) {
            throw new InvalidArgumentException("Variante de bouton inconnue : « {$this->variant} ».");
        }

        $this->classes = [
            ...self::BASE,
            ...self::VARIANTS[$this->variant],
            ...($this->icon !== null ? ['gap-2'] : []),
        ];
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.button');
    }
}
