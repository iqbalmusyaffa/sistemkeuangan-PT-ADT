<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Income;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Termin;
use Illuminate\Support\Str;

class IncomeSeeder extends Seeder
{
    public function run(): void
    {
        $proyek = Proyek::find(1);
        $invoice = Invoice::where('proyek_id', $proyek->id)->first();

        if (!$proyek || !$invoice) {
            $this->command->error('Proyek atau invoice tidak ditemukan.');
            return;
        }

        $jumlah = 4500000;

        // Auto-generate Termin (like in controller)
        $nextKe = Termin::where('proyek_id', $proyek->id)->max('termin_ke') + 1;

        $termin = Termin::create([
            'proyek_id' => $proyek->id,
            'invoice_id' => $invoice->id,
            'kode_termin' => 'TRM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'nama_termin' => 'Termin Auto Seeder',
            'jenis_termin' => 'DP',
            'termin_ke' => $nextKe,
            'nilai_termin' => $jumlah,
            'persentase_dp' => 100,
            'nilai_dp' => $jumlah,
            'nilai_pelunasan' => 0,
            'status_termin' => 'DP Dibayar',
            'status_approval' => 'Pending',
            'tanggal_dp' => now(),
            'target_progress' => 20,
        ]);

        Income::create([
            'kode_transaksi' => 'INC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'kategori_id' => 1,
            'payment_method_id' => 2,
            'proyek_id' => $proyek->id,
            'termin_id' => $termin->id,
            'invoice_id' => $invoice->id,
            'type' => 'dp',
            'jumlah' => $jumlah,
            'tanggal' => now()->toDateString(),
            'status' => 'pending',
            'deskripsi' => 'Pemasukan via Termin Seeder',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        $this->command->info('✅ Income dan Termin dummy berhasil dibuat.');
    }
}
