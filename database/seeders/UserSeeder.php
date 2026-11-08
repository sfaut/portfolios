<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Crée l'administrateur (id 1) depuis les variables `APP_ADMIN_*` du `.env`, lues par
     * `config('app.admin')`.
     *
     * Les quatre variables sont obligatoires et n'ont pas de valeur par défaut : une installation ne
     * peut donc pas garder des identifiants d'administrateur publiés avec le code.
     *
     * Une base qui a déjà un administrateur (installation sur une base existante, seed relancé) est
     * laissée telle quelle, sans contrôle des variables.
     *
     * @throws RuntimeException Si une variable manque ou n'est pas valide (longueur, adresse, mot de
     *                          passe trop faible).
     */
    public function run(): void
    {
        $existing_admin = User::query()->where('is_admin', true)->first();

        if ($existing_admin !== null) {
            $this->command?->info("Administrateur déjà présent ({$existing_admin->email}) : aucun compte créé.");

            return;
        }

        $admin = config('app.admin');

        $values = [
            'APP_ADMIN_LAST_NAME' => $admin['last_name'],
            'APP_ADMIN_FIRST_NAME' => $admin['first_name'],
            'APP_ADMIN_EMAIL' => $admin['email'],
            'APP_ADMIN_PASSWORD' => $admin['password'],
        ];

        $rules = [
            'APP_ADMIN_LAST_NAME' => ['required', 'string', 'max:30'],
            'APP_ADMIN_FIRST_NAME' => ['required', 'string', 'max:30'],
            'APP_ADMIN_EMAIL' => ['required', 'string', 'email', 'max:100'],
            'APP_ADMIN_PASSWORD' => ['required', 'string', Password::defaults()],
        ];

        $variable_names = array_keys($rules);
        $validator = Validator::make($values, $rules, attributes: array_combine($variable_names, $variable_names));

        if ($validator->fails()) {
            $errors = implode(PHP_EOL, $validator->errors()->all());

            throw new RuntimeException("Administrateur non créé, à corriger dans le .env :\n{$errors}");
        }

        User::create([
            'id' => 1,
            'last_name' => $admin['last_name'],
            'first_name' => $admin['first_name'],
            'email' => $admin['email'],
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => Hash::make($admin['password']),
        ]);
    }
}
