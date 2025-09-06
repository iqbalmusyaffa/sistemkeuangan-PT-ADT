<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Termin;
use App\Models\Proyek;
use App\Models\Invoice;
use App\Models\Income;
use App\Models\Expense; // Ensure Expense model is imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TerminExport;
use App\Imports\TerminImport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Resources\TerminResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // Import Rule for validation
use Illuminate\Support\Facades\Auth;

class TerminController extends Controller
{
    // Ambil semua termin, bisa filter berdasarkan invoice_id
  public function index(Request $request)
{
    try {
        $columns = [
            'nama_termin', 'jenis_termin', 'termin_ke', 'nilai_termin', 'persentase_dp',
            'nilai_dp', 'nilai_pelunasan', 'tanggal_dp', 'tanggal_pelunasan',
            'status_termin', 'status_approval', 'approved_by_name', 'approved_at',
            'tanggal_dp_dibayar', 'tanggal_pelunasan_dibayar', 'keterangan', 'created_at', 'updated_at'
        ];

        $query = Termin::with(['proyek', 'invoice']);

        // Filter by project_id and invoice_id if provided
        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        // Search filter
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%$search%");
                }
            });
        }

        // Sorting
        if ($request->has('order')) {
            $orderColumn = $columns[$request->order[0]['column']];
            $orderDir = $request->order[0]['dir'];
            $query->orderBy($orderColumn, $orderDir);
        }

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $totalRecords = $query->count();
        $data = $query->offset($start)->limit($length)->get();

        // 💡 Tambahkan kolom URL bukti pembayaran
        $data->transform(function ($termin) {
            $termin->bukti_pembayaran_url = $termin->bukti_pembayaran
                ? asset('storage/' . $termin->bukti_pembayaran)
                : null;
            return $termin;
        });

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
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
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
        DB::beginTransaction();
        try {
            Log::info('Creating new termin:', $request->all());

            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'required|exists:invoices,id',
                'nama_termin' => 'required|string|max:255',
                'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
                'target_progress' => 'required|numeric|min:0|max:100',
                'termin_ke' => 'nullable|integer|min:1',
                'nilai_termin' => 'required|numeric|min:0',
                'persentase_dp' => 'required|numeric|min:0|max:100',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
                'keterangan' => 'nullable|string',
                'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
            ]);
            // Abaikan input status_termin dan status_approval dari request, selalu set default
            unset($validated['status_termin'], $validated['status_approval']);

            // Validasi: termin_ke boleh sama asal jenis_termin berbeda (boleh DP & Pelunasan untuk termin_ke sama)
            $queryExistingTermin = Termin::where('proyek_id', $validated['proyek_id'])
                ->where('invoice_id', $validated['invoice_id'])
                ->where('termin_ke', $request->input('termin_ke'))
                ->where('jenis_termin', $validated['jenis_termin']);

            // If it's an update request, exclude the current termin from the check
            if ($request->route('termin')) {
                $queryExistingTermin->where('id', '!=', $request->route('termin')->id);
            }

            $existingTermin = $queryExistingTermin->first();
            if ($existingTermin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Termin ke-' . $request->input('termin_ke') . ' dengan jenis ' . $validated['jenis_termin'] . ' sudah ada untuk proyek dan invoice ini.'
                ], 422);
            }
if (!empty($validated['tanggal_dp']) && $validated['jenis_termin'] === 'DP') {
    $conflictingTermin = Termin::where('proyek_id', $validated['proyek_id'])
        ->where('invoice_id', $validated['invoice_id'])
        ->where('jenis_termin', 'DP')
        ->where('tanggal_dp', $validated['tanggal_dp'])
        ->when($request->route('termin'), function ($query) use ($request) {
            $query->where('id', '!=', $request->route('termin')->id);
        })
        ->first();

    if ($conflictingTermin) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tanggal DP ini sudah digunakan oleh termin lain dalam invoice yang sama.'
        ], 422);
    }
}
            // Gunakan relasi Eloquent untuk memvalidasi invoice terkait proyek
            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->whereHas('proyek', function ($query) use ($validated) {
                    $query->where('id', $validated['proyek_id']);
                })
                ->first();

            if (!$invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice tidak ditemukan atau tidak terkait dengan proyek ini.'
                ], 422);
            }

            // Validasi bahwa anggaran proyek mencukupi untuk nilai termin (pakai current_budget)
            $proyek = Proyek::findOrFail($validated['proyek_id']);
            $currentProjectBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
            $existingProjectExpenses = Expense::where('proyek_id', $proyek->id)->sum('amount');
            $availableBudget = $currentProjectBudget - $existingProjectExpenses;

            if ($availableBudget < $validated['nilai_termin']) {
                // Kirim notifikasi ke owner proyek jika ada
                if (method_exists($proyek, 'owner') && $proyek->owner) {
                    try {
                        $proyek->owner->notify(new \App\Notifications\BudgetExceededNotification($proyek, $validated['nilai_termin']));
                    } catch (\Throwable $e) {
                        \Log::warning('Gagal mengirim notifikasi budget exceeded: ' . $e->getMessage());
                    }
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anggaran proyek tidak mencukupi untuk nilai termin ini. Sisa anggaran: ' . number_format($availableBudget)
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
                // Status dan approval tidak bisa diisi manual, selalu default
                'status_termin' => 'Belum Dibayar',
                'status_approval' => 'Pending',
                'keterangan' => $validated['keterangan'] ?? null,
                'dibayar_oleh' => null,
                'bukti_pembayaran' => null, // Will be set after file upload
                'tanggal_pelunasan_dibayar' => null
            ];

            // Filter only fillable fields
            $allowed = (new \App\Models\Termin)->getFillable();
            $terminDataFiltered = array_intersect_key($terminData, array_flip($allowed));

            $termin = Termin::create($validated);

            // Handle file upload if present
if ($request->hasFile('bukti_pembayaran')) {
    $file = $request->file('bukti_pembayaran');
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());

    $destination = public_path('uploads/bukti_pembayaran');
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }

    $file->move($destination, $filename);
    $relativePath = 'uploads/bukti_pembayaran/' . $filename;

    $termin->bukti_pembayaran = $relativePath;
    $buktiBaru = $relativePath;
    $termin->saveQuietly();
}


            // Update invoice status (this will trigger cascading updates to project via Invoice observer)
            if ($termin->invoice) {
                try {
                    $termin->invoice->updateStatusFromTermins();
                } catch (\Exception $e) {
                    Log::error('Error updating invoice status after termin creation:', [
                        'message' => $e->getMessage(),
                        'invoice_id' => $termin->invoice_id,
                        'termin_id' => $termin->id
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal memperbarui status invoice setelah pembuatan termin. Silakan coba lagi.'
                    ], 500);
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
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
            ], 403);
        }
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'required|exists:invoices,id',
                'nama_termin' => 'required|string|max:255',
                'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
                'target_progress' => 'required|numeric|min:0|max:100',
                'termin_ke' => [
                    'nullable',
                    'integer',
                    'min:1',
                    // Validate uniqueness of termin_ke within the same project and invoice, excluding current termin
                    Rule::unique('termins')->where(function ($query) use ($request, $termin) {
                        return $query->where('proyek_id', $request->proyek_id)
                                     ->where('invoice_id', $request->invoice_id)
                                     ->where('id', '!=', $termin->id);
                    })
                ],
                'nilai_termin' => 'required|numeric|min:0',
                'persentase_dp' => 'required|numeric|min:0|max:100',
                'tanggal_dp' => 'nullable|date',
                'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp', // deadline/jatuh tempo
                'keterangan' => 'nullable|string',
                'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'dibayar_oleh' => 'nullable|exists:users,id'
            ]);
            // Abaikan input status_termin dan status_approval dari request pada update
            unset($validated['status_termin'], $validated['status_approval']);

            // Validate that the invoice belongs to the selected project
            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->where('proyek_id', $validated['proyek_id'])
                ->first();

            if (!$invoice) {
                return response()->json(['message' => 'Invoice tidak valid untuk proyek ini'], 422);
            }

            // Check if the change in nilai_termin exceeds the project budget
            $proyek = Proyek::findOrFail($validated['proyek_id']);
            $currentProjectBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;

            // Calculate total expenses excluding the current termin's previous value
            $existingProjectExpensesExcludingCurrent = Expense::where('proyek_id', $proyek->id)
                ->where(function($q) use ($termin) {
                    // Exclude this termin's old expense amount from calculation
                    $q->whereNull('source_type')
                      ->orWhere(function($q2) use ($termin) {
                          $q2->where('source_type', '!=', 'termin')
                             ->orWhere('source_id', '!=', $termin->id);
                      });
                })
                ->sum('amount');

            $totalExpensesWithNewTermin = $existingProjectExpensesExcludingCurrent + $validated['nilai_termin'];

            if ($totalExpensesWithNewTermin > $currentProjectBudget) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Update ini akan menyebabkan total pengeluaran melebihi sisa anggaran proyek. Sisa anggaran: ' . number_format($currentProjectBudget - $existingProjectExpensesExcludingCurrent)
                ], 422);
            }

            // Calculate nilai_dp and nilai_pelunasan
            $nilai_dp = round($validated['nilai_termin'] * ($validated['persentase_dp'] / 100), 2);
            $nilai_pelunasan = round($validated['nilai_termin'] - $nilai_dp, 2);

            // Handle file bukti pembayaran saat update
            $uploadingBukti = false;
          if ($request->hasFile('bukti_pembayaran')) {
    $file = $request->file('bukti_pembayaran');
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
    $destination = public_path('uploads/bukti_pembayaran');
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }

    if ($termin->bukti_pembayaran && file_exists(public_path($termin->bukti_pembayaran))) {
        unlink(public_path($termin->bukti_pembayaran));
    }

    $file->move($destination, $filename);
    $validated['bukti_pembayaran'] = 'uploads/bukti_pembayaran/' . $filename;
            } else {
                // If no new file is uploaded, retain the old one, unless explicitly cleared
                if (isset($request->bukti_pembayaran_cleared) && $request->bukti_pembayaran_cleared === true) {
                    if ($termin->bukti_pembayaran) {
                        Storage::delete($termin->bukti_pembayaran);
                    }
                    $validated['bukti_pembayaran'] = null;
                } else {
                    // Keep existing bukti_pembayaran if no new file and not cleared
                    $validated['bukti_pembayaran'] = $termin->bukti_pembayaran;
                }
            }

            // Set the isUpdatingStatus flag to prevent recursion in model hooks
            $termin->isUpdatingStatus = true;

            // PATCH: Cek perubahan nilai termin/barang/material pada termin yang sudah Lunas/Approved
            $revertApproval = false;
            $old_nilai_termin = $termin->nilai_termin;
            $old_persentase_dp = $termin->persentase_dp;
            $old_nilai_dp = $termin->nilai_dp;
            $old_nilai_pelunasan = $termin->nilai_pelunasan;
            $old_status_termin = $termin->status_termin;
            $old_status_approval = $termin->status_approval;

            // Deteksi perubahan nilai utama
            $isNilaiChanged = (
                $validated['nilai_termin'] != $old_nilai_termin ||
                $validated['persentase_dp'] != $old_persentase_dp ||
                $nilai_dp != $old_nilai_dp ||
                $nilai_pelunasan != $old_nilai_pelunasan
            );

            if ($isNilaiChanged && (strtolower($old_status_termin) === 'lunas' || strtolower($old_status_approval) === 'approved')) {
                // Otomatis revert status approval dan status termin
                $revertApproval = true;
            }

            // Status dan approval tidak bisa diubah manual lewat update
            $updateData = array_merge($validated, [
                'nilai_dp' => $nilai_dp,
                'nilai_pelunasan' => $nilai_pelunasan,
                'target_progress' => $validated['target_progress'], // <-- tambahkan ini

            ]);

            // Jika upload bukti, status_approval harus Pending dan status_termin Belum Dibayar
            if ($uploadingBukti) {
                $updateData['status_approval'] = 'Pending';
                $updateData['status_termin'] = 'Belum Dibayar';
            }

            // PATCH: Jika revertApproval, set status_approval dan status_termin ke default, reset tanggal dibayar
            if ($revertApproval) {
                $updateData['status_approval'] = 'Pending';
                $updateData['status_termin'] = 'Belum Dibayar';
                $updateData['tanggal_dp_dibayar'] = null;
                $updateData['tanggal_pelunasan_dibayar'] = null;
            } else {
                // Jangan izinkan update manual tanggal_dp_dibayar/tanggal_pelunasan_dibayar
                unset($updateData['tanggal_dp_dibayar']);
                unset($updateData['tanggal_pelunasan_dibayar']);
            }

            $termin->update($updateData);
            $termin->isUpdatingStatus = false;

            // Update invoice status (this will trigger cascading updates to project via Invoice observer)
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
            }

            DB::commit();
            $msg = 'Termin berhasil diperbarui';
            if ($revertApproval) {
                $msg .= ' (Status approval dan termin di-revert karena ada perubahan nilai pada termin yang sudah lunas/approved)';
            }
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'data' => new TerminResource($termin->load(['proyek', 'invoice']))
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating termin:', [
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


    // Hapus termin, update status invoice
 public function destroy(Termin $termin)
{
    $user = Auth::user();
    if (!in_array($user->role, ['admin', 'superadmin'])) {
        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak. Hanya admin atau super admin yang dapat melakukan aksi ini.'
        ], 403);
    }

    DB::beginTransaction();
    try {
        $invoice = $termin->invoice;

        // 🔁 Hapus income yang terkait (jika ada)
        if ($termin->income) {
            // Hapus file bukti jika ada
            if ($termin->income->bukti_pembayaran) {
                Storage::disk('public')->delete($termin->income->bukti_pembayaran);
            }
            $termin->income->delete();
        }

        // Set flag untuk mencegah infinite loop saat hook termin
        $termin->isUpdatingStatus = true;
        $termin->delete();
        $termin->isUpdatingStatus = false;

        if ($invoice) {
            $invoice->updateStatusFromTermins();
        }

        DB::commit();
        return response()->json(['message' => 'Termin dan income terkait berhasil dihapus.']);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting termin:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menghapus termin: ' . $e->getMessage()
        ], 500);
    }
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

public function updateStatus(Request $request, Termin $termin)
{
    if (!$termin->exists) {
        return response()->json([
            'status' => 'error',
            'message' => 'Termin tidak ditemukan.',
            'errors' => ['termin_id' => ['Termin tidak valid atau tidak ditemukan']]
        ], 404);
    }

    if (!$termin->proyek_id) {
        return response()->json([
            'status' => 'error',
            'message' => 'Termin tidak valid.',
            'errors' => ['proyek_id' => ['Termin harus terkait dengan proyek yang valid']]
        ], 422);
    }

    DB::beginTransaction();
    $warnings = [];

    try {
        $validated = $request->all();

        // Manual validation
        $required = ['status_termin', 'status_approval', 'approved_by'];
        foreach ($required as $field) {
            if (empty($validated[$field]) && $validated[$field] !== '0') {
                $warnings[$field][] = 'Field ' . $field . ' wajib diisi';
            }
        }

        $validStatuses = ['Belum Dibayar', 'DP Dibayar', 'Lunas'];
        if (isset($validated['status_termin']) && !in_array($validated['status_termin'], $validStatuses)) {
            $warnings['status_termin'][] = 'Status termin tidak valid';
        }

        $validApprovals = ['Pending', 'Approved', 'Rejected'];
        if (isset($validated['status_approval']) && !in_array($validated['status_approval'], $validApprovals)) {
            $warnings['status_approval'][] = 'Status approval tidak valid';
        }

        // Tanggal validation
        foreach (['tanggal_dp', 'tanggal_dp_dibayar', 'tanggal_pelunasan_dibayar'] as $field) {
            if (!empty($validated[$field]) && !strtotime($validated[$field])) {
                $warnings[$field][] = 'Format tanggal tidak valid';
            }
        }

        // Upload bukti pembayaran
        $buktiBaru = $termin->bukti_pembayaran;
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
            $destination = public_path('uploads/bukti_pembayaran');

            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            if ($termin->bukti_pembayaran && file_exists(public_path($termin->bukti_pembayaran))) {
                unlink(public_path($termin->bukti_pembayaran));
            }

            $file->move($destination, $filename);
            $buktiBaru = 'uploads/bukti_pembayaran/' . $filename;
        }

        // Normalisasi approved_by
        $approvedBy = $validated['approved_by'] ?? $termin->approved_by;
        $approvedBy = (!isset($approvedBy) || $approvedBy === '' || !is_numeric($approvedBy)) ? null : (int) $approvedBy;

        $newStatus = $validated['status_termin'] ?? $termin->status_termin;

        $updateData = [
            'status_termin' => $request->has('status_termin') ? $validated['status_termin'] : $termin->status_termin,
            'status_approval' => $request->has('status_approval') ? $validated['status_approval'] : $termin->status_approval,
            'approved_by' => $request->has('approved_by') ? $approvedBy : $termin->approved_by,
            'approved_at' => $request->has('status_approval') && in_array($validated['status_approval'], ['Approved', 'Rejected'])
                ? ($validated['approved_at'] ?? now())
                : $termin->approved_at,
            'keterangan' => array_key_exists('keterangan', $validated) ? $validated['keterangan'] : $termin->keterangan,
            'bukti_pembayaran' => $buktiBaru,
        ];

        // Cek income terkait
        $isIncomeDPExist = $termin->incomes()->where('type', 'dp')->exists();
        $isIncomePelunasanExist = $termin->incomes()->where('type', 'pelunasan')->exists();

        if ($newStatus === 'DP Dibayar') {
            $date = $validated['tanggal_dp_dibayar'] ?? $termin->tanggal_dp_dibayar ?? now();
            $updateData['tanggal_dp_dibayar'] = $date;
            $updateData['tanggal_pelunasan_dibayar'] = null;

            if (!$isIncomeDPExist) {
                $termin->recordIncome('dp', $termin->nilai_dp, $date, 'Auto income from status update', $buktiBaru);
            } else {
                $incomeDP = $termin->incomes()->where('type', 'dp')->first();
                if ($incomeDP && $buktiBaru !== $incomeDP->bukti_pembayaran) {
                    $incomeDP->bukti_pembayaran = $buktiBaru;
                    $incomeDP->save();
                }
            }
        } elseif ($newStatus === 'Lunas') {
            $tanggalPelunasan = $validated['tanggal_pelunasan_dibayar'] ?? $termin->tanggal_pelunasan_dibayar ?? now();
            $tanggalDp = $termin->tanggal_dp_dibayar ?: $termin->tanggal_dp ?: now();

            $updateData['tanggal_dp_dibayar'] = $tanggalDp;
            $updateData['tanggal_pelunasan_dibayar'] = $tanggalPelunasan;

            if (!$isIncomePelunasanExist) {
                $termin->recordIncome('pelunasan', $termin->nilai_pelunasan, $tanggalPelunasan, 'Auto income pelunasan from status update', $buktiBaru);
            } else {
                $incomePelunasan = $termin->incomes()->where('type', 'pelunasan')->first();
                if ($incomePelunasan && $buktiBaru !== $incomePelunasan->bukti_pembayaran) {
                    $incomePelunasan->bukti_pembayaran = $buktiBaru;
                    $incomePelunasan->save();
                }
            }
        } else {
            $updateData['tanggal_dp_dibayar'] = null;
            $updateData['tanggal_pelunasan_dibayar'] = null;
        }

        // Simpan termin
        $termin->fill($updateData);
        $termin->save();

        DB::commit();

        $response = [
            'status' => 'success',
            'message' => empty($warnings)
                ? 'Status termin berhasil diperbarui.'
                : 'Status termin berhasil diperbarui dengan peringatan validasi.',
            'data' => new TerminResource($termin->load(['proyek', 'invoice']))
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return response()->json($response, empty($warnings) ? 200 : 202);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Gagal update status termin: ' . $e->getMessage(), [
            'termin_id' => $termin->id ?? null,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat memperbarui status termin.',
            'errors' => ['exception' => $e->getMessage()]
        ], 500);
    }
}



    // Export termin ke Excel
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
        $invoice = $termins->first()?->invoice;

        if ($termins->isEmpty() || !$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data termin atau invoice tidak ditemukan.'
            ], 404);
        }

        // Hitung berdasarkan income yang sudah dibayar
        $totalIncomeDp = Income::where('invoice_id', $invoice->id)
            ->where('type', 'dp')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        $totalIncomePelunasan = Income::where('invoice_id', $invoice->id)
            ->where('type', 'pelunasan')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        $totalIncomeAll = $totalIncomeDp + $totalIncomePelunasan;
        $sisaBelumDibayar = $invoice->grand_total - $totalIncomeAll;

        $pdf = Pdf::loadView('exports.termins', [
            'termins' => $termins,
            'project' => $project,
            'invoice' => $invoice,
            'total_income_dp' => $totalIncomeDp,
            'total_income_pelunasan' => $totalIncomePelunasan,
            'total_income_all' => $totalIncomeAll,
            'sisa_belum_dibayar' => $sisaBelumDibayar,
        ])->setPaper('a4', 'landscape');

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

    // Get invoices for a project
    public function getInvoicesByProject($proyekId)
    {
        $invoices = Invoice::where('proyek_id', $proyekId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invoices);
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
            ->where('invoice_id', $invoiceId) // Ensure this join is correct for Invoice
            ->where('type', 'dp')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        // This calculation needs careful review. sisa_belum_dibayar is usually total_invoice_amount - total_paid_income
        // Not total_termin - (purchases + dp_paid).
        // It should be remaining amount from the invoice's total value, not from purchase materials.
        $invoice = Invoice::find($invoiceId);
        $sisaBelumDibayar = 0;
        if ($invoice) {
            $totalInvoiceAmount = $invoice->grand_total; // Use grand_total which includes taxes
            $totalPaidOnInvoice = \App\Models\Income::where('invoice_id', $invoiceId)
                                                    ->where('status', 'Diterima')
                                                    ->sum('jumlah');
            $sisaBelumDibayar = $totalInvoiceAmount - $totalPaidOnInvoice;
        }


        return response()->json([
            'total_termin' => $totalTermin,
            'total_dp' => $totalDP,
            'total_pelunasan' => $totalPelunasan,
            'total_pembelian_material' => $totalPurchases, // This might be an expense, not related to termin sum directly
            'total_dp_sudah_dibayar' => $totalDpSudahDibayar,
            'sisa_belum_dibayar' => $sisaBelumDibayar,
        ]);
    }

    // Tambahkan di dalam class TerminController
    public function datatables(Request $request)
    {
        $query = Termin::with(['proyek', 'invoice', 'expense', 'incomes']);
        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addColumn('invoice_status', function ($termin) {
                return $termin->invoice ? $termin->invoice->status : null;
            })
            ->addColumn('expense_status', function ($termin) {
                return $termin->expense ? $termin->expense->status : null;
            })
          ->addColumn('income_status', function ($termin) {
    return $termin->relationLoaded('incomes') && $termin->incomes->count()
        ? $termin->incomes->pluck('status')->unique()->join(', ')
        : null;
})

            ->toJson();
    }

    public function calculateTerminValues(Request $request)
    {
        $validated = $request->validate([
            'nilai_termin' => 'required|numeric|min:0',
            'persentase_dp' => 'required|numeric|min:0|max:100',
            'input_manual_dp' => 'required|boolean',
            'input_user_dp' => 'nullable|numeric|min:0',
            'total_dp_terbayar' => 'required|numeric|min:0',
            'total_pelunasan_terbayar' => 'required|numeric|min:0',
        ]);

        $result = Termin::calculateTerminValues(
            $validated['nilai_termin'],
            $validated['persentase_dp'],
            $validated['input_manual_dp'],
            $validated['input_user_dp'],
            $validated['total_dp_terbayar'],
            $validated['total_pelunasan_terbayar']
        );

        return response()->json($result);
    }
   public function approveTermin(Request $request, Termin $termin)
{
    $user = auth()->user();
    if (!$user || !in_array($user->role, ['admin', 'superadmin', 'keuangan'])) {
        return response()->json(['message' => 'Akses ditolak. Hanya peran tertentu yang dapat menyetujui termin.'], 403);
    }

    DB::beginTransaction();
    try {
        // Panggil metode approve di model Termin
        $termin->approve();

        // ✅ Tambahkan ini: update progress proyek setelah approval
        $proyek = $termin->proyek ?? \App\Models\Proyek::find($termin->proyek_id);
        if ($proyek) {
            $proyek->updateProgressFromTermins();
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Termin berhasil disetujui & progress proyek diperbarui.',
            'data' => new TerminResource($termin->load(['proyek', 'invoice', 'expense']))
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Gagal menyetujui termin:', [
            'termin_id' => $termin->id,
            'error' => $e->getMessage()
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menyetujui termin: ' . $e->getMessage()
        ], 422);
    }
}

        // ENDPOINT: Approval admin untuk income
    public function approveIncome(Request $request, $terminId, $incomeId)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin', 'keuangan'])) {
            return response()->json(['message' => 'Akses ditolak. Hanya admin/keuangan yang dapat approve.'], 403);
        }
        try {
            $termin = \App\Models\Termin::findOrFail($terminId);
            $income = $termin->approveIncome($incomeId);
            return response()->json([
                'message' => 'Income berhasil di-approve',
                'data' => $income
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal approve income',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    // ENDPOINT: Approval admin untuk expense
    public function approveExpense(Request $request, $terminId, $expenseId)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin', 'keuangan'])) {
            return response()->json(['message' => 'Akses ditolak. Hanya admin/keuangan yang dapat approve.'], 403);
        }
        try {
            $termin = \App\Models\Termin::findOrFail($terminId);
            $expense = $termin->approveExpense($expenseId);
            return response()->json([
                'message' => 'Expense berhasil di-approve',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal approve expense',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function getUsedDates(Request $request)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|integer',
            'invoice_id' => 'required|integer',
        ]);

        $usedDates = Termin::where('proyek_id', $validated['proyek_id'])
            ->where('invoice_id', $validated['invoice_id'])
            ->get(['tanggal_dp', 'tanggal_pelunasan'])
            ->flatMap(function ($termin) {
                return [$termin->tanggal_dp, $termin->tanggal_pelunasan];
            })
            ->filter()
            ->unique()
            ->values();

        return response()->json($usedDates);
    }
    public function revokeApproval(int $id)
{
    $this->authorizeAdmin();

    DB::beginTransaction();
    try {
        $termin = Termin::with('incomes')->findOrFail($id);

        if (strtolower($termin->status_approval) !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Termin belum disetujui. Tidak perlu reset.'
            ], 400);
        }

        // Reset approval status termin
        $termin->status_approval = 'Pending';
        $termin->save();

        // Juga reset semua income yang terhubung ke termin ini
        foreach ($termin->incomes as $income) {
            if ($income->status === 'approved') {
                $income->status = 'pending';
                $income->save();
                    // Tambahkan ini agar status_termin ikut berubah
    $termin->clearCache();
    $termin->updateStatusFromPayments(true);
    $termin->updateRelatedRecordsAfterStatusChange();
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Approval termin dan pemasukan terkait berhasil di-reset.',
            'data'    => $termin->load('incomes'),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('Gagal revoke approval termin: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengubah status termin.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

}
