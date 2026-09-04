<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Portfolio;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'id' => 1,
            'last_name' => env('APP_ADMIN_LAST_NAME', 'One'),
            'first_name' => env('APP_ADMIN_FIRST_NAME', 'Sim'),
            'email' => env('APP_ADMIN_EMAIL', 'sim.one@example.net'),
            'is_admin' => true,
            'password' => Hash::make(env('APP_ADMIN_PASSWORD', '4dminPa55word!!')),
        ]);
    }
}
