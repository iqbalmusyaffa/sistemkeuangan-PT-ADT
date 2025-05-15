<?php

namespace App\Imports;

use App\Models\Termin;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TerminImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Validate required fields
        $validator = Validator::make($row, [
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'status' => ['required', Rule::in(['Belum Dibayar', 'DP Dibayar', 'Lunas'])],
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
            'keterangan' => 'nullable|string|max:1000'
        ], [
            'nama_termin.required' => 'Nama termin harus diisi',
            'nilai_termin.required' => 'Nilai termin harus diisi',
            'nilai_termin.numeric' => 'Nilai termin harus berupa angka',
            'nilai_termin.min' => 'Nilai termin tidak boleh negatif',
            'dp_percentage.required' => 'Persentase DP harus diisi',
            'dp_percentage.numeric' => 'Persentase DP harus berupa angka',
            'dp_percentage.min' => 'Persentase DP tidak boleh negatif',
            'dp_percentage.max' => 'Persentase DP tidak boleh lebih dari 100',
            'status.required' => 'Status harus diisi',
            'status.in' => 'Status tidak valid',
            'tanggal_dp.date' => 'Format tanggal DP tidak valid',
            'tanggal_pelunasan.date' => 'Format tanggal pelunasan tidak valid',
            'tanggal_pelunasan.after_or_equal' => 'Tanggal pelunasan harus sama dengan atau setelah tanggal DP'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Invalid data in row: ' . json_encode($validator->errors()));
        }

        // Calculate DP and Pelunasan values
        $nilaiDp = $row['nilai_termin'] * ($row['dp_percentage'] / 100);
        $nilaiPelunasan = $row['nilai_termin'] - $nilaiDp;

        return new Termin([
            'nama_termin' => $row['nama_termin'],
            'nilai_termin' => $row['nilai_termin'],
            'dp_percentage' => $row['dp_percentage'],
            'nilai_dp' => $nilaiDp,
            'nilai_pelunasan' => $nilaiPelunasan,
            'status_termin' => $row['status'],
            'tanggal_dp' => $row['tanggal_dp'] ?? null,
            'tanggal_pelunasan' => $row['tanggal_pelunasan'] ?? null,
            'proyek_id' => request()->input('project_id'),
            'keterangan' => $row['keterangan'] ?? null
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'status' => ['required', Rule::in(['Belum Dibayar', 'DP Dibayar', 'Lunas'])],
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
            'keterangan' => 'nullable|string|max:1000'
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_termin.required' => 'Nama termin harus diisi',
            'nilai_termin.required' => 'Nilai termin harus diisi',
            'nilai_termin.numeric' => 'Nilai termin harus berupa angka',
            'nilai_termin.min' => 'Nilai termin tidak boleh negatif',
            'dp_percentage.required' => 'Persentase DP harus diisi',
            'dp_percentage.numeric' => 'Persentase DP harus berupa angka',
            'dp_percentage.min' => 'Persentase DP tidak boleh negatif',
            'dp_percentage.max' => 'Persentase DP tidak boleh lebih dari 100',
            'status.required' => 'Status harus diisi',
            'status.in' => 'Status tidak valid',
            'tanggal_dp.date' => 'Format tanggal DP tidak valid',
            'tanggal_pelunasan.date' => 'Format tanggal pelunasan tidak valid',
            'tanggal_pelunasan.after_or_equal' => 'Tanggal pelunasan harus sama dengan atau setelah tanggal DP'
        ];
    }
}
