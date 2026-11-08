<?php

namespace App\Models;

use App\Models\Scopes\AuthUserParentScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $with = [
        'account.portfolio.user',
    ];

    protected $fillable = [
        'label',
        'amount',
        'delivery_at',
        'account_id',
    ];

    /**
     * Restreint les opérations à celles des comptes de l'utilisateur connecté.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new AuthUserParentScope('account'));
    }

    /**
     * Compte de l'opération, archivé ou non.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Lignes analytiques de l'opération, qui répartissent son montant entre des axes.
     *
     * @return HasMany<AnalyticLine, $this>
     */
    public function analyticLines(): HasMany
    {
        return $this->hasMany(AnalyticLine::class);
    }
}
