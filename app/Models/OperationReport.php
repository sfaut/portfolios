<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationReport extends Model
{
    protected $with = [
        'account',
        'account.portfolio',
    ];

    protected $fillable = [
        // Readonly view
    ];

    protected function casts(): array
    {
        return [
            'delivery_at' => 'datetime',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Lignes analytiques de l'opération (la vue reprend l'identifiant de l'opération).
     *
     * @return HasMany<AnalyticLine, $this>
     */
    public function analyticLines(): HasMany
    {
        return $this->hasMany(AnalyticLine::class, 'operation_id');
    }
}
