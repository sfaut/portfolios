<?php

namespace App\Models;

use App\Enums\ColorScheme;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Utilisateur de l'application, propriétaire de ses portfolios.
 *
 * Son adresse doit être vérifiée pour accéder aux pages métier (middleware `verified`). Sa photo de
 * profil, facultative, est rangée à plat sur le disque privé des photos (avatarDisk()), sous
 * `avatars/{id}-{aléatoire}.webp` (`avatar_path`, '' sans photo), et supprimée avec lui.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $attributes = [
        'is_admin' => false,
        'color_scheme' => 'system',
        'avatar_path' => '',
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

    /**
     * Supprime les photos de l'utilisateur avec lui.
     */
    protected static function booted(): void
    {
        static::deleted(function (User $user): void {
            $user->deleteAvatarFiles();
        });
    }

    /**
     * Disque privé des photos de profil, nommé par `filesystems.avatar_disk` (variable `AVATAR_DISK`) :
     * `local` sur un serveur, le disque d'un bucket privé sur Laravel Cloud.
     */
    public static function avatarDisk(): FilesystemAdapter
    {
        return Storage::disk(config('filesystems.avatar_disk'));
    }

    /**
     * Supprime les fichiers de photo de l'utilisateur, retrouvés par leur préfixe `avatars/{id}-` : sa
     * photo, et tout orphelin d'un envoi interrompu entre l'écriture du fichier et son enregistrement
     * en base. Le tiret du préfixe distingue l'utilisateur 1 de l'utilisateur 12.
     *
     * Le dossier `avatars` est listé en entier : un fichier par utilisateur qui a une photo.
     *
     * @param  string  $except  Chemin à garder, la photo qui vient d'être enregistrée ; '' pour aucun.
     */
    public function deleteAvatarFiles(string $except = ''): void
    {
        $disk = self::avatarDisk();
        $prefix = "avatars/{$this->id}-";

        $disk->delete(array_values(array_filter(
            $disk->files('avatars'),
            fn (string $path): bool => str_starts_with($path, $prefix) && $path !== $except,
        )));
    }

    /**
     * Adresse de la photo de l'utilisateur, '' sans photo. Elle ne sert que pour l'utilisateur connecté
     * (route `profile.avatar`), et change avec chaque photo (`v`, nom du fichier).
     */
    public function avatarUrl(): string
    {
        if ($this->avatar_path === '') {
            return '';
        }

        return route('profile.avatar', ['v' => pathinfo($this->avatar_path, PATHINFO_FILENAME)]);
    }

    /**
     * Initiales de l'utilisateur, en capitales, accents gardés : la première lettre du prénom, puis
     * celle du nom (« SF ») ; avec un seul des deux, sa lettre ; sans aucun, celle de l'adresse.
     */
    public function initials(): string
    {
        $letters = array_filter(
            [mb_substr(trim($this->first_name ?? ''), 0, 1), mb_substr(trim($this->last_name ?? ''), 0, 1)],
            fn (string $letter): bool => $letter !== '',
        );

        return mb_strtoupper(implode('', $letters) ?: mb_substr((string) $this->email, 0, 1));
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
