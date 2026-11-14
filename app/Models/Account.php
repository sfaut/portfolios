<?php

namespace App\Models;

use App\Models\Scopes\AuthUserParentScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $with = [
        'portfolio.user',
    ];

    protected $fillable = [
        'name',
        'description',
        'portfolio_id',
    ];

    protected $casts = [

    ];

    /**
     * Restreint les comptes à ceux des portfolios de l'utilisateur connecté.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new AuthUserParentScope('portfolio'));
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function operationsReport(): HasMany
    {
        return $this->hasMany(OperationReport::class);
    }

    /**
     * Répartition analytique par défaut du compte : ses axes, dans l'ordre choisi (position), chacun
     * avec sa part (pivot percentage, total de 100 %) ; un axe archivé reste affiché.
     *
     * @return BelongsToMany<AnalyticAxis, $this>
     */
    public function analyticAxes(): BelongsToMany
    {
        return $this->belongsToMany(AnalyticAxis::class, 'account_analytic_axis', 'account_id')
            ->withPivot('percentage', 'position')
            ->withTrashed()
            ->orderByPivot('position');
    }
}
