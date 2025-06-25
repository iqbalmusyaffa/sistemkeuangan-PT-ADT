<?php

namespace Database\Factories;
use App\Models\Invoice;
use App\Models\Proyek;
use App\Models\Termin;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Termin>
 */
class TerminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           'proyek_id' => Proyek::factory(),
            'invoice_id' => Invoice::factory(),
            'nama_termin' => 'Termin 1',
            'jenis_termin' => 'DP',
            'termin_ke' => 1,
            'nilai_termin' => 500000,
            'persentase_dp' => 20,
            'status_termin' => 'Belum Dibayar',
            'status_approval' => 'Pending',
            'tanggal_dp' => now(),
            'keterangan' => 'Test Termin',
        ];
    }
}
