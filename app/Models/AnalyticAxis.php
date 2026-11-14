<?php

namespace App\Models;

use App\Enums\AxisColor;
use App\Models\Scopes\AuthUserParentScope;
use App\Support\AxisIcons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Axe analytique d'un portfolio, entre lesquels se répartit le montant d'une opération.
 *
 * L'archivage passe par SoftDeletes : un axe archivé ne se choisit plus pour une nouvelle ligne,
 * mais reste affiché sur les opérations qui l'utilisent.
 */
class AnalyticAxis extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'analytic_axes';

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'portfolio_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'description' => '',
        'icon' => AxisIcons::DEFAULT,
        'color' => 'indigo',
    ];

    /**
     * Conversions des attributs : la couleur en AxisColor.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => AxisColor::class,
        ];
    }

    /**
     * Restreint les axes à ceux des portfolios de l'utilisateur connecté.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new AuthUserParentScope('portfolio'));
    }

    /**
     * Portfolio de l'axe.
     *
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * Axe tel que le propose l'éditeur d'une répartition (resources/js/analytic-lines.js) : avec son
     * icône colorée, rendue ici en SVG.
     *
     * @return array{id: int, name: string, archived: bool, icon: string}
     */
    public function formOption(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'archived' => $this->trashed(),
            'icon' => svg("lucide-{$this->icon}", "size-4 {$this->color->textClasses()}", ['aria-hidden' => 'true'])->toHtml(),
        ];
    }

    /**
     * Lignes analytiques des opérations réparties sur l'axe.
     *
     * @return HasMany<AnalyticLine, $this>
     */
    public function analyticLines(): HasMany
    {
        return $this->hasMany(AnalyticLine::class);
    }

    /**
     * Comptes dont la répartition analytique par défaut contient l'axe.
     *
     * @return BelongsToMany<Account, $this>
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_analytic_axis');
    }
}
