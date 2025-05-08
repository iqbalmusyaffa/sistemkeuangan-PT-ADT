<?php

namespace App\Imports;

use App\Models\Termin;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TerminImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Termin([
            'nama_termin' => $row['nama_termin'],
            'nilai_termin' => $row['nilai_termin'],
            'dp_percentage' => $row['dp_percentage'],
            'status_termin' => $row['status'],
            'tanggal_dp' => $row['tanggal_dp'],
            'tanggal_pelunasan' => $row['tanggal_pelunasan'],
            'proyek_id' => request()->input('project_id') // Ambil dari request
        ]);
    }
}
