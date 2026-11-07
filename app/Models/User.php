<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_admin' => false,
    ];

    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'password',
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
