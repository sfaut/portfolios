<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

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

    /**
     * Répartition analytique en clair, « Cours piano 50 % · Cours échecs 50 % », vide sans ligne.
     *
     * @return Attribute<string, never>
     */
    protected function analyticBreakdown(): Attribute
    {
        return Attribute::get(fn (): string => $this->analyticLines
            ->map(fn (AnalyticLine $line): string => "{$line->analyticAxis->name} ".Number::percentage($line->percentage, maxPrecision: 2, locale: 'fr'))
            ->join(' · '));
    }
}
