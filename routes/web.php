<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ServerStatusController;
use App\Http\Controllers\AnalyticAxisController;
use App\Http\Controllers\ColorSchemeController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
// use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/', function () {
    return to_route('login');
});

Route::middleware('auth')->get('/', function () {
    return to_route('portfolio.index');
});

Route::controller(PortfolioController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/portfolios', 'index')->name('portfolio.index');
    Route::get('/portfolio/create', 'create')->name('portfolio.create');
    Route::post('/portfolio', 'store')->name('portfolio.store');
    Route::get('/portfolio/{portfolio}/dashboard', 'dashboard')->name('portfolio.dashboard');
    Route::get('/portfolio/{portfolio}/show', 'show')->name('portfolio.show');
    Route::get('/portfolio/{portfolio}/edit', 'edit')->name('portfolio.edit');
    Route::put('/portfolio/{portfolio}', 'update')->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', 'destroy')->name('portfolio.destroy');

    Route::get('/portfolio/{id}', fn (int $id) => to_route('portfolio.dashboard', ['portfolio' => $id]))->whereNumber('id');
});

Route::controller(AccountController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/portfolio/{portfolio}/account/create', 'create')->name('account.create');
    Route::post('/portfolio/{portfolio}/account', 'store')->name('account.store');

    Route::get('/account/{account}/show', 'show')->name('account.show')->withTrashed();
    Route::get('/account/{account}/edit', 'edit')->name('account.edit')->withTrashed();
    Route::put('/account/{account}', 'update')->name('account.update')->withTrashed();
    Route::patch('/account/{account}/restore', 'restore')->name('account.restore')->withTrashed();
    Route::delete('/account/{account}', 'destroy')->name('account.destroy');
});

Route::controller(AnalyticAxisController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/portfolio/{portfolio}/analytic-axes', 'index')->name('analytic-axis.index');
    Route::get('/portfolio/{portfolio}/analytic-axis/create', 'create')->name('analytic-axis.create');
    Route::post('/portfolio/{portfolio}/analytic-axis', 'store')->name('analytic-axis.store');

    Route::get('/analytic-axis/{analytic_axis}/edit', 'edit')->name('analytic-axis.edit')->withTrashed();
    Route::put('/analytic-axis/{analytic_axis}', 'update')->name('analytic-axis.update')->withTrashed();
    Route::patch('/analytic-axis/{analytic_axis}/restore', 'restore')->name('analytic-axis.restore')->withTrashed();
    Route::delete('/analytic-axis/{analytic_axis}', 'destroy')->name('analytic-axis.destroy');
});

Route::controller(LedgerController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/portfolio/{portfolio}/ledger', 'index')->name('ledger.index');
    Route::get('/portfolio/{portfolio}/ledger/export', 'export')->name('ledger.export');
});

Route::controller(OperationController::class)->middleware(['auth', 'verified'])->group(function () {
    Route::get('/account/{account}/operation/create', 'create')->name('operation.create');
    Route::post('/account/{account}/operation', 'store')->name('operation.store');
    Route::get('/operation/{operation}/edit', 'edit')->name('operation.edit');
    Route::put('/operation/{operation}', 'update')->name('operation.update');
    Route::patch('/operation/{operation}/tick', 'tick')->name('operation.tick');
    Route::delete('/operation/{operation}', 'destroy')->name('operation.destroy');
});

Route::controller(ProfileController::class)->middleware(['auth', 'password.confirm'])->group(function () {
    Route::get('/profile', 'edit')->name('profile.edit');
    Route::get('/profile/preferences', 'preferences')->name('profile.preferences');
    Route::get('/profile/password', 'password')->name('profile.password');
    Route::get('/profile/account', 'account')->name('profile.account');
    Route::patch('/profile', 'update')->name('profile.update');
    Route::delete('/profile', 'destroy')->name('profile.destroy')->middleware('throttle:account-deletion');
});

// Photo de profil : affichée sur toutes les pages (barre du haut), changée depuis le profil.
Route::controller(ProfileAvatarController::class)->group(function () {
    Route::get('/profile/avatar', 'show')->middleware('auth')->name('profile.avatar');
    Route::put('/profile/avatar', 'update')
        ->middleware(['auth', 'verified', 'password.confirm', 'throttle:avatar'])
        ->name('profile.avatar.update');
    Route::delete('/profile/avatar', 'destroy')
        ->middleware(['auth', 'verified', 'password.confirm'])
        ->name('profile.avatar.destroy');
});

// Thème : hors de password.confirm, l'interrupteur de la barre l'envoie depuis n'importe quelle page.
Route::put('/profile/color-scheme', ColorSchemeController::class)
    ->middleware(['auth', 'throttle:color-scheme'])
    ->name('profile.color-scheme');

Route::get('/profile/export', DataExportController::class)
    ->middleware(['auth', 'password.confirm'])
    ->name('profile.export');

// Administration : `admin` répond 404 à qui n'est pas administrateur, avant `verified` et
// `password.confirm`. La dernière route renvoie aussi 404 à toute autre adresse et toute autre
// méthode de la zone, qui sans elle répondraient 405 et trahiraient une route existante.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'verified', 'password.confirm'])->group(function () {
    Route::get('/server', ServerStatusController::class)->name('server');

    Route::any('/{path?}', fn () => abort(404))->where('path', '.*');
});

require __DIR__.'/auth.php';
