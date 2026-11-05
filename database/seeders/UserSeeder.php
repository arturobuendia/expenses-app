<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Create the single account this app is built for.
     *
     * Idempotent on purpose: running it against an existing database adds the
     * user when missing and never touches an already existing account.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'arturo@apperture.mx'],
            [
                'name' => 'Arturo',
                'password' => Hash::make('pAsw000rD##'),
            ],
        );
    }
}
