<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountReport extends Model
{
    use SoftDeletes;

    protected $with = [
        //
    ];

    protected $fillable = [
        // Readonly view
    ];

    protected function casts(): array
    {
        return [
            'operation_first_at' => 'date',
            'operation_last_at' => 'date',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * Axes analytiques affectés au compte, pour le repérer, par nom ; un axe archivé reste affiché.
     *
     * @return BelongsToMany<AnalyticAxis, $this>
     */
    public function analyticAxes(): BelongsToMany
    {
        return $this->belongsToMany(AnalyticAxis::class, 'account_analytic_axis', 'account_id')
            ->withTrashed()
            ->orderBy('analytic_axes.name');
    }
}
