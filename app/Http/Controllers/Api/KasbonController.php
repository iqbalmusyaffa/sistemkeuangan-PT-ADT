<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kasbon;
use App\Models\KasbonAttachment;
use App\Models\KasbonPayment;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $query = Kasbon::query()->with(['proyek', 'user', 'attachments', 'payments']);

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('kasbon_date', [$request->start_date, $request->end_date]);
        }

        $kasbons = $query->paginate(10);
        $proyeks = Proyek::all();

        return response()->json([
            'kasbons' => $kasbons,
            'proyeks' => $proyeks
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'proyek_id' => 'nullable|exists:proyeks,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'kasbon_date' => 'required|date',
            'due_date' => 'nullable|date|after:kasbon_date',
            'payment_method' => 'required|string',
            'bank_account' => 'required_if:payment_method,bank_transfer',
            'bank_name' => 'required_if:payment_method,bank_transfer',
            'account_number' => 'required_if:payment_method,bank_transfer',
            'account_holder' => 'required_if:payment_method,bank_transfer',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:2048' // max 2MB
        ]);

        try {
            DB::beginTransaction();

            $kasbon = Kasbon::create([
                'proyek_id' => $request->proyek_id,
                'user_id' => auth()->id(),
                'nomor_kasbon' => 'KB-' . date('Ymd') . '-' . Str::random(6),
                'amount' => $request->amount,
                'description' => $request->description,
                'kasbon_date' => $request->kasbon_date,
                'due_date' => $request->due_date,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'bank_account' => $request->bank_account,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_holder' => $request->account_holder
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('kasbon_attachments');
                    KasbonAttachment::create([
                        'kasbon_id' => $kasbon->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Kasbon created successfully',
                'kasbon' => $kasbon->load(['proyek', 'user', 'attachments'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create kasbon'], 500);
        }
    }

    public function show(Kasbon $kasbon)
    {
        return response()->json($kasbon->load(['proyek', 'user', 'attachments', 'payments']));
    }

    public function approve(Request $request, Kasbon $kasbon)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $kasbon->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Kasbon approved successfully',
            'kasbon' => $kasbon
        ]);
    }

    public function reject(Request $request, Kasbon $kasbon)
    {
        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $kasbon->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);

        return response()->json([
            'message' => 'Kasbon rejected successfully',
            'kasbon' => $kasbon
        ]);
    }

    public function addPayment(Request $request, Kasbon $kasbon)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $kasbon->remaining_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $payment = KasbonPayment::create([
                'kasbon_id' => $kasbon->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes
            ]);

            if ($kasbon->is_fully_paid) {
                $kasbon->update(['status' => 'paid']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment added successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add payment'], 500);
        }
    }

    public function downloadAttachment(KasbonAttachment $attachment)
    {
        if (!Storage::exists($attachment->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }
} 