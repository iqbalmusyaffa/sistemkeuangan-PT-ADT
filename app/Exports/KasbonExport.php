<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class KasbonExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $kasbons;

    public function __construct($kasbons)
    {
        $this->kasbons = $kasbons;
    }

    public function collection()
    {
        return $this->kasbons;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Kasbon',
            'Nama Pengaju',
            'Proyek',
            'Jumlah (Rp)',
            'Deskripsi',
            'Status',
            'Tanggal Permintaan',
            'Tanggal Jatuh Tempo',
            'Tanggal Persetujuan',
            'Tanggal Pencairan',
            'Tanggal Pelunasan',
            'Metode Pembayaran',
            'Catatan'
        ];
    }

    public function map($kasbon): array
    {
        return [
            $kasbon->id,
            $kasbon->nomor_kasbon,
            $kasbon->user_name,
            $kasbon->proyek ? $kasbon->proyek->nama_proyek : '-',
            number_format($kasbon->amount, 2, ',', '.'),
            $kasbon->description,
            $kasbon->status_label,
            $kasbon->kasbon_date ? date('d/m/Y', strtotime($kasbon->kasbon_date)) : '-',
            $kasbon->due_date ? date('d/m/Y', strtotime($kasbon->due_date)) : '-',
            $kasbon->approval_date ? date('d/m/Y', strtotime($kasbon->approval_date)) : '-',
            $kasbon->disbursement_date ? date('d/m/Y', strtotime($kasbon->disbursement_date)) : '-',
            $kasbon->settlement_date ? date('d/m/Y', strtotime($kasbon->settlement_date)) : '-',
            $kasbon->payment_method,
            $kasbon->notes ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:N1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ]
        ];
    }
} 