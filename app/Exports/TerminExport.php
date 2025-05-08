<?php

namespace App\Exports;

use App\Models\Termin;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TerminExport implements FromCollection, WithHeadings
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function collection()
    {
        return Termin::where('proyek_id', $this->projectId)
            ->get()
            ->map(function($item) {
                return [
                    'Nama Termin' => $item->nama_termin,
                    'Nilai Termin' => $item->nilai_termin,
                    'DP (%)' => $item->dp_percentage,
                    'Status' => $item->status_termin,
                    'Tanggal DP' => $item->tanggal_dp,
                    'Tanggal Pelunasan' => $item->tanggal_pelunasan
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama Termin',
            'Nilai Termin',
            'DP (%)',
            'Status',
            'Tanggal DP',
            'Tanggal Pelunasan'
        ];
    }
}
