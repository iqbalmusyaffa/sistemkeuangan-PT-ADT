<?php

namespace App\Http\Controllers;

use App\Models\Termin;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use Barryvdh\DomPDF\Facade\Pdf;

class TerminController extends Controller
{
    public function updateStatus(Request $request, $id)
    {
        try {
            $termin = Termin::findOrFail($id);
            $user = auth()->user();

            // Validate request
            $request->validate([
                'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
                'tanggal_dp_dibayar' => 'required_if:status_termin,DP Dibayar|date',
                'tanggal_pelunasan_dibayar' => 'required_if:status_termin,Lunas|date',
                'bukti_pembayaran' => 'required_if:status_termin,DP Dibayar,Lunas|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'keterangan' => 'nullable|string'
            ]);

            // Handle file upload
            if ($request->hasFile('bukti_pembayaran')) {
                $path = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');
                $termin->bukti_pembayaran_url = $path;
            }

            // Update termin status
            $termin->status_termin = $request->status_termin;
            $termin->tanggal_dp_dibayar = $request->tanggal_dp_dibayar;
            $termin->tanggal_pelunasan_dibayar = $request->tanggal_pelunasan_dibayar;
            $termin->keterangan = $request->keterangan;
            $termin->status_approval = 'Pending';
            $termin->save();

            // Create income record if status is DP Dibayar or Lunas
            if (in_array($request->status_termin, ['DP Dibayar', 'Lunas'])) {
                $amount = $request->status_termin === 'DP Dibayar' ? $termin->nilai_dp : $termin->nilai_pelunasan;

                Income::create([
                    'proyek_id' => $termin->proyek_id,
                    'kategori_id' => 1, // Assuming 1 is the ID for "Pembayaran Termin"
                    'payment_method_id' => 1, // Assuming 1 is the ID for "Transfer"
                    'jumlah' => $amount,
                    'tanggal' => $request->status_termin === 'DP Dibayar' ? $request->tanggal_dp_dibayar : $request->tanggal_pelunasan_dibayar,
                    'status' => 'Pending',
                    'deskripsi' => "Pembayaran {$request->status_termin} untuk termin {$termin->nama_termin}",
                    'bukti_pembayaran_url' => $path ?? null,
                    'termin_id' => $termin->id
                ]);
            }

            // Create expense record if status is DP Dibayar or Lunas
            if (in_array($request->status_termin, ['DP Dibayar', 'Lunas'])) {
                $amount = $request->status_termin === 'DP Dibayar' ? $termin->nilai_dp : $termin->nilai_pelunasan;

                Expense::create([
                    'proyek_id' => $termin->proyek_id,
                    'kategori_id' => 2, // Assuming 2 is the ID for "Pengeluaran Termin"
                    'payment_method_id' => 1, // Assuming 1 is the ID for "Transfer"
                    'jumlah' => $amount,
                    'tanggal' => $request->status_termin === 'DP Dibayar' ? $request->tanggal_dp_dibayar : $request->tanggal_pelunasan_dibayar,
                    'status' => 'Pending',
                    'deskripsi' => "Pengeluaran {$request->status_termin} untuk termin {$termin->nama_termin}",
                    'bukti_pembayaran_url' => $path ?? null,
                    'termin_id' => $termin->id
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Status termin berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approveStatus($id)
    {
        try {
            $termin = Termin::findOrFail($id);
            $user = auth()->user();

            // Check if user has permission to approve
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menyetujui termin'
                ], 403);
            }

            // Update termin approval status
            $termin->status_approval = 'Disetujui';
            $termin->approved_by = $user->id;
            $termin->approved_at = now();
            $termin->save();

            // Update related income status
            $income = Income::where('termin_id', $termin->id)->latest()->first();
            if ($income) {
                $income->status = 'Diterima';
                $income->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Status termin berhasil disetujui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectStatus($id)
    {
        try {
            $termin = Termin::findOrFail($id);
            $user = auth()->user();

            // Check if user has permission to reject
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menolak termin'
                ], 403);
            }

            // Update termin approval status
            $termin->status_approval = 'Ditolak';
            $termin->approved_by = $user->id;
            $termin->approved_at = now();
            $termin->save();

            // Update related income status
            $income = Income::where('termin_id', $termin->id)->latest()->first();
            if ($income) {
                $income->status = 'Ditolak';
                $income->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Status termin berhasil ditolak'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel($projectId)
    {
        try {
            $termins = Termin::where('proyek_id', $projectId)
                ->with(['proyek', 'invoice'])
                ->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $headers = [
                'Nama Termin', 'Jenis Termin', 'Termin Ke', 'Nilai Termin',
                'Persentase DP', 'Nilai DP', 'Nilai Pelunasan', 'Tanggal DP',
                'Tanggal Pelunasan', 'Status', 'Status Approval', 'Keterangan'
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Add data
            $row = 2;
            foreach ($termins as $termin) {
                $sheet->setCellValue('A' . $row, $termin->nama_termin);
                $sheet->setCellValue('B' . $row, $termin->jenis_termin);
                $sheet->setCellValue('C' . $row, $termin->termin_ke);
                $sheet->setCellValue('D' . $row, $termin->nilai_termin);
                $sheet->setCellValue('E' . $row, $termin->persentase_dp);
                $sheet->setCellValue('F' . $row, $termin->nilai_dp);
                $sheet->setCellValue('G' . $row, $termin->nilai_pelunasan);
                $sheet->setCellValue('H' . $row, $termin->tanggal_dp);
                $sheet->setCellValue('I' . $row, $termin->tanggal_pelunasan);
                $sheet->setCellValue('J' . $row, $termin->status_termin);
                $sheet->setCellValue('K' . $row, $termin->status_approval);
                $sheet->setCellValue('L' . $row, $termin->keterangan);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'L') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'termins_' . date('Y-m-d_His') . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);

            return response()->download($path)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportPDF($projectId)
    {
        try {
            $termins = Termin::where('proyek_id', $projectId)
                ->with(['proyek', 'invoice'])
                ->get();

            $pdf = PDF::loadView('exports.termins', [
                'termins' => $termins,
                'project' => $termins->first()->proyek
            ]);

            $filename = 'termins_' . date('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function importExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // Skip empty rows

                Termin::updateOrCreate(
                    [
                        'nama_termin' => $row[0],
                        'proyek_id' => $request->proyek_id
                    ],
                    [
                        'jenis_termin' => $row[1],
                        'termin_ke' => $row[2],
                        'nilai_termin' => $row[3],
                        'persentase_dp' => $row[4],
                        'nilai_dp' => $row[5],
                        'nilai_pelunasan' => $row[6],
                        'tanggal_dp' => $row[7],
                        'tanggal_pelunasan' => $row[8],
                        'status_termin' => $row[9],
                        'status_approval' => $row[10],
                        'keterangan' => $row[11]
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data termin berhasil diimpor'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $termin = Termin::findOrFail($id);

            $request->validate([
                'nama_termin' => 'required|string|max:255',
                'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
                'termin_ke' => 'nullable|integer|min:1',
                'nilai_termin' => 'required|numeric|min:0',
                'persentase_dp' => 'required|numeric|min:0|max:100',
                'nilai_dp' => 'required|numeric|min:0',
                'nilai_pelunasan' => 'required|numeric|min:0',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date',
                'keterangan' => 'nullable|string'
            ]);

            // Update termin
            $termin->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Termin berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
