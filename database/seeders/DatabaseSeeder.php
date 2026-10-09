<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    //use WithoutModelEvents; EDB 09/03/26: avoid the custom trait to generate ulid based on the event creating.

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                RolesSeeder::class,
                //UserSeeder::class,
                //LocationSeeder::class,
                OlfactoryTaxonomySeeder::class,
                CertificationTypeSeeder::class,
                PasswordGrantClient::class,
            ]
        );

        $this->callWith(RoasterySeeder::class, ['count' => 25]);
        $this->callWith(CoffeeSeeder::class, ['count' => 50]);
        $this->callWith(CoffeeInventorySeeder::class, ['count' => 30]);
        for ($i = 1; $i <= 20; $i++) {
            $locations = mt_rand(1, 4);
            $this->callWith(LocationSeeder::class, ['locations' => $locations]);
        }
    }
}
