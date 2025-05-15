<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MerekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mereks = [
            [
                'name' => '-',
                // 'deskripsi' => 'Untuk kategori jasa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kenika',
                // 'deskripsi' => 'Merek Kenika adalah produsen peralatan listrik berkualitas tinggi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Solana',
                // 'deskripsi' => 'Merek Solana dikenal dengan produk-produk inovatif dan ramah lingkungan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Deye',
                // 'deskripsi' => 'Merek Deye adalah pemimpin dalam teknologi energi terbarukan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('mereks')->insert($mereks);
    }
}
