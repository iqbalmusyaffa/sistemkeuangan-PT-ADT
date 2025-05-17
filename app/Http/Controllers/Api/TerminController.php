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
        try {
            DB::beginTransaction();

            // Log request data
            Log::info('Termin store request data:', [
                'all' => $request->all(),
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            // Validasi dasar
            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'required|exists:invoices,id',
                'nama_termin' => 'required|string|max:255',
                'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
                'termin_ke' => 'nullable|integer|min:1',
                'nilai_termin' => 'required|numeric|min:0',
                'persentase_dp' => 'required|numeric|min:0|max:100',
                'tanggal_dp' => 'nullable|date',
                'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
                'keterangan' => 'nullable|string',
            ]);

            Log::info('Validated data:', $validated);

            // Hitung nilai DP dan pelunasan
            $nilai_dp = round($validated['nilai_termin'] * ($validated['persentase_dp'] / 100), 2);
            $nilai_pelunasan = round($validated['nilai_termin'] - $nilai_dp, 2);

            // Siapkan data untuk disimpan
            $terminData = array_merge($validated, [
                'nilai_dp' => $nilai_dp,
                'nilai_pelunasan' => $nilai_pelunasan,
                'dibayar_oleh' => $validated['status_termin'] !== 'Belum Dibayar' ? auth()->id() : null,
                'bukti_pembayaran' => null,
                'tanggal_pelunasan' => null
            ]);

            Log::info('Attempting to create termin with data:', $terminData);

            // Buat termin
            $termin = Termin::create($terminData);
            Log::info('Termin created successfully:', ['id' => $termin->id]);

            // Update status invoice
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
                Log::info('Invoice status updated');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Termin berhasil dibuat',
                'data' => new TerminResource($termin->load(['proyek', 'invoice']))
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error:', [
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating termin:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
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
            'dibayar_oleh' => $validated['status_termin'] !== 'Belum Dibayar' ? auth()->id() : null
        ]));

        // Update status invoice setelah update termin
        $invoice->updateStatusFromTermins();

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
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date',
                'status_approval' => 'required|in:Pending,Approved,Rejected',
                'approved_by' => 'required|exists:users,id',
                'approved_at' => 'nullable|date',
                'tanggal_dp_dibayar' => 'nullable|date',
                'tanggal_pelunasan_dibayar' => 'nullable|date',
            ]);

            $oldStatus = $termin->status_termin;
            $newStatus = $validated['status_termin'];

            $termin->status_termin = $newStatus;
            $termin->status_approval = $validated['status_approval'];
            $termin->approved_by = $validated['approved_by'];
            $termin->approved_at = $validated['approved_at'] ?? now();

            if ($newStatus === 'DP Dibayar' && !$termin->tanggal_dp) {
                $termin->tanggal_dp = $validated['tanggal_dp'] ?? now();
                $termin->tanggal_dp_dibayar = $validated['tanggal_dp_dibayar'] ?? now();
            }

            if ($newStatus === 'Lunas' && !$termin->tanggal_pelunasan) {
                $termin->tanggal_pelunasan = $validated['tanggal_pelunasan'] ?? now();
                $termin->tanggal_pelunasan_dibayar = $validated['tanggal_pelunasan_dibayar'] ?? now();
            }

            $termin->save();

            // Update status invoice berdasar termin
            if ($termin->invoice_id && $termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
            }

            // Buat income/expense otomatis sesuai status
            if ($oldStatus !== $newStatus) {
                // Cek dan buat income DP jika DP Dibayar
                if ($newStatus === 'DP Dibayar') {
                    $existingIncome = \App\Models\Income::where('termin_id', $termin->id)
                        ->where('type', 'dp')
                        ->where('status', 'Diterima')
                        ->first();
                    if (!$existingIncome) {
                        // Cari kategori_id default pemasukan
                        $kategori = \App\Models\Kategori::where('jenis', 'pemasukan')->first();
                        $kategoriId = $kategori ? $kategori->id : null;
                        \App\Models\Income::create([
                            'jumlah' => $termin->nilai_dp,
                            'status' => 'Diterima',
                            'type' => 'dp',
                            'termin_id' => $termin->id,
                            'proyek_id' => $termin->proyek_id,
                            'deskripsi' => "Pembayaran DP Termin {$termin->nama_termin}",
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                            'invoice_id' => $termin->invoice_id,
                            'kategori_id' => $kategoriId,
                        ]);
                    }
                }
                // Cek dan buat income pelunasan jika Lunas
                if ($newStatus === 'Lunas') {
                    $existingIncome = \App\Models\Income::where('termin_id', $termin->id)
                        ->where('type', 'pelunasan')
                        ->where('status', 'Diterima')
                        ->first();
                    if (!$existingIncome) {
                        \App\Models\Income::create([
                            'jumlah' => $termin->nilai_pelunasan,
                            'status' => 'Diterima',
                            'type' => 'pelunasan',
                            'termin_id' => $termin->id,
                            'proyek_id' => $termin->proyek_id,
                            'deskripsi' => "Pelunasan Termin {$termin->nama_termin}",
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                            'invoice_id' => $termin->invoice_id,
                        ]);
                    }
                    // Buat expense jika belum ada
                    $existingExpense = \App\Models\Expense::where('source_type', 'termin')
                        ->where('source_id', $termin->id)
                        ->where('status', 'Lunas')
                        ->first();
                    if (!$existingExpense) {
                        \App\Models\Expense::create([
                            'user_id' => auth()->id(),
                            'proyek_id' => $termin->proyek_id,
                            'amount' => $termin->nilai_pelunasan,
                            'description' => "Pengeluaran pelunasan termin {$termin->nama_termin}",
                            'transaction_date' => $termin->tanggal_pelunasan ?? now(),
                            'status' => 'Lunas',
                            'source_type' => 'termin',
                            'source_id' => $termin->id,
                            'prepared_fund' => $termin->nilai_pelunasan,
                            'invoice_id' => $termin->invoice_id,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Status termin berhasil diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update status termin error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui status termin'], 500);
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
}
