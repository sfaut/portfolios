<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Interrupteur à 2 ou 3 états, balise `<x-switch>` (Switch est un mot réservé de PHP) : une piste en
 * pilule dont le curseur glisse sous l'option choisie.
 *
 * Les options sont des boutons radio natifs, masqués : l'interrupteur s'envoie dans un formulaire par
 * son nom, se parcourt au clavier avec les flèches, et son curseur suit l'option cochée en CSS
 * (`:has()`), sans JavaScript. Avec `model`, les radios se lient à une expression Alpine
 * (`x-model`). La variante `color-scheme` donne les options du thème : clair, système, sombre.
 */
class ChoiceSwitch extends Component
{
    /**
     * Options de la variante color-scheme : valeur => [libellé, icône Lucide].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array COLOR_SCHEME = [
        'light' => ['Clair', 'sun'],
        'system' => ['Système', 'monitor'],
        'dark' => ['Sombre', 'moon'],
    ];

    /**
     * Options de l'interrupteur : valeur => [libellé, icône Lucide ou null].
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    public array $choices;

    /**
     * Construit l'interrupteur à partir de ses options, ou de celles de sa variante.
     *
     * @param  string  $label  Nom accessible du groupe (aria-label).
     * @param  string  $name  Nom des radios, envoyé avec le formulaire ; '' hors d'un formulaire.
     * @param  array<string, string|array{0: string, 1?: string|null}>  $options
     *                                                                            Options, valeur => libellé, ou valeur => [libellé, icône Lucide] ; ignorées avec une variante.
     * @param  string  $value  Valeur cochée au rendu ; '' pour aucune.
     * @param  string  $variant  Rendu prédéfini : color-scheme, ou '' pour les options données.
     * @param  bool  $labels  Si les libellés s'affichent à côté des icônes ; sinon, ils ne sont lus que
     *                        par les lecteurs d'écran (toujours affichés pour une option sans icône).
     * @param  string  $model  Expression Alpine liée aux radios par x-model, '' sans liaison.
     *
     * @throws InvalidArgumentException Si la variante est inconnue, ou s'il n'y a pas 2 ou 3 options.
     */
    public function __construct(
        public string $label,
        public string $name = '',
        array $options = [],
        public string $value = '',
        public string $variant = '',
        public bool $labels = false,
        public string $model = '',
    ) {
        $options = match ($variant) {
            'color-scheme' => self::COLOR_SCHEME,
            '' => $options,
            default => throw new InvalidArgumentException("Variante d'interrupteur inconnue : {$variant}."),
        };

        if (count($options) < 2 || count($options) > 3) {
            throw new InvalidArgumentException('Un interrupteur a 2 ou 3 options, '.count($options).' données.');
        }

        $this->choices = array_map(
            fn (string|array $option): array => is_string($option) ? [$option, null] : [$option[0], $option[1] ?? null],
            $options,
        );
    }

    /**
     * Vue de l'interrupteur.
     */
    public function render(): View
    {
        return view('components.switch');
    }
}
