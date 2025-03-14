<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            ['nama_kategori' => 'Pendapatan Proyek', 'jenis' => 'pemasukan', 'deskripsi' => 'Pembayaran dari klien'],
            ['nama_kategori' => 'Pendapatan Jasa Instalasi', 'jenis' => 'pemasukan', 'deskripsi' => 'Pendapatan dari pemasangan alat listrik'],
            ['nama_kategori' => 'Pembelian Material PLTS', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk material PLTS'],
            ['nama_kategori' => 'Biaya Transportasi', 'jenis' => 'pengeluaran', 'deskripsi' => 'Biaya pengiriman alat menggunakan truk'],
            ['nama_kategori' => 'Gaji Karyawan', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk gaji staf dan teknisi'],
        ];

        foreach ($kategori as $data) {
            Kategori::create($data);
        }
    }
}
