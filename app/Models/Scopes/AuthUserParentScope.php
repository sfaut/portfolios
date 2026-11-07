<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restreint un modèle aux enregistrements dont le parent appartient à l'utilisateur connecté.
 *
 * L'appartenance se vérifie de proche en proche : la requête sur le parent garde ses propres portées
 * globales, jusqu'à AuthUserScope sur Portfolio. Un parent archivé (suppression logique) reste pris en
 * compte : la portée ne filtre que sur le propriétaire.
 *
 * @see AuthUserScope
 */
class AuthUserParentScope implements Scope
{
    /**
     * @param  string  $relation  Relation vers le parent, dont le modèle utilise SoftDeletes.
     */
    public function __construct(
        private readonly string $relation,
    ) {
        //
    }

    /**
     * Ajoute la condition d'existence d'un parent appartenant à l'utilisateur connecté.
     *
     * @param  Builder<Model>  $builder  Requête à restreindre.
     * @param  Model  $model  Modèle interrogé.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas($this->relation, fn (Builder $parent_query) => $parent_query->withTrashed());
    }
}
