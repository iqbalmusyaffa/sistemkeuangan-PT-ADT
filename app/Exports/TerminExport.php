<?php

namespace App\Exports;

use App\Models\Termin;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TerminExport implements FromCollection, WithHeadings, WithMapping
{
    protected $termins;

    public function __construct($termins)
    {
        $this->termins = $termins;
    }

    public function collection()
    {
        return $this->termins;
    }

    public function headings(): array
    {
        return [
            'Nama Termin',
            'Jenis Termin',
            'Termin Ke',
            'Nilai Termin',
            'Persentase DP',
            'Nilai DP',
            'Nilai Pelunasan',
            'Tanggal DP',
            'Tanggal Pelunasan',
            'Status',
            'Status Approval',
            'Keterangan'
        ];
    }

    public function map($termin): array
    {
        return [
            $termin->nama_termin,
            $termin->jenis_termin,
            $termin->termin_ke,
            number_format($termin->nilai_termin, 0, ',', '.'),
            $termin->persentase_dp . '%',
            number_format($termin->nilai_dp, 0, ',', '.'),
            number_format($termin->nilai_pelunasan, 0, ',', '.'),
            $termin->tanggal_dp,
            $termin->tanggal_pelunasan,
            $termin->status_termin,
            $termin->status_approval,
            $termin->keterangan
        ];
    }
}
