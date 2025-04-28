<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('proyeks')->insert([
            [
                'nama_customer' => 'Budi Santoso',
                'nama_proyek' => 'Pembangunan Gudang Surabaya',
                'nama_perusahaan' => 'PT Sukses Makmur',
                'alamat' => 'Jl. Raya Industri No. 88, Surabaya',
                'no_telp' => '081234567890',
                'email' => 'budi@suksesmakmur.com',
                'lokasi' => 'Surabaya',
                'anggaran_kontrak' => 1500000000.00,
                'tanggal_mulai' => '2025-05-01',
                'tanggal_selesai' => '2025-11-30',
                'status_project' => 'Berjalan',
                'deskripsi' => 'Proyek pembangunan gudang industri di Surabaya.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_customer' => 'Siti Aminah',
                'nama_proyek' => 'Renovasi Kantor Pusat',
                'nama_perusahaan' => 'CV Maju Jaya',
                'alamat' => 'Jl. Merdeka No. 123, Jakarta',
                'no_telp' => '082345678901',
                'email' => 'siti@majupusat.com',
                'lokasi' => 'Jakarta',
                'anggaran_kontrak' => 750000000.00,
                'tanggal_mulai' => '2025-06-15',
                'tanggal_selesai' => '2025-09-15',
                'status_project' => 'Berjalan',
                'deskripsi' => 'Renovasi total gedung kantor pusat di Jakarta.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_customer' => 'Andi Wijaya',
                'nama_proyek' => 'Pembangunan Apartemen',
                'nama_perusahaan' => 'PT Properti Nusantara',
                'alamat' => 'Jl. Gatot Subroto No. 45, Bandung',
                'no_telp' => '083212345678',
                'email' => 'andi@propertinusantara.com',
                'lokasi' => 'Bandung',
                'anggaran_kontrak' => 3000000000.00,
                'tanggal_mulai' => '2025-07-01',
                'tanggal_selesai' => '2026-07-01',
                'status_project' => 'Berjalan',
                'deskripsi' => 'Pembangunan apartemen mewah di kawasan pusat kota Bandung.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
