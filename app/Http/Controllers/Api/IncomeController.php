<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Income;
use App\Models\PaymentMethod;
use App\Models\Termin;
use App\Models\Proyek;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Income::with(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy', 'termin']);

            // Filter by project if provided
            if ($request->has('proyek_id')) {
                $query->where('proyek_id', $request->proyek_id);
            }

            // Filter by date range if provided
            if ($request->has(['start_date', 'end_date'])) {
                $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $incomes = $query->latest()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'List data pemasukan',
                'data' => $incomes
            ]);
        } catch (\Exception $e) {
            Log::error('Error in IncomeController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            Log::info('Income store request: ' . json_encode($request->all()));

            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategoris,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'proyek_id' => 'nullable|exists:proyeks,id',
                'termin_id' => 'nullable|exists:termins,id',
                'type' => 'required_if:termin_id,!=,null|in:dp,pelunasan',
                'jumlah' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'tanggal' => 'required|date',
                'status' => 'required|in:Pending,Diterima,Ditolak',
                'bukti_pembayaran' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            // Validasi jumlah pembayaran jika terkait termin
            if ($validated['termin_id']) {
                $termin = Termin::findOrFail($validated['termin_id']);
                
                if ($validated['type'] === 'dp') {
                    // Validasi DP
                    $totalDpPaid = $termin->total_dp_paid;
                    if (($totalDpPaid + $validated['jumlah']) > $termin->nilai_dp) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Jumlah DP melebihi nilai DP yang ditentukan'
                        ], 422);
                    }
                } else {
                    // Validasi pelunasan
                    $totalPelunasanPaid = $termin->total_pelunasan_paid;
                    if (($totalPelunasanPaid + $validated['jumlah']) > $termin->nilai_pelunasan) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Jumlah pelunasan melebihi nilai pelunasan yang ditentukan'
                        ], 422);
                    }
                }
            }

            $income = new Income();
            $income->fill($validated);
            $income->created_by = Auth::id();
            $income->updated_by = Auth::id();

            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                $income->bukti_pembayaran = $path;
            }

            $income->save();

            // Update termin status if income is accepted
            if ($income->status === 'Diterima' && $income->termin) {
                $income->termin->updateStatusFromPayments();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil ditambahkan',
                'data' => $income->load(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy', 'termin'])
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Income store exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pemasukan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $income = Income::with(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy', 'termin'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Detail pemasukan ditemukan',
                'data' => $income
            ]);
        } catch (\Exception $e) {
            Log::error('Error in IncomeController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Data tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $income = Income::findOrFail($id);

            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategoris,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'proyek_id' => 'nullable|exists:proyeks,id',
                'jumlah' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'tanggal' => 'required|date',
                'status' => 'required|in:Pending,Diterima,Ditolak',
                'bukti_pembayaran' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            $income->fill($validated);
            $income->updated_by = Auth::id();

            if ($request->hasFile('bukti_pembayaran')) {
                // Delete old file if exists
                if ($income->bukti_pembayaran) {
                    Storage::disk('public')->delete($income->bukti_pembayaran);
                }

                $file = $request->file('bukti_pembayaran');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                $income->bukti_pembayaran = $path;
            }

            $income->save();

            // Update termin status if income is accepted
            if ($income->status === 'Diterima' && $income->termin) {
                $income->termin->updateStatusFromPayments();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil diperbarui',
                'data' => $income->load(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy', 'termin'])
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Income update exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pemasukan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $income = Income::findOrFail($id);

            // Delete bukti pembayaran file if exists
            if ($income->bukti_pembayaran) {
                Storage::disk('public')->delete($income->bukti_pembayaran);
            }

            $income->delete();

            // Update termin status if income was related to a termin
            if ($income->termin) {
                $income->termin->updateStatusFromPayments();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pemasukan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in IncomeController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project income summary
     */
    public function getProjectIncomeSummary($projectId)
    {
        try {
            $proyek = Proyek::findOrFail($projectId);
            
            $summary = [
                'total_income' => Income::where('proyek_id', $projectId)
                    ->where('status', 'Diterima')
                    ->sum('jumlah'),
                'total_dp' => Income::where('proyek_id', $projectId)
                    ->where('status', 'Diterima')
                    ->where('type', 'dp')
                    ->sum('jumlah'),
                'total_pelunasan' => Income::where('proyek_id', $projectId)
                    ->where('status', 'Diterima')
                    ->where('type', 'pelunasan')
                    ->sum('jumlah'),
                'project_name' => $proyek->nama_proyek,
                'project_budget' => $proyek->anggaran_kontrak
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Error in IncomeController@getProjectIncomeSummary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan ringkasan pemasukan proyek: ' . $e->getMessage()
            ], 500);
        }
    }
}
