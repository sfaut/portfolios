<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Région des toasts, posée une fois par la mise en page (layouts/app.blade.php).
 *
 * Les toasts sont menés par le store Alpine `toasts` (resources/js/toasts.js) : un composant en
 * affiche un par `Alpine.store('toasts').show({...})`. Le serveur en affiche un par le flash de
 * session `toast`, que la région montre au chargement de la page, et en repli sans JavaScript :
 * `['type' => 'success', 'message' => '…']`, ou le message seul. Types et icônes sont ceux de
 * `<x-alert>`, un type inconnu retombant sur info.
 */
class ToastRegion extends Component
{
    /**
     * Places des toasts sur l'écran, celles de resources/js/toast-rules.js.
     *
     * @var list<string>
     */
    public const array POSITIONS = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];

    /**
     * Couleur de l'icône de chaque type, claire sur le fond sombre du toast.
     *
     * @var array<string, string>
     */
    public const array ICON_COLORS = [
        'info' => 'text-info-300',
        'success' => 'text-success-400',
        'warning' => 'text-warning-300',
        'danger' => 'text-danger-400',
    ];

    /**
     * Icône (composant lucide-…) et couleur de chaque type.
     *
     * @var array<string, array{icon: string, color: string}>
     */
    public array $types;

    /**
     * Toast demandé par le flash de session `toast`, null sans flash.
     *
     * @var array{type: string, message: string}|null
     */
    public ?array $flash;

    /**
     * Lit le flash de session et contrôle la place.
     *
     * @param  string  $position  Place des toasts qui n'en donnent pas (POSITIONS), bottom-right
     *                            par défaut ; une place inconnue retombe sur ce défaut.
     */
    public function __construct(
        public string $position = 'bottom-right',
    ) {
        if (! in_array($this->position, self::POSITIONS, strict: true)) {
            $this->position = 'bottom-right';
        }

        $this->types = array_map(
            fn (string $type): array => ['icon' => Alert::ICONS[$type], 'color' => self::ICON_COLORS[$type]],
            array_combine(array_keys(Alert::ICONS), array_keys(Alert::ICONS)),
        );
        $this->flash = $this->readFlash(session('toast'));
    }

    /**
     * Toast d'un flash de session, null quand le flash est absent ou sans message.
     *
     * @return array{type: string, message: string}|null
     */
    private function readFlash(mixed $flash): ?array
    {
        if (is_string($flash)) {
            $flash = ['message' => $flash];
        }

        $message = is_array($flash) && is_string($flash['message'] ?? null) ? $flash['message'] : '';

        if ($message === '') {
            return null;
        }

        $type = $flash['type'] ?? 'info';

        return [
            'type' => is_string($type) && array_key_exists($type, Alert::STYLES) ? $type : 'info',
            'message' => $message,
        ];
    }

    /**
     * Vue de la région.
     */
    public function render(): View
    {
        return view('components.toast-region');
    }
}
