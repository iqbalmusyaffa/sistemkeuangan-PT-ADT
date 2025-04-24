<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
         $this->call([
            KategoriTransaksiSeeder::class, // Memanggil seeder kategori transaksi
            UserSeeder::class, // Memanggil seeder user
            UnitsSeeder::class, // Memanggil seeder unit
            MerekSeeder::class, // Memanggil seeder merek
        ]);

    }
}
