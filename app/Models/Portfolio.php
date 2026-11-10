<?php

namespace App\Models;

use App\Models\Scopes\AuthUserScope;
use App\Observers\PortfolioObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use SoftDeletes;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    protected $attributes = [
        'description' => '',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new AuthUserScope);

        Portfolio::observe(PortfolioObserver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class)->orderBy('name');
    }

    public function accountsReport(): HasMany
    {
        return $this->hasMany(AccountReport::class)->orderBy('name');
    }

    /**
     * Axes analytiques du portfolio, par nom.
     *
     * @return HasMany<AnalyticAxis, $this>
     */
    public function analyticAxes(): HasMany
    {
        return $this->hasMany(AnalyticAxis::class)->orderBy('name');
    }

    /**
     * Axes analytiques qu'un compte du portfolio peut recevoir, par nom : les axes non archivés, plus
     * les axes archivés déjà affectés au compte, qui restent cochés tant qu'on ne les retire pas.
     *
     * @param  Account|null  $account  Compte modifié, null en création.
     * @return Collection<int, AnalyticAxis>
     */
    public function assignableAnalyticAxes(?Account $account = null): Collection
    {
        $assigned_axis_ids = $account?->analyticAxes()->pluck('analytic_axes.id')->all() ?? [];

        return $this->analyticAxes()
            ->withTrashed()
            ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereIn('id', $assigned_axis_ids))
            ->get();
    }

    public function operations(): HasManyThrough
    {
        return $this->hasManyThrough(Operation::class, Account::class);
    }

    /**
     * Mois où les comptes du portfolio, archivés compris, ont au moins une opération (date
     * effective), du plus récent au plus ancien.
     *
     * @return list<string> Mois « 2026-11 ».
     */
    public function operationMonths(): array
    {
        return Operation::query()
            ->whereIn('account_id', $this->accounts()->withTrashed()->select('id'))
            ->selectRaw("DATE_FORMAT(delivery_at, '%Y-%m') AS month")
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->all();
    }
}
