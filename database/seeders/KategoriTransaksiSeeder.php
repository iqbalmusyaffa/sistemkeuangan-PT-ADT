<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;
use App\Models\Unit;

class KategoriTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        // Ambil ID unit berdasarkan kode unit
        $unitPLTS = Unit::where('unit_code', 'P001')->first()?->id;
        $unitListrik = Unit::where('unit_code', 'U001')->first()?->id;
        $unitPendukung = Unit::where('unit_code', 'L001')->first()?->id;

        $kategori = [
            // =================== KATEGORI PEMASUKAN ===================
            [
                'nama_kategori' => 'Pendapatan Proyek',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Pembayaran dari klien',
                'unit_id' => null
            ],
            [
                'nama_kategori' => 'Pendapatan Jasa Instalasi',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Pendapatan dari pemasangan alat listrik',
                'unit_id' => null
            ],
            [
                'nama_kategori' => 'Investasi Masuk',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Dana dari investor atau pemodal',
                'unit_id' => null
            ],
            [
                'nama_kategori' => 'Retensi Proyek',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Dana retensi proyek yang telah selesai',
                'unit_id' => null
            ],
            [
                'nama_kategori' => 'Pendapatan Lainnya',
                'jenis' => 'pemasukan',
                'deskripsi' => 'Pemasukan selain dari proyek utama',
                'unit_id' => null
            ],

            // =================== KATEGORI PENGELUARAN ===================
            [
                'nama_kategori' => 'Material PLTS',
                'jenis' => 'pengeluaran',
                'deskripsi' => 'Pengeluaran untuk material PLTS',
                'unit_id' => $unitPLTS
            ],
            [
                'nama_kategori' => 'Material Listrik',
                'jenis' => 'pengeluaran',
                'deskripsi' => 'Pengeluaran untuk material listrik',
                'unit_id' => $unitListrik
            ],
            [
                'nama_kategori' => 'Material Pendukung',
                'jenis' => 'pengeluaran',
                'deskripsi' => 'Pengeluaran untuk material pendukung',
                'unit_id' => $unitPendukung
            ],
        ];

        // Masukkan data kategori ke database jika belum ada
        foreach ($kategori as $data) {
            Kategori::firstOrCreate(
                ['nama_kategori' => $data['nama_kategori']],
                $data
            );
        }
    }
}
