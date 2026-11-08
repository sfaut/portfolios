<?php

namespace App\Models;

use App\Http\Requests\OperationRequest;
use App\Models\Scopes\AuthUserParentScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne analytique : part (en pourcentage) du montant d'une opération attribuée à un axe.
 *
 * Les lignes d'une opération totalisent 100 %, ce que contrôle OperationRequest.
 *
 * @see OperationRequest
 */
class AnalyticLine extends Model
{
    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'analytic_axis_id',
        'percentage',
    ];

    /**
     * Restreint les lignes à celles des opérations de l'utilisateur connecté.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new AuthUserParentScope('operation'));
    }

    /**
     * Conversions des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
        ];
    }

    /**
     * Opération répartie.
     *
     * @return BelongsTo<Operation, $this>
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Axe de la ligne, archivé ou non.
     *
     * @return BelongsTo<AnalyticAxis, $this>
     */
    public function analyticAxis(): BelongsTo
    {
        return $this->belongsTo(AnalyticAxis::class)->withTrashed();
    }
}
