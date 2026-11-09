<?php

namespace App\Models;

use App\Enums\ColorScheme;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Utilisateur de l'application, propriétaire de ses portfolios.
 *
 * Son adresse doit être vérifiée pour accéder aux pages métier (middleware `verified`).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_admin' => false,
        'color_scheme' => 'system',
    ];

    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'password',
        'color_scheme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'color_scheme' => ColorScheme::class,
            'email_verified_at' => 'datetime',
        ];
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, Portfolio::class);
    }

    public function operations(): HasManyThrough
    {
        return $this->accounts->hasManyThrough(Operation::class, Account::class);
    }
}
