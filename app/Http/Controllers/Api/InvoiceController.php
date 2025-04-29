<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\PurchaseMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    // Menampilkan daftar invoice
    public function index(Request $request)
    {
        $query = Invoice::with(['proyek', 'purchaseMaterials']);
        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }
        $invoices = $query->get();
        return InvoiceResource::collection($invoices);
    }

    // Menampilkan detail invoice
    public function show($id)
    {
        $invoice = Invoice::with(['proyek', 'purchaseMaterials', 'termins', 'expenses'])->findOrFail($id);
        return new InvoiceResource($invoice);
    }

    // Membuat invoice baru dengan multiple items
    public function store(Request $request)
    {
        try {
            \Log::info('Invoice store request:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_date' => 'required|date',
                'purchase_materials' => 'required|array|min:1',
                'purchase_materials.*.item' => 'required|string',
                'purchase_materials.*.qty' => 'required|numeric',
                'purchase_materials.*.harga' => 'required|numeric',
                'purchase_materials.*.type' => 'required|string|max:255',
                'purchase_materials.*.merek_id' => 'nullable|exists:mereks,id',
                'purchase_materials.*.unit_id' => 'required|exists:units,id',
                'purchase_materials.*.category_id' => 'nullable|exists:kategoris,id',
                'purchase_materials.*.deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                \Log::error('Invoice validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Generate invoice number
                $date = now()->format('Ymd');
                $lastInvoice = Invoice::where('invoice_number', 'like', "INV-{$date}-%")->latest()->first();
                $number = 1;

                if ($lastInvoice) {
                    $number = (int)substr($lastInvoice->invoice_number, -4) + 1;
                }

                $invoiceNumber = 'INV-' . $date . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'proyek_id' => $request->proyek_id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => $request->invoice_date,
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'notes' => $request->notes,
                ]);

                $totalPurchaseAmount = 0;
                foreach ($request->purchase_materials as $item) {
                    $totalHarga = $item['qty'] * $item['harga'];
                    $purchaseMaterial = PurchaseMaterial::create([
                        'proyek_id' => $request->proyek_id,
                        'invoice_id' => $invoice->id,
                        'item' => $item['item'],
                        'merek_id' => $item['merek_id'] ?? null,
                        'type' => $item['type'],
                        'spesifikasi' => $item['spesifikasi'] ?? null,
                        'unit_id' => $item['unit_id'],
                        'category_id' => $item['category_id'] ?? null,
                        'service_category_id' => $item['service_category_id'] ?? null,
                        'qty' => $item['qty'],
                        'harga' => $item['harga'],
                        'total_harga' => $totalHarga,
                        'deskripsi' => $item['deskripsi'] ?? null,
                        'is_service' => isset($item['service_category_id']) && $item['service_category_id'] ? 1 : 0,
                    ]);

                    // Create expense for each purchase material
                    $expense = new \App\Models\Expense([
                        'user_id' => auth()->id(),
                        'proyek_id' => $request->proyek_id,
                        'category_id' => $purchaseMaterial->is_service ? null : $purchaseMaterial->category_id,
                        'service_category_id' => $purchaseMaterial->is_service ? $purchaseMaterial->service_category_id : null,
                        'amount' => $purchaseMaterial->total_harga,
                        'description' => "Pembelian " . $purchaseMaterial->item . " - " . $purchaseMaterial->deskripsi,
                        'transaction_date' => $request->invoice_date,
                        'status' => 'Pending',
                        'payment_method' => null,
                        'prepared_fund' => $purchaseMaterial->total_harga,
                        'source_type' => 'purchase',
                        'source_id' => $purchaseMaterial->id,
                        'invoice_id' => $invoice->id
                    ]);
                    $expense->save();

                    // Update purchase material with expense_id
                    $purchaseMaterial->expense_id = $expense->id;
                    $purchaseMaterial->save();

                    $totalPurchaseAmount += $totalHarga;
                }

                $invoice->total_amount = $totalPurchaseAmount;
                $invoice->save();

                // Logika termin fleksibel
                if ($request->has('termins') && is_array($request->termins) && count($request->termins) > 0) {
                    foreach ($request->termins as $i => $terminData) {
                        \App\Models\Termin::create([
                            'proyek_id' => $request->proyek_id,
                            'invoice_id' => $invoice->id,
                            'nama_termin' => $terminData['nama_termin'] ?? 'Termin ' . ($i+1),
                            'nilai_termin' => $terminData['nilai_termin'],
                            'dp_percentage' => $terminData['dp_percentage'] ?? 0,
                            'nilai_dp' => $terminData['nilai_dp'] ?? 0,
                            'nilai_pelunasan' => $terminData['nilai_pelunasan'] ?? $terminData['nilai_termin'],
                            'tanggal_dp' => $terminData['tanggal_dp'] ?? null,
                            'tanggal_pelunasan' => $terminData['tanggal_pelunasan'] ?? null,
                            'status_termin' => $terminData['status_termin'] ?? 'Belum Dibayar',
                            'keterangan' => $terminData['keterangan'] ?? ($request->notes ?? '-')
                        ]);
                    }
                } elseif (!$request->has('is_cash') || !$request->is_cash) {
                    // Jika tidak cash dan tidak ada data termin, buat 1 termin default
                    \App\Models\Termin::create([
                        'proyek_id' => $request->proyek_id,
                        'invoice_id' => $invoice->id,
                        'nama_termin' => 'Termin 1',
                        'nilai_termin' => $invoice->total_amount,
                        'dp_percentage' => 0,
                        'nilai_dp' => 0,
                        'nilai_pelunasan' => $invoice->total_amount,
                        'tanggal_dp' => null,
                        'tanggal_pelunasan' => null,
                        'status_termin' => 'Belum Dibayar',
                        'keterangan' => $request->notes ?? '-'
                    ]);
                }

                DB::commit();
                \Log::info('Invoice created successfully:', ['invoice_id' => $invoice->id]);
                return new InvoiceResource($invoice);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error creating invoice:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create invoice',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Unexpected error in invoice store:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mengupdate invoice (status dan pembayaran)
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unpaid,partially_paid,paid,cancelled',
            'amount_paid' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $invoice = Invoice::findOrFail($id);

        $invoice->status = $request->status;
        $invoice->amount_paid = $request->amount_paid;

        // Update status berdasarkan amount_paid
        $invoice->status = $invoice->determineStatus(); // Pastikan status diperbarui
        $invoice->save();

        return new InvoiceResource($invoice);
    }

    // Menghapus invoice
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'unpaid') {
            return response()->json(['error' => 'Invoice cannot be deleted unless it is unpaid.'], 400);
        }

        try {
            // Pastikan tidak ada transaksi atau pembayaran yang terkait sebelum menghapus
            if ($invoice->purchaseMaterials->isNotEmpty()) {
                return response()->json(['error' => 'Cannot delete invoice with associated purchase materials.'], 400);
            }

            $invoice->purchaseMaterials()->delete();
            $invoice->delete();
            return response()->json(['message' => 'Invoice deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete invoice. ' . $e->getMessage()], 500);
        }
    }
}
