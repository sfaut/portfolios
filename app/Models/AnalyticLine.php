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
     * Part d'un montant que représente la ligne, arrondie au centime, la moitié s'éloignant de zéro
     * (−0,025 donne −0,03), comme le formulaire de l'opération (resources/js/analytic-lines.js).
     *
     * Le calcul se fait en centimes et centièmes de pourcentage entiers, pour ne pas dépendre de
     * l'arrondi des flottants.
     *
     * @param  string  $amount  Montant à répartir, à deux décimales (montant de l'opération).
     */
    public function amountShare(string $amount): float
    {
        $cents = (int) round((float) $amount * 100);
        $hundredths = (int) round((float) $this->percentage * 100);

        $share = intdiv(abs($cents) * $hundredths + 5000, 10000);

        return ($cents < 0 ? -$share : $share) / 100;
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
