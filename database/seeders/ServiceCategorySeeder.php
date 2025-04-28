<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use App\Models\Unit;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Ambil unit id berdasarkan kode unit
          $transaksiUnit = Unit::where('unit_code', 'T001')->first();
          $jasaUnit = Unit::where('unit_code', 'J001')->first();
          $setUnit = Unit::where('unit_code', 'S001')->first();

          $serviceCategories = [
              [
                  'nama_kategori' => 'Jasa Pemasangan',
                  'jenis' => 'pengeluaran',
                  'harga' => 500000.00,
                  'unit_id' => $jasaUnit?->id, // pakai jasa
                  'deskripsi' => 'Biaya jasa pemasangan per unit.'
              ],
              [
                  'nama_kategori' => 'Biaya Admin',
                  'jenis' => 'pengeluaran',
                  'harga' => 2500.00,
                  'unit_id' => $transaksiUnit?->id, // pakai transaksi
                  'deskripsi' => 'Biaya administrasi tambahan untuk transaksi.'
              ],
              [
                  'nama_kategori' => 'Service Perbaikan',
                  'jenis' => 'pengeluaran',
                  'harga' => 300000.00,
                  'unit_id' => $setUnit?->id, // pakai set
                  'deskripsi' => 'Biaya perbaikan alat dan mesin.'
              ],
          ];

          foreach ($serviceCategories as $category) {
              ServiceCategory::create($category);
          }
      }
}
