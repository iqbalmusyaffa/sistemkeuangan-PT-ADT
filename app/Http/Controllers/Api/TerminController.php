<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Termin;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TerminExport;
use App\Imports\TerminImport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Resources\TerminResource;
use Illuminate\Support\Facades\Storage;


class TerminController extends Controller
{
    // Ambil semua termin, bisa filter berdasarkan invoice_id
    public function index(Request $request)
    {
        try {
        $query = Termin::with(['proyek', 'invoice'])->orderBy('created_at', 'desc');

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        $termins = $query->get();

            if ($termins->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tidak ada data termin',
                    'data' => []
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => TerminResource::collection($termins)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in TerminController@index: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data termin'
            ], 500);
        }
    }

    // Simpan termin baru dengan validasi dan cek anggaran
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('Creating new termin:', $request->all());

            // Validate required fields first
            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'required|exists:invoices,id',
                'nama_termin' => 'required|string|max:255',
                'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
                'nilai_termin' => 'required|numeric|min:0',
                'persentase_dp' => 'required|numeric|min:0|max:100',
                'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
                'keterangan' => 'nullable|string',
                'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
            ]);

            // Validate invoice exists and belongs to project
            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->where('proyek_id', $validated['proyek_id'])
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice tidak ditemukan atau tidak terkait dengan proyek ini'
                ], 422);
            }

            // Calculate DP and pelunasan values
            $nilai_termin = $validated['nilai_termin'];
            $persentase_dp = $validated['persentase_dp'];
            $nilai_dp = round($nilai_termin * ($persentase_dp / 100), 2);
            $nilai_pelunasan = round($nilai_termin - $nilai_dp, 2);

            $terminData = [
                'proyek_id' => $validated['proyek_id'],
                'invoice_id' => $validated['invoice_id'],
                'nama_termin' => $validated['nama_termin'],
                'jenis_termin' => $validated['jenis_termin'],
                'termin_ke' => $request->input('termin_ke'),
                'nilai_termin' => $nilai_termin,
                'persentase_dp' => $persentase_dp,
                'nilai_dp' => $nilai_dp,
                'nilai_pelunasan' => $nilai_pelunasan,
                'tanggal_dp' => $validated['tanggal_dp'] ?? null,
                'tanggal_pelunasan' => $validated['tanggal_pelunasan'] ?? null,
                'status_termin' => $validated['status_termin'],
                'keterangan' => $validated['keterangan'] ?? null,
                'dibayar_oleh' => $validated['status_termin'] !== 'Belum Dibayar' ? auth()->id() : null,
                'bukti_pembayaran' => null,
                'tanggal_pelunasan_dibayar' => null
            ];

            // Filter only fillable fields
            $allowed = (new \App\Models\Termin)->getFillable();
            $terminDataFiltered = array_intersect_key($terminData, array_flip($allowed));
            
            // Create termin
            $termin = Termin::create($terminDataFiltered);

            // Handle file upload if present
            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
                $path = 'uploads/bukti_pembayaran/' . $filename;
                
                if (!Storage::exists('uploads/bukti_pembayaran')) {
                    Storage::makeDirectory('uploads/bukti_pembayaran');
                }
                
                if ($file->storeAs('uploads/bukti_pembayaran', $filename)) {
                    $termin->bukti_pembayaran = $path;
                    $termin->save();
                }
            }

            // Update invoice status
            if ($termin->invoice) {
                try {
                $termin->invoice->updateStatusFromTermins();
                } catch (\Exception $e) {
                    Log::error('Error updating invoice status:', [
                        'message' => $e->getMessage(),
                        'invoice_id' => $termin->invoice_id,
                        'termin_id' => $termin->id
                    ]);
                    // Don't throw the error, just log it
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Termin berhasil dibuat',
                'data' => new TerminResource($termin->load(['proyek', 'invoice']))
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating termin:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tampilkan detail termin
    public function show(Termin $termin)
    {
        $termin->load(['proyek', 'invoice']);
        return new TerminResource($termin);
    }

    // Update termin, dengan validasi dan cek batas anggaran
    public function update(Request $request, Termin $termin)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'required|exists:invoices,id',
            'nama_termin' => 'required|string|max:255',
            'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
            'termin_ke' => 'nullable|integer|min:1',
            'nilai_termin' => 'required|numeric|min:0',
            'persentase_dp' => 'required|numeric|min:0|max:100',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp', // deadline/jatuh tempo
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
            'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'dibayar_oleh' => 'nullable|exists:users,id'
        ]);

        // Handle file bukti pembayaran saat update
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
            $path = 'uploads/bukti_pembayaran/' . $filename;
            if (!\Storage::exists('uploads/bukti_pembayaran')) {
                \Storage::makeDirectory('uploads/bukti_pembayaran');
            }
            if (!$file->storeAs('uploads/bukti_pembayaran', $filename)) {
                throw new \Exception('Failed to store file');
            }
            // Hapus file lama jika ada
            if ($termin->bukti_pembayaran) {
                \Storage::delete($termin->bukti_pembayaran);
            }
            $validated['bukti_pembayaran'] = $path;
        }

        // Validasi invoice terkait proyek
        $invoice = \App\Models\Invoice::where('id', $validated['invoice_id'])
            ->where('proyek_id', $validated['proyek_id'])
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak valid untuk proyek ini'], 422);
        }

        // Hitung nilai DP dan pelunasan
        $nilai_dp = $validated['nilai_termin'] * ($validated['persentase_dp'] / 100);
        $nilai_pelunasan = $validated['nilai_termin'] - $nilai_dp;

        $termin->update(array_merge($validated, [
            'nilai_dp' => $nilai_dp,
            'nilai_pelunasan' => $nilai_pelunasan,
            'dibayar_oleh' => $validated['status_termin'] !== 'Belum Dibayar' ? auth()->id() : null,
        ]));

        // Update status invoice setelah update termin
        if ($termin->invoice) {
            $termin->invoice->updateStatusFromTermins();
        }

        return new TerminResource($termin->load(['proyek', 'invoice']));
    }

    // Hapus termin, update status invoice
    public function destroy(Termin $termin)
    {
        $invoice = $termin->invoice;
        $termin->delete();

        if ($invoice) {
            $invoice->updateStatusFromTermins();
        }

        return response()->json(['message' => 'Termin berhasil dihapus']);
    }

    // Ambil termin berdasarkan proyek
    public function getByProject($proyekId)
    {
        $termins = Termin::with(['proyek', 'invoice'])
            ->where('proyek_id', $proyekId)
            ->orderBy('created_at', 'desc')
            ->get();

        return TerminResource::collection($termins);
    }

    // Update status termin dan otomatis buat expense jika ada pembayaran DP atau Pelunasan
    public function updateStatus(Request $request, Termin $termin)
    {
        // Cek apakah termin benar-benar ada di database
        if (!$termin->exists) {
            return response()->json([
                'message' => 'Termin tidak ditemukan',
                'errors' => ['termin_id' => ['Termin tidak valid atau tidak ditemukan']]
            ], 404);
        }

        // Cek apakah termin memiliki proyek_id yang valid
        if (!$termin->proyek_id) {
            return response()->json([
                'message' => 'Termin tidak valid',
                'errors' => ['proyek_id' => ['Termin harus terkait dengan proyek yang valid']]
            ], 422);
        }
        DB::beginTransaction();
        $warnings = [];
        try {
            $validated = $request->all();
            // Manual validation, collect warnings but allow update
            $required = [
                'status_termin', 'status_approval', 'approved_by'
            ];
            foreach ($required as $field) {
                if (empty($validated[$field]) && $validated[$field] !== 0 && $validated[$field] !== '0') {
                    $warnings[$field][] = 'Field ' . $field . ' wajib diisi';
                }
            }
            $status = ['Belum Dibayar','DP Dibayar','Lunas'];
            if (isset($validated['status_termin']) && !in_array($validated['status_termin'], $status)) {
                $warnings['status_termin'][] = 'Status termin tidak valid';
            }
            $approval = ['Pending','Approved','Rejected'];
            if (isset($validated['status_approval']) && !in_array($validated['status_approval'], $approval)) {
                $warnings['status_approval'][] = 'Status approval tidak valid';
            }
            if (!empty($validated['tanggal_dp']) && !strtotime($validated['tanggal_dp'])) {
                $warnings['tanggal_dp'][] = 'Format tanggal DP tidak valid';
            }
            if (!empty($validated['tanggal_dp_dibayar']) && !strtotime($validated['tanggal_dp_dibayar'])) {
                $warnings['tanggal_dp_dibayar'][] = 'Format tanggal DP dibayar tidak valid';
            }
            if (!empty($validated['tanggal_pelunasan_dibayar']) && !strtotime($validated['tanggal_pelunasan_dibayar'])) {
                $warnings['tanggal_pelunasan_dibayar'][] = 'Format tanggal pelunasan dibayar tidak valid';
            }
            // File
            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
                $path = 'uploads/bukti_pembayaran/' . $filename;
                if (!\Storage::exists('uploads/bukti_pembayaran')) {
                    \Storage::makeDirectory('uploads/bukti_pembayaran');
                }
                if (!$file->storeAs('uploads/bukti_pembayaran', $filename)) {
                    $warnings['bukti_pembayaran'][] = 'Gagal upload file';
                } else {
                    // Hapus file lama jika ada
                    if ($termin->bukti_pembayaran) {
                        \Storage::delete($termin->bukti_pembayaran);
                    }
                    $termin->bukti_pembayaran = $path;
                }
            }
            $oldStatus = $termin->status_termin;
            $newStatus = $validated['status_termin'] ?? $termin->status_termin;
            // Siapkan data update hanya field yang valid
            // Normalisasi approved_by agar tidak string 'undefined' atau non-numeric
            $approvedBy = $validated['approved_by'] ?? $termin->approved_by;
            // Normalisasi: jika kosong, 'undefined', 'null', null, bukan angka => null. Jika numeric, cast ke int.
            if (
                !isset($approvedBy) || $approvedBy === '' || strtolower((string)$approvedBy) === 'undefined' || strtolower((string)$approvedBy) === 'null' || !is_numeric($approvedBy)
            ) {
                $approvedBy = null;
            } else {
                $approvedBy = (int)$approvedBy;
            }
            $updateData = [
                'status_termin' => $newStatus,
                'status_approval' => $validated['status_approval'] ?? $termin->status_approval ?? 'Pending',
                'approved_by' => $approvedBy,
                'approved_at' => $validated['approved_at'] ?? $termin->approved_at ?? now(),
                'keterangan' => array_key_exists('keterangan', $validated) ? $validated['keterangan'] : $termin->keterangan,
            ];
            if ($newStatus === 'DP Dibayar') {
                $updateData['tanggal_dp_dibayar'] = array_key_exists('tanggal_dp_dibayar', $validated) ? $validated['tanggal_dp_dibayar'] : ($termin->tanggal_dp_dibayar ?: now());
            }
            if ($newStatus === 'Lunas') {
                $updateData['tanggal_pelunasan_dibayar'] = array_key_exists('tanggal_pelunasan_dibayar', $validated) ? $validated['tanggal_pelunasan_dibayar'] : ($termin->tanggal_pelunasan_dibayar ?: now());
            }
            // Filter hanya field yang ada di fillable
            $allowed = $termin->getFillable();
            $updateDataFiltered = array_intersect_key($updateData, array_flip($allowed));
            $termin->fill($updateDataFiltered);
            $termin->save();
            if ($termin->invoice_id && $termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
            }
            if ($oldStatus !== $newStatus) {
                // Buat/update expense
                $termin->createExpense();

                // Record income berdasarkan status baru
                if ($newStatus === 'DP Dibayar') {
                    $termin->recordIncome(
                        'dp',
                        $termin->nilai_dp,
                        $termin->tanggal_dp_dibayar
                    );
                } elseif ($newStatus === 'Lunas') {
                    // Jika status berubah ke Lunas, record pelunasan
                    $termin->recordIncome(
                        'pelunasan',
                        $termin->nilai_pelunasan,
                        $termin->tanggal_pelunasan_dibayar
                    );
                }
            }
            DB::commit();
            $response = [
                'message' => empty($warnings) ? 'Status termin berhasil diperbarui' : 'Status termin berhasil diperbarui dengan peringatan validasi',
            ];
            if (!empty($warnings)) {
                $response['warnings'] = $warnings;
            }
            return response()->json($response, empty($warnings) ? 200 : 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update status termin error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal memperbarui status termin', 'error' => $e->getMessage()], 500);
        }
    }

    // Export termin ke Excel
    public function export()
    {
        return Excel::download(new TerminExport, 'termin.xlsx');
    }

    // Import termin dari Excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new TerminImport, $request->file('file'));
            return response()->json(['message' => 'Import termin berhasil']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import gagal: ' . $e->getMessage()], 500);
        }
    }

    // Cetak PDF termin
    public function cetakPdf()
    {
        $termins = Termin::with(['proyek', 'invoice'])->get();
        $pdf = Pdf::loadView('termin.pdf', compact('termins'));
        return $pdf->download('termin.pdf');
    }

    // Get invoices for a project
    public function getInvoicesByProject($proyekId)
    {
        $invoices = Invoice::where('proyek_id', $proyekId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invoices);
    }

    public function exportPdf(Request $request)
    {
        try {
            $query = Termin::with(['proyek', 'invoice']);

            if ($request->has('proyek_id')) {
                $query->where('proyek_id', $request->proyek_id);
            }

            if ($request->has('invoice_id')) {
                $query->where('invoice_id', $request->invoice_id);
            }

            $termins = $query->get();
            $project = $termins->first()?->proyek;

            if ($termins->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data termin untuk diekspor'
                ], 404);
            }

            $pdf = PDF::loadView('exports.termins', [
                'termins' => $termins,
                'project' => $project
            ]);

            return $pdf->download('termin-report.pdf');
        } catch (\Exception $e) {
            \Log::error('Error in TerminController@exportPdf: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengekspor PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $query = Termin::with(['proyek', 'invoice']);

            if ($request->has('proyek_id')) {
                $query->where('proyek_id', $request->proyek_id);
            }

            if ($request->has('invoice_id')) {
                $query->where('invoice_id', $request->invoice_id);
            }

            $termins = $query->get();

            if ($termins->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data termin untuk diekspor'
                ], 404);
            }

            return Excel::download(new TerminExport($termins), 'termin-report.xlsx');
        } catch (\Exception $e) {
            \Log::error('Error in TerminController@exportExcel: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengekspor Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTerminSummary(Request $request)
    {
        $proyekId = $request->proyek_id;
        $invoiceId = $request->invoice_id;

        $totalTermin = \App\Models\Termin::where('proyek_id', $proyekId)
            ->where('invoice_id', $invoiceId)
            ->sum('nilai_termin');

        $totalDP = \App\Models\Termin::where('proyek_id', $proyekId)
            ->where('invoice_id', $invoiceId)
            ->sum('nilai_dp');

        $totalPelunasan = \App\Models\Termin::where('proyek_id', $proyekId)
            ->where('invoice_id', $invoiceId)
            ->sum('nilai_pelunasan');

        $totalPurchases = \App\Models\PurchaseMaterial::where('proyek_id', $proyekId)
            ->where('invoice_id', $invoiceId)
            ->sum('total_harga');

        $totalDpSudahDibayar = \App\Models\Income::where('proyek_id', $proyekId)
            ->where('invoice_id', $invoiceId)
            ->where('type', 'dp')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        $sisaBelumDibayar = $totalTermin - ($totalPurchases + $totalDpSudahDibayar);

        return response()->json([
            'total_termin' => $totalTermin,
            'total_dp' => $totalDP,
            'total_pelunasan' => $totalPelunasan,
            'total_pembelian_material' => $totalPurchases,
            'total_dp_sudah_dibayar' => $totalDpSudahDibayar,
            'sisa_belum_dibayar' => $sisaBelumDibayar,
        ]);
    }
}
