<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['unit_name' => 'pcs', 'unit_code' => 'P001'],
            ['unit_name' => 'unit', 'unit_code' => 'U001'],
            ['unit_name' => 'set', 'unit_code' => 'S001'],
            ['unit_name' => 'ls', 'unit_code' => 'L001'],
            ['unit_name' => 'm', 'unit_code' => 'M001'],
            ['unit_name' => 'kwp', 'unit_code' => 'K001'],
            ['unit_name' => 'transaksi', 'unit_code' => 'T001'],
            ['unit_name' => 'paket', 'unit_code' => 'P002'],
            ['unit_name' => 'rol', 'unit_code' => 'R001'],
            ['unit_name' => 'jasa', 'unit_code' => 'J001']
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
