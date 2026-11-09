<?php

namespace App\Support\ServerStatus;

/**
 * Section du relevé de l'état du serveur (Application, PHP, Système…) : ses lignes, son niveau, le
 * pire des leurs, et ses alertes.
 */
final readonly class StatusSection
{
    /**
     * @param  string  $id  Identifiant, ancre de la section dans la page (« php »).
     * @param  string  $title  Titre (« PHP »).
     * @param  list<StatusItem>  $items  Lignes de la section.
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $items,
    ) {
        //
    }

    /**
     * Niveau de la section : le pire de ses lignes.
     */
    public function level(): string
    {
        return StatusItem::worst(array_map(fn (StatusItem $item): string => $item->level, $this->items));
    }

    /**
     * Lignes de la section qui portent une alerte.
     *
     * @return list<StatusItem>
     */
    public function alerts(): array
    {
        return array_values(array_filter($this->items, fn (StatusItem $item): bool => $item->level !== StatusItem::OK));
    }
}
