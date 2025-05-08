<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kasbon;
use App\Models\KasbonAttachment;
use App\Models\KasbonPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KasbonExport;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $query = Kasbon::with(['proyek', 'attachments', 'payments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        return response()->json(
            $query->orderBy('kasbon_date', 'desc')->paginate(10)
        );
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Kasbon creation request data:', $request->all());

            $validated = $request->validate([
                'proyek_id'        => 'nullable|exists:proyeks,id',
                'user_name'        => 'required|string',
                'amount'           => 'required|numeric|min:0',
                'description'      => 'required|string',
                'kasbon_date'      => 'required|date',
                'due_date'         => 'nullable|date|after_or_equal:kasbon_date',
                'approval_date'    => 'nullable|date',
                'disbursement_date'=> 'nullable|date',
                'settlement_date'  => 'nullable|date',
                'status'           => 'required|string|in:pending,approved,disbursed,settled,rejected',
                'payment_method'   => 'required|string',
                'bank_account'     => 'nullable|string',
                'bank_name'        => 'nullable|string',
                'account_number'   => 'nullable|string',
                'account_holder'   => 'nullable|string',
                'notes'            => 'nullable|string',
                'attachments'      => 'nullable|array',
                'attachments.*'    => 'file|max:2048',
            ]);

            DB::beginTransaction();

            $nomorKasbon = 'KB-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $kasbon = Kasbon::create([
                'proyek_id'      => $validated['proyek_id'] ?? null,
                'user_name'      => $validated['user_name'],
                'nomor_kasbon'   => $nomorKasbon,
                'amount'         => $validated['amount'],
                'description'    => $validated['description'],
                'kasbon_date'    => $validated['kasbon_date'],
                'due_date'       => $validated['due_date'] ?? null,
                'approval_date'  => $validated['approval_date'] ?? null,
                'disbursement_date' => $validated['disbursement_date'] ?? null,
                'settlement_date'   => $validated['settlement_date'] ?? null,
                'status'         => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'bank_account'   => $validated['bank_account'] ?? null,
                'bank_name'      => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'account_holder' => $validated['account_holder'] ?? null,
                'notes'          => $validated['notes'] ?? null,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('kasbon_attachments', 'public');
                    KasbonAttachment::create([
                        'kasbon_id' => $kasbon->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();
            $kasbon->load(['proyek', 'attachments', 'payments']);

            return response()->json([
                'message' => 'Kasbon berhasil dibuat',
                'data'    => $kasbon,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Kasbon creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Gagal membuat kasbon',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function show($id)
    {
        $kasbon = Kasbon::with(['proyek', 'attachments', 'payments'])->find($id);

        if (!$kasbon) {
            return response()->json(['message' => 'Kasbon tidak ditemukan'], 404);
        }

        return response()->json($kasbon);
    }

    public function update(Request $request, $id)
    {
        $kasbon = Kasbon::find($id);

        if (!$kasbon) {
            return response()->json(['message' => 'Kasbon tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'proyek_id'        => 'nullable|exists:proyeks,id',
            'amount'           => 'required|numeric|min:0',
            'description'      => 'required|string',
            'kasbon_date'      => 'required|date',
            'due_date'         => 'nullable|date|after_or_equal:kasbon_date',
            'approval_date'    => 'nullable|date',
            'disbursement_date'=> 'nullable|date',
            'settlement_date'  => 'nullable|date',
            'payment_method'   => 'required|string',
            'bank_account'     => 'required_if:payment_method,bank_transfer|string|nullable',
            'bank_name'        => 'required_if:payment_method,bank_transfer|string|nullable',
            'account_number'   => 'required_if:payment_method,bank_transfer|string|nullable',
            'account_holder'   => 'required_if:payment_method,bank_transfer|string|nullable',
            'status'           => 'required|string|in:pending,approved,rejected,paid,settled',
            'notes'            => 'nullable|string',
            'attachments'      => 'nullable|array',
            'attachments.*'    => 'file|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $kasbon->update($validated);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('kasbon_attachments', 'public');
                    KasbonAttachment::create([
                        'kasbon_id' => $kasbon->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();
            $kasbon->load(['proyek', 'attachments', 'payments']);

            return response()->json([
                'message' => 'Kasbon berhasil diperbarui',
                'data'    => $kasbon,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui kasbon',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $kasbon = Kasbon::with('attachments')->find($id);

        if (!$kasbon) {
            return response()->json(['message' => 'Kasbon tidak ditemukan'], 404);
        }

        DB::beginTransaction();

        try {
            foreach ($kasbon->attachments as $attachment) {
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }

            $kasbon->delete();
            DB::commit();

            return response()->json(['message' => 'Kasbon berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus kasbon',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, Kasbon $kasbon)
    {
        $request->validate(['notes' => 'nullable|string']);

        $kasbon->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Kasbon berhasil disetujui',
            'kasbon'  => $kasbon,
        ]);
    }

    public function reject(Request $request, Kasbon $kasbon)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $kasbon->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Kasbon berhasil ditolak',
            'kasbon'  => $kasbon,
        ]);
    }

    public function addPayment(Request $request, Kasbon $kasbon)
    {
        $request->validate([
            'amount'           => 'required|numeric|min:0|max:' . $kasbon->remaining_amount,
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|string',
            'reference_number' => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $payment = KasbonPayment::create([
                'kasbon_id'        => $kasbon->id,
                'amount'           => $request->amount,
                'payment_date'     => $request->payment_date,
                'payment_method'   => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes'            => $request->notes,
            ]);

            $kasbon->refresh();

            if ($kasbon->isFullyPaid) {
                $kasbon->update(['status' => 'settled']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran berhasil ditambahkan',
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menambahkan pembayaran',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadAttachment(KasbonAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    public function exportPdf()
    {
        try {
            $kasbons = Kasbon::with(['proyek', 'attachments', 'payments'])
                ->orderBy('kasbon_date', 'desc')
                ->get();

            if ($kasbons->isEmpty()) {
                return response()->json(['message' => 'Tidak ada data kasbon untuk diekspor'], 404);
            }

            $pdf = Pdf::loadView('exports.kasbon_pdf', compact('kasbons'));
            $pdf->setPaper('a4', 'landscape');
            
            return $pdf->download('kasbon.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengekspor PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel()
    {
        try {
            $kasbons = Kasbon::with(['proyek', 'attachments', 'payments'])
                ->orderBy('kasbon_date', 'desc')
                ->get();

            if ($kasbons->isEmpty()) {
                return response()->json(['message' => 'Tidak ada data kasbon untuk diekspor'], 404);
            }

            return Excel::download(new KasbonExport($kasbons), 'kasbon.xlsx');
        } catch (\Exception $e) {
            \Log::error('Excel Export Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengekspor Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
