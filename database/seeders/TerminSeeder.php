<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Termin;
use App\Models\Proyek;
use App\Models\Invoice;

class TerminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan proyek dan invoice ada
        $proyek = Proyek::find(1);
        $invoice = Invoice::find(1);

        if (!$proyek || !$invoice || $invoice->proyek_id != $proyek->id) {
            $this->command->error('Proyek atau Invoice tidak ditemukan, atau tidak saling terhubung.');
            return;
        }

        $nilai_termin = 15000000;
        $persentase_dp = 30;
        $nilai_dp = round($nilai_termin * ($persentase_dp / 100), 2);
        $nilai_pelunasan = $nilai_termin - $nilai_dp;

        Termin::create([
            'proyek_id' => $proyek->id,
            'invoice_id' => $invoice->id,
            'nama_termin' => 'Termin DP Tahap 1',
            'jenis_termin' => 'DP',
            'termin_ke' => 1,
            'target_progress' => 20,
            'nilai_termin' => $nilai_termin,
            'persentase_dp' => $persentase_dp,
            'nilai_dp' => $nilai_dp,
            'nilai_pelunasan' => $nilai_pelunasan,
            'tanggal_dp' => now()->toDateString(),
            'tanggal_pelunasan' => now()->addDays(30)->toDateString(),
            'status_termin' => 'Belum Dibayar',
            'status_approval' => 'Pending',
            'keterangan' => 'Termin dummy untuk pengujian sistem',
        ]);

        $this->command->info('✅ Termin dummy berhasil dibuat.');
    }
}
