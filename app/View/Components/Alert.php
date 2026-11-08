<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Message d'alerte, aux couleurs et à l'icône de son type : info, success, warning ou danger.
 *
 * Un type inconnu retombe sur info.
 */
class Alert extends Component
{
    /**
     * Classes de couleur de chaque type, en clair et en sombre.
     *
     * @var array<string, list<string>>
     */
    public const array STYLES = [
        'info' => ['text-info-700', 'bg-info-200', 'ring-info-300', 'dark:text-info-200', 'dark:bg-info-900/60', 'dark:ring-info-700'],
        'success' => ['text-success-700', 'bg-success-200', 'ring-success-300', 'dark:text-success-200', 'dark:bg-success-900/60', 'dark:ring-success-700'],
        'warning' => ['text-warning-700', 'bg-warning-200', 'ring-warning-300', 'dark:text-warning-200', 'dark:bg-warning-900/60', 'dark:ring-warning-700'],
        'danger' => ['text-danger-700', 'bg-danger-200', 'ring-danger-300', 'dark:text-danger-200', 'dark:bg-danger-900/60', 'dark:ring-danger-700'],
    ];

    /**
     * Icône Lucide de chaque type, en nom de composant.
     *
     * @var array<string, string>
     */
    public const array ICONS = [
        'info' => 'lucide-info',
        'success' => 'lucide-circle-check',
        'warning' => 'lucide-triangle-alert',
        'danger' => 'lucide-circle-alert',
    ];

    /**
     * Classes de couleur du message.
     *
     * @var list<string>
     */
    public array $style;

    /**
     * Composant de l'icône du message.
     */
    public string $icon;

    /**
     * Rôle ARIA du message : alert pour un danger, annoncé sans délai, status sinon.
     */
    public string $role;

    /**
     * Retient le type du message, ou info s'il est inconnu, et en déduit ses couleurs, son icône et
     * son rôle.
     *
     * @param  string  $type  Type du message : info (par défaut), success, warning ou danger.
     */
    public function __construct(
        public string $type = 'info',
    ) {
        if (! array_key_exists($this->type, self::STYLES)) {
            $this->type = 'info';
        }

        $this->style = self::STYLES[$this->type];
        $this->icon = self::ICONS[$this->type];
        $this->role = $this->type === 'danger' ? 'alert' : 'status';
    }

    /**
     * Vue du composant.
     */
    public function render(): View
    {
        return view('components.alert');
    }
}
