<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Income;
use App\Models\PaymentMethod;
use App\Models\Termin;
use App\Models\Proyek;
use App\Models\Expense;
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
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
        // Validasi input
        $validated = $request->validate([
            'termin_id' => 'required|exists:termins,id',
            'type' => 'required|in:dp,pelunasan',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'deskripsi' => 'nullable|string',
            'bukti_pembayaran' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        try {
            $termin = Termin::findOrFail($validated['termin_id']);
            $proyek = $termin->proyek;
            $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
            // Hitung total income diterima untuk proyek ini
            $totalIncome = \App\Models\Income::where('proyek_id', $proyek->id)
                ->where('status', 'Diterima')
                ->sum('jumlah');
            // Hitung total pengeluaran
            $existingExpenses = Expense::where('proyek_id', $proyek->id)->sum('amount');
            // Sisa anggaran = budget - pengeluaran - income yang sudah diterima
            $availableBudget = $currentBudget - $existingExpenses - $totalIncome;

            if ($validated['jumlah'] > $availableBudget) {
                if ($proyek->owner) {
                    $proyek->owner->notify(new \App\Notifications\BudgetExceededNotification($proyek, $validated['jumlah']));
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anggaran proyek tidak mencukupi untuk jumlah income ini. Sisa anggaran: ' . number_format($availableBudget)
                ], 422);
            }

            $buktiFile = $request->file('bukti_pembayaran') ?? null;
            $income = $termin->recordIncome(
                $validated['type'],
                $validated['jumlah'],
                $validated['tanggal'],
                $validated['deskripsi'] ?? null,
                $buktiFile
            );
            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil ditambahkan',
                'data' => $income->load(['kategori', 'paymentMethod', 'proyek', 'createdBy', 'updatedBy', 'termin'])
            ], 201);
        } catch (\Throwable $e) {
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
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
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
                // status tidak boleh diupdate manual jika income terkait termin
                'bukti' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            // ====== Tambahan validasi anggaran & notifikasi ======
            $proyekId = $validated['proyek_id'] ?? $income->proyek_id;
            $proyek = Proyek::findOrFail($proyekId);
            $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
            // Hitung total income diterima selain income ini
            $totalIncomeLain = Income::where('proyek_id', $proyek->id)
                ->where('status', 'Diterima')
                ->where('id', '!=', $income->id)
                ->sum('jumlah');
            $totalIncomeSetelahUpdate = $totalIncomeLain + $validated['jumlah'];
            if ($totalIncomeSetelahUpdate > $currentBudget) {
                if ($proyek->owner) {
                    $proyek->owner->notify(new \App\Notifications\BudgetExceededNotification($proyek, $validated['jumlah']));
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update ini akan menyebabkan total pemasukan melebihi anggaran proyek. Sisa anggaran: ' . number_format($currentBudget - $totalIncomeLain)
                ], 422);
            }
            // ====== END Tambahan validasi anggaran & notifikasi ======

            // Jika income terkait termin, status tidak boleh diubah manual
            if ($income->termin_id) {
                // Hanya update field non-status
                $income->kategori_id = $validated['kategori_id'];
                $income->payment_method_id = $validated['payment_method_id'];
                $income->proyek_id = $validated['proyek_id'] ?? $income->proyek_id;
                $income->jumlah = $validated['jumlah'];
                $income->deskripsi = $validated['deskripsi'] ?? $income->deskripsi;
                $income->tanggal = $validated['tanggal'];
                $income->updated_by = Auth::id();

                if ($request->hasFile('bukti')) {
                    // Delete old file if exists
                    if ($income->bukti) {
                        Storage::disk('public')->delete($income->bukti);
                    }
                    $file = $request->file('bukti');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                    $income->bukti = $path;
                    // Set status_approval dan status income ke pending setelah upload bukti
                    $income->status_approval = 'pending';
                    $income->status = 'Pending';
                }
            } else {
                // Untuk income non-termin, status boleh diupdate manual
                $income->fill($validated);
                $income->updated_by = Auth::id();
                if ($request->hasFile('bukti')) {
                    if ($income->bukti) {
                        Storage::disk('public')->delete($income->bukti);
                    }
                    $file = $request->file('bukti');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('bukti_pembayaran', $filename, 'public');
                    $income->bukti = $path;
                }
            }

            $income->save();
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
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
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

            // Update invoice status & amount_paid jika income terkait invoice
            if ($income->invoice_id) {
                $invoice = $income->invoice;
                if ($invoice) {
                    $totalPaid = Income::where('invoice_id', $invoice->id)
                        ->where('status', 'Diterima')
                        ->sum('jumlah');
                    $invoice->amount_paid = $totalPaid;
                    $invoice->status = $invoice->determineStatus();
                    $invoice->save();
                }
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

    /**
     * Create income automatically when invoice is paid
     */
    public function createFromInvoice($invoice)
    {
        try {
            DB::beginTransaction();

            // Create income record
            $income = new Income();
            $income->kategori_id = $invoice->kategori_id;
            $income->payment_method_id = $invoice->payment_method_id;
            $income->proyek_id = $invoice->proyek_id;
            $income->jumlah = $invoice->total_amount;
            $income->deskripsi = "Pembayaran invoice {$invoice->invoice_number}";
            $income->tanggal = now();
            $income->status = 'Diterima';
            $income->type = 'pelunasan';
            $income->created_by = auth()->id();
            $income->updated_by = auth()->id();

            // If invoice has termin, link it
            if ($invoice->termins->isNotEmpty()) {
                $income->termin_id = $invoice->termins->first()->id;
            }


            // Validasi total income tidak melebihi budget proyek
            if ($income->proyek_id) {
                $proyek = Proyek::find($income->proyek_id);
                if ($proyek) {
                    $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
                    $totalIncome = Income::where('proyek_id', $proyek->id)
                        ->where('status', 'Diterima')
                        ->sum('jumlah');
                    if (($totalIncome + $income->jumlah) > $currentBudget) {
                        throw new \Exception('Total pemasukan melebihi anggaran proyek.');
                    }
                }
            }

            $income->save();

            DB::commit();

            return $income;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating income from invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create income automatically when termin is paid
     */
    public function createFromTermin($termin)
    {
        try {
            DB::beginTransaction();

            // Create income record
            $income = new Income();
            $income->kategori_id = $termin->kategori_id;
            $income->payment_method_id = $termin->payment_method_id;
            $income->proyek_id = $termin->proyek_id;
            $income->jumlah = $termin->jumlah_pembayaran;
            $income->deskripsi = "Pembayaran termin {$termin->nama_termin}";
            $income->tanggal = now();
            $income->status = 'Diterima';
            $income->type = $termin->type;
            $income->termin_id = $termin->id;
            $income->created_by = auth()->id();
            $income->updated_by = auth()->id();


            // Validasi total income tidak melebihi budget proyek
            if ($income->proyek_id) {
                $proyek = Proyek::find($income->proyek_id);
                if ($proyek) {
                    $currentBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
                    $totalIncome = Income::where('proyek_id', $proyek->id)
                        ->where('status', 'Diterima')
                        ->sum('jumlah');
                    if (($totalIncome + $income->jumlah) > $currentBudget) {
                        throw new \Exception('Total pemasukan melebihi anggaran proyek.');
                    }
                }
            }

            $income->save();

            DB::commit();

            return $income;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating income from termin: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get total DP paid for a project and termin
     */
    public function getTotalDpPaid(Request $request)
    {
        try {
            $query = Income::where('type', 'dp')
                ->where('status', 'Diterima');

            if ($request->has('proyek_id')) {
                $query->where('proyek_id', $request->proyek_id);
            }

            if ($request->has('invoice_id')) {
                $query->whereHas('termin', function($q) use ($request) {
                    $q->where('invoice_id', $request->invoice_id);
                });
            }

            $totalDpPaid = $query->sum('jumlah');

            return response()->json([
                'success' => true,
                'total_dp_paid' => $totalDpPaid
            ]);
        } catch (\Exception $e) {
            Log::error('Error in IncomeController@getTotalDpPaid: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan total DP yang sudah dibayar: ' . $e->getMessage()
            ], 500);
        }
    }
        /**
     * Admin approval for income (setujui pemasukan setelah cek bukti)
     */
    public function approve(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan approval.'
                ], 403);
            }
            $income = \App\Models\Income::findOrFail($id);
            if (!$income->termin_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Income tidak terkait termin.'
                ], 422);
            }
            $termin = \App\Models\Termin::findOrFail($income->termin_id);
            $approvedIncome = $termin->approveIncome($income->id);
            return response()->json([
                'success' => true,
                'message' => 'Pemasukan berhasil di-approve',
                'data' => $approvedIncome
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve pemasukan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
