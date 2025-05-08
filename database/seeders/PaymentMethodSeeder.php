<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('payment_methods')->insert([
            [
                'nama_metode' => 'Transfer Bank',
                'deskripsi'   => 'Pembayaran dilakukan melalui transfer ke rekening bank.',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'nama_metode' => 'Tunai',
                'deskripsi'   => 'Pembayaran dilakukan secara langsung dengan uang tunai.',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'nama_metode' => 'QRIS',
                'deskripsi'   => 'Pembayaran melalui kode QRIS menggunakan e-wallet.',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'nama_metode' => 'E-Wallet',
                'deskripsi'   => 'Pembayaran dilakukan melalui dompet digital seperti OVO, DANA, dll.',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
