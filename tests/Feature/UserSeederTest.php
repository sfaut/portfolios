<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['app.admin' => [
        'last_name' => 'Martin',
        'first_name' => 'Claire',
        'email' => 'claire.martin@example.net',
        'password' => 'Un mot de passe sûr',
    ]]);
});

test('the seeder creates the administrator from the configuration', function () {
    $this->seed(UserSeeder::class);

    $admin = User::find(1);
    expect($admin)
        ->last_name->toBe('Martin')
        ->first_name->toBe('Claire')
        ->email->toBe('claire.martin@example.net')
        ->is_admin->toBeTruthy()
        ->and(Hash::check('Un mot de passe sûr', $admin->password))->toBeTrue();
});

test('the seeder leaves a database that already has an administrator', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    config(['app.admin.password' => '']);

    $this->seed(UserSeeder::class);

    expect(User::sole()->is($admin))->toBeTrue();
});

test('the seeder refuses a missing or invalid administrator setting', function (string $key, string $value, string $variable) {
    config(["app.admin.{$key}" => $value]);

    expect(fn () => $this->seed(UserSeeder::class))
        ->toThrow(RuntimeException::class, $variable);

    $this->assertDatabaseEmpty('users');
})->with([
    'missing last name' => ['last_name', '', 'APP_ADMIN_LAST_NAME'],
    'missing first name' => ['first_name', '', 'APP_ADMIN_FIRST_NAME'],
    'missing email' => ['email', '', 'APP_ADMIN_EMAIL'],
    'invalid email' => ['email', 'claire.martin', 'APP_ADMIN_EMAIL'],
    'missing password' => ['password', '', 'APP_ADMIN_PASSWORD'],
    'short password' => ['password', 'court', 'APP_ADMIN_PASSWORD'],
]);
