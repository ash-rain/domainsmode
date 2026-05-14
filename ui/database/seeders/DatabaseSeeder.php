<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This seeder is run automatically on every `docker-compose up` via
     * the UI container entrypoint. It is fully idempotent — it will not
     * create duplicate records if the user already exists.
     *
     * Default credentials:
     *   Email    admin@domainsmode.local
     *   Password password
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@domainsmode.local'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ],
        );
    }
}
