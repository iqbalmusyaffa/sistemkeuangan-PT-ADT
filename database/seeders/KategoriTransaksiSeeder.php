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
            // ['nama_kategori' => 'Pendapatan Proyek', 'jenis' => 'pemasukan', 'deskripsi' => 'Pembayaran dari klien'],
            // ['nama_kategori' => 'Pendapatan Jasa Instalasi', 'jenis' => 'pemasukan', 'deskripsi' => 'Pendapatan dari pemasangan alat listrik'],
            ['nama_kategori' => 'Material PLTS', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk material PLTS'],
            ['nama_kategori' => 'Material Listrik', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk material listrik'],
            ['nama_kategori' => 'Material Pendukung', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk material pendukung'],
            // ['nama_kategori' => 'Jasa Instalasi', 'jenis' => 'pengeluaran', 'deskripsi' => 'Biaya jasa instalasi'],
            // ['nama_kategori' => 'Jasa Pengiriman', 'jenis' => 'pengeluaran', 'deskripsi' => 'Biaya pengiriman dan transportasi'],
            // ['nama_kategori' => 'Jasa Lain-lain', 'jenis' => 'pengeluaran', 'deskripsi' => 'Biaya jasa lainnya'],
            // ['nama_kategori' => 'Gaji Karyawan', 'jenis' => 'pengeluaran', 'deskripsi' => 'Pengeluaran untuk gaji staf dan teknisi'],
            // ['nama_kategori' => 'Jasa Lain - Admin Bank', 'jenis' => 'pengeluaran', 'deskripsi' => 'Biaya administrasi transfer bank'],
        ];

        foreach ($kategori as $data) {
            Kategori::create($data);
        }
    }
}
