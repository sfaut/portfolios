<?php

namespace App\Enums;

/**
 * Couleur d'un axe analytique : une famille de la palette Tailwind 4, parmi les dix-sept vives et
 * le gris.
 *
 * Les classes sont écrites en entier, pour que Tailwind les trouve dans app/ et les génère.
 */
enum AxisColor: string
{
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Green = 'green';
    case Emerald = 'emerald';
    case Teal = 'teal';
    case Cyan = 'cyan';
    case Sky = 'sky';
    case Blue = 'blue';
    case Indigo = 'indigo';
    case Violet = 'violet';
    case Purple = 'purple';
    case Fuchsia = 'fuchsia';
    case Pink = 'pink';
    case Rose = 'rose';
    case Gray = 'gray';

    /**
     * Couleur d'un axe qui n'en a pas choisi.
     */
    public const self DEFAULT = self::Indigo;

    /**
     * Nom de la couleur, en français.
     */
    public function label(): string
    {
        return match ($this) {
            self::Red => 'Rouge',
            self::Orange => 'Orange',
            self::Amber => 'Ambre',
            self::Yellow => 'Jaune',
            self::Lime => 'Citron vert',
            self::Green => 'Vert',
            self::Emerald => 'Émeraude',
            self::Teal => 'Sarcelle',
            self::Cyan => 'Cyan',
            self::Sky => 'Ciel',
            self::Blue => 'Bleu',
            self::Indigo => 'Indigo',
            self::Violet => 'Violet',
            self::Purple => 'Pourpre',
            self::Fuchsia => 'Fuchsia',
            self::Pink => 'Rose',
            self::Rose => 'Rose vif',
            self::Gray => 'Gris',
        };
    }

    /**
     * Classes d'une pastille d'axe : fond clair et texte foncé, inversés en sombre.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Red => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            self::Orange => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
            self::Amber => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
            self::Yellow => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
            self::Lime => 'bg-lime-100 text-lime-800 dark:bg-lime-900/50 dark:text-lime-300',
            self::Green => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            self::Emerald => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
            self::Teal => 'bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-300',
            self::Cyan => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300',
            self::Sky => 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300',
            self::Blue => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
            self::Indigo => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300',
            self::Violet => 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-300',
            self::Purple => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
            self::Fuchsia => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/50 dark:text-fuchsia-300',
            self::Pink => 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300',
            self::Rose => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300',
            self::Gray => 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300',
        };
    }

    /**
     * Classe du texte coloré, pour une icône seule (infobulles, formulaire d'opération).
     */
    public function textClasses(): string
    {
        return match ($this) {
            self::Red => 'text-red-600 dark:text-red-400',
            self::Orange => 'text-orange-600 dark:text-orange-400',
            self::Amber => 'text-amber-600 dark:text-amber-400',
            self::Yellow => 'text-yellow-600 dark:text-yellow-400',
            self::Lime => 'text-lime-600 dark:text-lime-400',
            self::Green => 'text-green-600 dark:text-green-400',
            self::Emerald => 'text-emerald-600 dark:text-emerald-400',
            self::Teal => 'text-teal-600 dark:text-teal-400',
            self::Cyan => 'text-cyan-600 dark:text-cyan-400',
            self::Sky => 'text-sky-600 dark:text-sky-400',
            self::Blue => 'text-blue-600 dark:text-blue-400',
            self::Indigo => 'text-indigo-600 dark:text-indigo-400',
            self::Violet => 'text-violet-600 dark:text-violet-400',
            self::Purple => 'text-purple-600 dark:text-purple-400',
            self::Fuchsia => 'text-fuchsia-600 dark:text-fuchsia-400',
            self::Pink => 'text-pink-600 dark:text-pink-400',
            self::Rose => 'text-rose-600 dark:text-rose-400',
            self::Gray => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Classe du texte coloré sur un fond toujours sombre (infobulle), en clair comme en sombre.
     */
    public function onDarkClasses(): string
    {
        return match ($this) {
            self::Red => 'text-red-400',
            self::Orange => 'text-orange-400',
            self::Amber => 'text-amber-400',
            self::Yellow => 'text-yellow-400',
            self::Lime => 'text-lime-400',
            self::Green => 'text-green-400',
            self::Emerald => 'text-emerald-400',
            self::Teal => 'text-teal-400',
            self::Cyan => 'text-cyan-400',
            self::Sky => 'text-sky-400',
            self::Blue => 'text-blue-400',
            self::Indigo => 'text-indigo-400',
            self::Violet => 'text-violet-400',
            self::Purple => 'text-purple-400',
            self::Fuchsia => 'text-fuchsia-400',
            self::Pink => 'text-pink-400',
            self::Rose => 'text-rose-400',
            self::Gray => 'text-gray-400',
        };
    }

    /**
     * Classes d'une barre de graphique ou d'une pastille de choix.
     */
    public function barClasses(): string
    {
        return match ($this) {
            self::Red => 'bg-red-500 dark:bg-red-400',
            self::Orange => 'bg-orange-500 dark:bg-orange-400',
            self::Amber => 'bg-amber-500 dark:bg-amber-400',
            self::Yellow => 'bg-yellow-500 dark:bg-yellow-400',
            self::Lime => 'bg-lime-500 dark:bg-lime-400',
            self::Green => 'bg-green-500 dark:bg-green-400',
            self::Emerald => 'bg-emerald-500 dark:bg-emerald-400',
            self::Teal => 'bg-teal-500 dark:bg-teal-400',
            self::Cyan => 'bg-cyan-500 dark:bg-cyan-400',
            self::Sky => 'bg-sky-500 dark:bg-sky-400',
            self::Blue => 'bg-blue-500 dark:bg-blue-400',
            self::Indigo => 'bg-indigo-500 dark:bg-indigo-400',
            self::Violet => 'bg-violet-500 dark:bg-violet-400',
            self::Purple => 'bg-purple-500 dark:bg-purple-400',
            self::Fuchsia => 'bg-fuchsia-500 dark:bg-fuchsia-400',
            self::Pink => 'bg-pink-500 dark:bg-pink-400',
            self::Rose => 'bg-rose-500 dark:bg-rose-400',
            self::Gray => 'bg-gray-500 dark:bg-gray-400',
        };
    }
}
