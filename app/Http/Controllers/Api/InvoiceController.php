<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\PurchaseMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\InvoiceResource;
use App\Models\Proyek;

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
                'payment_method_id' => 'nullable|exists:payment_methods,id',
                'invoice_date' => 'required|date',
                'purchase_materials' => 'required|array|min:1',
                'purchase_materials.*.item' => 'required|string',
                'purchase_materials.*.qty' => 'required|numeric|min:1',
                'purchase_materials.*.harga' => 'required|numeric|min:0',
                'purchase_materials.*.type' => 'required|string|max:255',
                'purchase_materials.*.merek_id' => 'nullable|exists:mereks,id',
                'purchase_materials.*.unit_id' => 'required|exists:units,id',
                'purchase_materials.*.category_id' => 'nullable|exists:kategoris,id',
                'purchase_materials.*.deskripsi' => 'nullable|string',
                'use_ppn' => 'boolean',
                'use_pph_non_final' => 'boolean',
                'use_pph_final' => 'boolean',
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
                // Validasi anggaran proyek
                $proyek = Proyek::find($request->proyek_id);
                $totalInvoice = Invoice::where('proyek_id', $request->proyek_id)->sum('total_amount');
                $totalBaru = 0;
                foreach ($request->purchase_materials as $item) {
                    $totalBaru += $item['qty'] * $item['harga'];
                }
                if ($proyek && ($totalInvoice + $totalBaru) > $proyek->anggaran_kontrak) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Total invoice melebihi anggaran proyek'
                    ], 422);
                }

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
                    'payment_method_id' => $request->payment_method_id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => $request->invoice_date,
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'notes' => $request->notes,
                    'use_ppn' => $request->use_ppn ?? false,
                    'use_pph_non_final' => $request->use_pph_non_final ?? false,
                    'use_pph_final' => $request->use_pph_final ?? false,
                ]);

                $totalPurchaseAmount = 0;
                $totalBarangAmount = 0;
                $totalJasaAmount = 0;

                foreach ($request->purchase_materials as $item) {
                    $totalHarga = $item['qty'] * $item['harga'];
                    $totalPurchaseAmount += $totalHarga;

                    if (isset($item['is_service']) && $item['is_service']) {
                        $totalJasaAmount += $totalHarga;
                    } else {
                        $totalBarangAmount += $totalHarga;
                    }

                    $purchaseMaterial = new PurchaseMaterial([
                        'item' => $item['item'],
                        'type' => $item['type'],
                        'spesifikasi' => $item['spesifikasi'] ?? null,
                        'unit_id' => $item['unit_id'],
                        'qty' => $item['qty'],
                        'harga' => $item['harga'],
                        'total_harga' => $totalHarga,
                        'deskripsi' => $item['deskripsi'] ?? null,
                        'proyek_id' => $request->proyek_id,
                        'is_service' => $item['is_service'] ?? false,
                        'category_id' => $item['category_id'] ?? null,
                        'service_category_id' => $item['service_category_id'] ?? null,
                        'merek_id' => $item['merek_id'] ?? null,
                        'invoice_id' => $invoice->id
                    ]);
                    $purchaseMaterial->save();

                    // Create expense record
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

                    $purchaseMaterial->expense_id = $expense->id;
                    $purchaseMaterial->save();
                }

                // Calculate taxes
                $pphNonFinalBarang = $request->use_pph_non_final ? ($totalBarangAmount * 0.015) : 0;
                $pphNonFinalJasa = $request->use_pph_non_final ? ($totalJasaAmount * 0.02) : 0;
                $pphNonFinalTotal = $pphNonFinalBarang + $pphNonFinalJasa;

                $ppnAmount = $request->use_ppn ? ($totalPurchaseAmount * 0.11) : 0;

                $netProfit = $totalPurchaseAmount * 0.3;
                $pphFinal = $request->use_pph_final ? ($netProfit * 0.22) : 0;

                $invoice->update([
                    'total_amount' => $totalPurchaseAmount,
                    'pph_non_final_amount' => $pphNonFinalTotal,
                    'pph_final_amount' => $pphFinal,
                    'ppn_amount' => $ppnAmount,
                    'net_profit' => $netProfit,
                    'payment_method_id' => $request->payment_method_id,
                ]);

                // Handle termins
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
            'amount_paid' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $invoice = Invoice::findOrFail($id);
        $invoice->status = $request->status;
        $invoice->amount_paid = $request->amount_paid;
        $invoice->status = $invoice->determineStatus();
        if ($request->has('payment_method_id')) {
            $invoice->payment_method_id = $request->payment_method_id;
        }
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
            DB::beginTransaction();
            
            // Delete associated expenses first
            $invoice->expenses()->delete();
            
            // Delete associated termins
            $invoice->termins()->delete();
            
            // Delete associated purchase materials
            $invoice->purchaseMaterials()->delete();
            
            // Finally delete the invoice
            $invoice->delete();
            
            DB::commit();
            return response()->json(['message' => 'Invoice and all associated records deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete invoice. ' . $e->getMessage()], 500);
        }
    }
}
