<?php

namespace Database\Seeders;

use App\Models\Passport\Client;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PasswordGrantClient extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ClientRepository $client): void
    {
        $client->createPasswordGrantClient(
            name: 'password',
            confidential: false,
        );
    }
}
