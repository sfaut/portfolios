<?php

namespace App\Enums;

/**
 * Thème de l'interface choisi par l'utilisateur : clair, sombre, ou celui du système.
 *
 * Il devient la classe `light` ou `dark` de `<html>`, que lit la variante `dark:` de
 * resources/css/app.css ; sans classe, le système décide.
 */
enum ColorScheme: string
{
    case Light = 'light';
    case System = 'system';
    case Dark = 'dark';

    /**
     * Classe de `<html>` du thème : light, dark, ou '' pour suivre le système.
     */
    public function htmlClass(): string
    {
        return $this === self::System ? '' : $this->value;
    }
}
