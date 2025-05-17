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
                'purchase_materials.*.service_category_id' => 'nullable|exists:service_categories,id',
                'purchase_materials.*.deskripsi' => 'nullable|string',
                'use_ppn' => 'boolean',
                'use_pph_non_final' => 'boolean',
                'use_pph_final' => 'boolean',
                'profit_margin_percentage' => 'nullable|numeric|min:0|max:100',
                'termins' => 'nullable|array',
                'termins.*.nama_termin' => 'required_with:termins|string',
                'termins.*.nilai_termin' => 'required_with:termins|numeric|min:0',
                'termins.*.dp_percentage' => 'required_with:termins|numeric|min:0|max:100',
                'termins.*.nilai_dp' => 'required_with:termins|numeric|min:0',
                'termins.*.nilai_pelunasan' => 'required_with:termins|numeric|min:0',
                'termins.*.tanggal_dp' => 'nullable|date',
                'termins.*.tanggal_pelunasan' => 'nullable|date|after_or_equal:termins.*.tanggal_dp',
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
                $proyek = Proyek::findOrFail($request->proyek_id);
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

                // Create invoice
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
                    'profit_margin_percentage' => $request->profit_margin_percentage ?? 30.00,
                ]);

                // Create purchase materials
                $totalPurchaseAmount = 0;
                $totalBarang = 0;
                $totalJasa = 0;
                foreach ($request->purchase_materials as $item) {
                    $totalHarga = $item['qty'] * $item['harga'];
                    $totalPurchaseAmount += $totalHarga;
                    if (!empty($item['is_service'])) {
                        $totalJasa += $totalHarga;
                    } else {
                        $totalBarang += $totalHarga;
                    }

                    PurchaseMaterial::create([
                        'invoice_id' => $invoice->id,
                        'proyek_id' => $request->proyek_id,
                        'item' => $item['item'],
                        'type' => $item['type'],
                        'spesifikasi' => $item['spesifikasi'] ?? null,
                        'unit_id' => $item['unit_id'],
                        'category_id' => $item['category_id'] ?? null,
                        'service_category_id' => $item['service_category_id'] ?? null,
                        'qty' => $item['qty'],
                        'harga' => $item['harga'],
                        'total_harga' => $totalHarga,
                        'deskripsi' => $item['deskripsi'] ?? null,
                        'merek_id' => $item['merek_id'] ?? null,
                        'is_service' => isset($item['is_service']) ? $item['is_service'] : false
                    ]);
                }

                // Calculate taxes and profit
                $pphNonFinalTotal = $request->use_pph_non_final ? ($totalPurchaseAmount * 0.02) : 0;
                $pphFinal = $request->use_pph_final ? ($totalPurchaseAmount * 0.05) : 0;
                $pphJasa = $request->use_pph_jasa ? ($totalJasa * 0.02) : 0;
                $pphBarang = $request->use_pph_barang ? ($totalBarang * 0.015) : 0;
                $ppnAmount = $request->use_ppn ? (($totalBarang + $totalJasa) * 0.11) : 0;
                $netProfit = ($totalBarang + $totalJasa) * ($invoice->profit_margin_percentage / 100);

                // Update invoice with calculated amounts
                $invoice->update([
                    'total_amount' => $totalPurchaseAmount,
                    'pph_non_final_amount' => $pphNonFinalTotal,
                    'pph_final_amount' => $pphFinal,
                    'ppn_amount' => $ppnAmount,
                    'net_profit' => $netProfit,
                    'pph_jasa_amount' => $pphJasa,
                    'pph_barang_amount' => $pphBarang,
                ]);

                // Handle termins if provided
                if ($request->has('termins') && is_array($request->termins) && count($request->termins) > 0) {
                    foreach ($request->termins as $terminData) {
                        \App\Models\Termin::create([
                            'proyek_id' => $request->proyek_id,
                            'invoice_id' => $invoice->id,
                            'nama_termin' => $terminData['nama_termin'],
                            'nilai_termin' => $terminData['nilai_termin'],
                            'dp_percentage' => $terminData['dp_percentage'],
                            'nilai_dp' => $terminData['nilai_dp'],
                            'nilai_pelunasan' => $terminData['nilai_pelunasan'],
                            'tanggal_dp' => $terminData['tanggal_dp'] ?? null,
                            'tanggal_pelunasan' => $terminData['tanggal_pelunasan'] ?? null,
                            'status_termin' => 'Belum Dibayar',
                            'keterangan' => $terminData['keterangan'] ?? ($request->notes ?? '-')
                        ]);
                    }
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
                    'message' => 'Failed to create invoice: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Unexpected error in invoice store:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
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

    // Record payment for an invoice
    public function recordPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            $expense = $invoice->recordPayment($request->amount, $request->payment_method_id);
            
            // Calculate project profit/loss after payment
            $invoice->calculateProjectProfitLoss();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully',
                'data' => [
                    'invoice' => new InvoiceResource($invoice),
                    'expense' => $expense
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get project financial summary
    public function getProjectFinancialSummary($projectId)
    {
        try {
            $invoices = Invoice::where('proyek_id', $projectId)->get();
            
            if ($invoices->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No invoices found for this project'
                ], 404);
            }

            $totalIncome = $invoices->sum('total_income');
            $totalExpenses = $invoices->sum('total_expenses');
            $totalProfitLoss = $invoices->sum('profit_loss');
            $totalProfitLossPercentage = $totalExpenses > 0 ? ($totalProfitLoss / $totalExpenses) * 100 : 0;

            $paymentSummary = [
                'total_invoice_amount' => $invoices->sum('total_amount'),
                'total_paid_amount' => $invoices->sum('amount_paid'),
                'total_remaining' => $invoices->sum('total_amount') - $invoices->sum('amount_paid'),
                'total_termin_amount' => $invoices->sum(function($invoice) {
                    return $invoice->termins->sum('nilai_termin');
                }),
                'total_paid_termin' => $invoices->sum(function($invoice) {
                    return $invoice->termins->where('status_termin', 'Lunas')->sum('nilai_termin');
                }),
                'total_dp_paid' => $invoices->sum(function($invoice) {
                    return $invoice->termins->where('status_termin', 'DP Dibayar')->sum('nilai_dp');
                })
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'total_profit_loss' => $totalProfitLoss,
                    'total_profit_loss_percentage' => $totalProfitLossPercentage,
                    'payment_summary' => $paymentSummary,
                    'invoices' => InvoiceResource::collection($invoices)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get project financial summary: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get payment status for an invoice
    public function getPaymentStatus($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $paymentStatus = $invoice->getPaymentStatusSummary();

            return response()->json([
                'status' => 'success',
                'data' => $paymentStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get payment status: ' . $e->getMessage()
            ], 500);
        }
    }
}
