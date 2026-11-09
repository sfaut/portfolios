<?php

namespace App\Providers;

use App\Support\PortfolioContext;
use App\View\Components\ChoiceSwitch;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        $this->registerRateLimiters();
        $this->registerViewComposers();

        Blade::component('switch', ChoiceSwitch::class);
    }

    /**
     * Passe à la barre de navigation le portfolio de la page, sa section et, dans un portfolio, la liste
     * des portfolios de l'utilisateur pour le sélecteur (triés par nom).
     */
    private function registerViewComposers(): void
    {
        View::composer('layouts.navigation', function (ViewContract $view): void {
            $context = PortfolioContext::fromRoute(request()->route());

            $view->with([
                'current_portfolio' => $context->portfolio,
                'current_section' => $context->section,
                'portfolios' => $context->portfolio === null
                    ? collect()
                    : auth()->user()->portfolios()->orderBy('name')->get(['id', 'name']),
            ]);
        });
    }

    /**
     * Déclare les limites de débit des formulaires d'authentification (middleware `throttle:<nom>`).
     *
     * Elles freinent le sondage des adresses et la recherche des mots de passe : `login` par IP, en plus
     * de la limite par adresse de LoginRequest ; `registration` et `password-reset` (demande du lien et
     * nouveau mot de passe) par IP ; par utilisateur, les formulaires qui vérifient le mot de passe actuel
     * (`password-confirmation`, `password-update`, `account-deletion`). Au-delà, le formulaire revient
     * avec un message sous le champ indiqué, à la place de la page 429. `color-scheme` borne par
     * utilisateur l'enregistrement du thème, que l'interrupteur de la barre envoie par script, et
     * `avatar` l'envoi de la photo de profil, que le serveur décode et réencode.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip())
            ->response($this->throttledForm('email')));

        RateLimiter::for('registration', fn (Request $request) => Limit::perHour(5)
            ->by($request->ip())
            ->response($this->throttledForm('email')));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(10)
            ->by($request->ip())
            ->response($this->throttledForm('email')));

        RateLimiter::for('password-confirmation', fn (Request $request) => Limit::perMinute(5)
            ->by((string) $request->user()?->id)
            ->response($this->throttledForm('password')));

        RateLimiter::for('password-update', fn (Request $request) => Limit::perMinute(5)
            ->by((string) $request->user()?->id)
            ->response($this->throttledForm('current_password', bag: 'updatePassword')));

        RateLimiter::for('color-scheme', fn (Request $request) => Limit::perMinute(30)
            ->by((string) $request->user()?->id));

        RateLimiter::for('avatar', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->user()?->id));

        RateLimiter::for('account-deletion', fn (Request $request) => Limit::perMinute(5)
            ->by((string) $request->user()?->id)
            ->response($this->throttledForm('password', bag: 'userDeletion')));
    }

    /**
     * Réponse d'un formulaire qui dépasse sa limite de débit : retour au formulaire, saisie gardée hors
     * mots de passe, avec le délai d'attente sous le champ donné.
     *
     * @param  string  $field  Champ qui porte le message.
     * @param  string  $bag  Sac d'erreurs que lit le formulaire.
     * @return Closure(Request, array<string, mixed>): RedirectResponse
     */
    private function throttledForm(string $field, string $bag = 'default'): Closure
    {
        return function (Request $request, array $headers) use ($field, $bag): RedirectResponse {
            $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 60)) / 60));

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password']))
                ->withErrors([$field => trans_choice('auth.throttle_minutes', $minutes, ['minutes' => $minutes])], $bag)
                ->withHeaders($headers);
        };
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
