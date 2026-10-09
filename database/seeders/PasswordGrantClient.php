<?php

namespace Database\Seeders;

use App\Models\Passport\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PasswordGrantClient extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ClientRepository $clients): void
    {
        $client = Client::front()->first()
            ?? $clients->createAuthorizationCodeGrantClient(
                name: 'front',
                redirectUris: [env('FRONT_URL', 'https://localhost:3000') . '/callback'],
                confidential: true,
            );
    }
}
