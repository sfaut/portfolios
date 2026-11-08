<?php

namespace App\Providers;

use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerNumberFormats();
    }

    /**
     * Déclare Number::amount(), le format des montants de l'interface.
     *
     * Number::amount($number, signed: false, currency: '') écrit le montant en français, à deux
     * décimales, suivi de la devise si elle est donnée (code ISO 4217, « EUR »). Un montant négatif
     * porte le signe moins typographique (U+2212), à la place du tiret (U+002D) de Number ; avec
     * signed, un montant positif porte le signe plus. Zéro n'a pas de signe.
     */
    private function registerNumberFormats(): void
    {
        Number::macro('amount', function (int|float|string $number, bool $signed = false, string $currency = ''): string {
            $value = (float) $number;

            $digits = $currency === ''
                ? Number::format(abs($value), precision: 2, locale: 'fr')
                : Number::currency(abs($value), $currency, 'fr');

            $sign = match (true) {
                $value < 0 => '−',
                $signed && $value > 0 => '+',
                default => '',
            };

            return "{$sign}{$digits}";
        });
    }
}
