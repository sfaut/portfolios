<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Remplit la base d'une nouvelle installation.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
    }
}
