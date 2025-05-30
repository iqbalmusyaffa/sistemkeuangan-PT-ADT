<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchasematerial;
use App\Models\Proyek;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Resources\PurchaseMaterialResource;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentMethod; // Added PaymentMethod import

class PurchasematerialController extends Controller
{
    /**
     * Display a listing of all purchase materials.
     */
    public function index(Request $request)
    {
        $query = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory', 'expense']); // Added 'expense'

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        $purchaseMaterials = $query->get();
        return PurchaseMaterialResource::collection($purchaseMaterials);
    }

    /**
     * Store a newly created purchase material in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'proyek_id' => 'required|exists:proyeks,id',
            'item' => 'required|string',
            'type' => 'required|string',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'category_id' => 'nullable|exists:kategoris,id',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'is_service' => 'sometimes|boolean',
            'qty' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'merek_id' => 'nullable|exists:mereks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate total harga
            $totalHarga = $request->qty * $request->harga;

            // Get the project to check budget
            $proyek = Proyek::findOrFail($request->proyek_id);
            $currentProjectBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
            // Sum all expenses related to this project that are 'Lunas' or directly from invoices.
            $existingProjectExpenses = Expense::where('proyek_id', $proyek->id)
                ->where('status', 'Lunas')
                ->sum('amount');
            $availableBudget = $currentProjectBudget - $existingProjectExpenses;

            if ($availableBudget < $totalHarga) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sisa anggaran proyek tidak mencukupi untuk pembelian material ini.'
                ], 422);
            }

            // Create purchase material
            $purchaseMaterial = Purchasematerial::create([
                'invoice_id' => $request->invoice_id,
                'proyek_id' => $request->proyek_id,
                'item' => $request->item,
                'type' => $request->type,
                'spesifikasi' => $request->spesifikasi,
                'unit_id' => $request->unit_id,
                'category_id' => $request->category_id,
                'service_category_id' => $request->service_category_id,
                'qty' => $request->qty,
                'harga' => $request->harga,
                'total_harga' => $totalHarga,
                'deskripsi' => $request->deskripsi,
                'merek_id' => $request->merek_id,
                'is_service' => $request->is_service ?? false,
            ]);

            DB::commit();
            // Reload the purchase material to ensure expense_id is populated from the observer
            $purchaseMaterial->load('expense'); // Load the expense relation
            return new PurchaseMaterialResource($purchaseMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create purchase material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified purchase material in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'item' => 'required|string',
            'type' => 'required|string',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'category_id' => 'nullable|exists:kategoris,id',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'qty' => 'required|numeric|min:1',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'merek_id' => 'nullable|exists:mereks,id',
            'is_service' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $purchaseMaterial = Purchasematerial::findOrFail($id);

            // Calculate new total harga
            $totalHarga = $request->qty * $request->harga;

            // Perform budget validation if amount is changing significantly
            $proyek = Proyek::findOrFail($purchaseMaterial->proyek_id);
            $currentProjectBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
            // Exclude the current expense's amount from total expenses for validation if it was already 'Lunas'
            $existingProjectExpenses = Expense::where('proyek_id', $proyek->id)
                ->where('status', 'Lunas')
                ->where('source_type', Expense::SOURCE_PURCHASE)
                ->where('source_id', '!=', $purchaseMaterial->id)
                ->sum('amount');

            $projectedTotalExpenses = $existingProjectExpenses + $totalHarga;

            if ($projectedTotalExpenses > $currentProjectBudget) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update ini akan menyebabkan total pengeluaran melebihi sisa anggaran proyek.'
                ], 422);
            }

            // Update purchase material
            $purchaseMaterial->update([
                'item' => $request->item,
                'type' => $request->type,
                'spesifikasi' => $request->spesifikasi,
                'unit_id' => $request->unit_id,
                'category_id' => $request->category_id,
                'service_category_id' => $request->service_category_id,
                'qty' => $request->qty,
                'harga' => $request->harga,
                'total_harga' => $totalHarga,
                'deskripsi' => $request->deskripsi,
                'merek_id' => $request->merek_id,
                'is_service' => $request->is_service ?? false,
            ]);

            // Update associated expense (if exists)
            if ($purchaseMaterial->expense) {
                // Determine expense status based on the invoice's status
                $invoiceStatus = $purchaseMaterial->invoice ? $purchaseMaterial->invoice->status : Expense::STATUS_PENDING;
                $expenseStatus = Expense::STATUS_PENDING; // Default
                if ($invoiceStatus === Invoice::STATUS_PAID) {
                    $expenseStatus = Expense::STATUS_LUNAS;
                } else if ($invoiceStatus === Invoice::STATUS_UNPAID || $invoiceStatus === Invoice::STATUS_PARTIALLY_PAID) {
                    $expenseStatus = Expense::STATUS_PENDING;
                }

                $purchaseMaterial->expense->update([
                    'amount' => $totalHarga,
                    'description' => "Pembelian " . ($purchaseMaterial->item ?? '') . " untuk proyek " . optional($purchaseMaterial->proyek)->nama_proyek,
                    'category_id' => $purchaseMaterial->is_service ? null : $purchaseMaterial->category_id,
                    'service_category_id' => $purchaseMaterial->is_service ? $purchaseMaterial->service_category_id : null,
                    'prepared_fund' => $totalHarga,
                    'status' => $expenseStatus, // Set updated expense status based on invoice
                    'proyek_id' => $purchaseMaterial->proyek_id, // Ensure proyek_id is updated on expense too
                    'payment_method_id' => $purchaseMaterial->invoice->payment_method_id, // Get payment method from invoice
                ]);
            } else {
                // If for some reason expense doesn't exist, create it (should be rare if booted method works)
                Log::warning("Expense missing for PurchaseMaterial ID {$purchaseMaterial->id} during update. Attempting to create.");
                $expense = Expense::createFromPurchase($purchaseMaterial);
                $purchaseMaterial->expense_id = $expense->id;
                $purchaseMaterial->saveQuietly();
            }

            DB::commit();
            $purchaseMaterial->load('expense'); // Reload to ensure latest expense_id
            return new PurchaseMaterialResource($purchaseMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update purchase material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified purchase material from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $purchaseMaterial = Purchasematerial::findOrFail($id);
            $invoice = $purchaseMaterial->invoice;

            // The expense will be deleted by the PurchaseMaterial's `deleted` observer.
            $purchaseMaterial->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase material deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete purchase material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all projects.
     */
    public function getProyeks()
    {
        try {
            // Using App\Models\Proyek
            $proyeks = Proyek::select('id', 'nama_proyek as nama', 'anggaran_kontrak as anggaran')->orderBy('nama_proyek')->get();
            return response()->json($proyeks, 200);
        } catch (\Exception $e) {
            Log::error('Error PurchasematerialController@getProyeks: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data proyek'], 500);
        }
    }

    /**
     * Get purchase materials by project ID.
     */
    public function getByProject($proyekId)
    {
        $purchaseMaterials = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory', 'expense'])
            ->where('proyek_id', $proyekId)
            ->get();
        return PurchaseMaterialResource::collection($purchaseMaterials);
    }

    /**
     * Get purchase materials by invoice number.
     */
    public function getByInvoice($invoiceId)
    {
        $purchaseMaterials = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory', 'expense'])
            ->where('invoice_id', $invoiceId)
            ->get();
        return PurchaseMaterialResource::collection($purchaseMaterials);
    }

    /**
     * Store multiple purchase materials in bulk.
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'proyek_id' => 'required|exists:proyeks,id',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string|max:255',
            'items.*.type' => 'required|string',
            'items.*.spesifikasi' => 'nullable|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.category_id' => 'nullable|exists:kategoris,id', // Can be null if is_service is true
            'items.*.merek_id' => 'nullable|exists:mereks,id',
            'items.*.deskripsi' => 'nullable|string',
            'items.*.is_service' => 'nullable|boolean',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);
        if ($invoice->proyek_id != $request->proyek_id) {
            return response()->json(['error' => 'Invoice dan Proyek tidak cocok'], 422);
        }

        // Validasi sisa anggaran proyek sebelum insert
        $proyek = Proyek::findOrFail($request->proyek_id);
        $totalPembelian = collect($request->items)->sum(function($item) {
            return $item['qty'] * $item['harga'];
        });

        $currentProjectBudget = $proyek->budget_adjusted ?? $proyek->anggaran_kontrak;
        $existingProjectExpenses = Expense::where('proyek_id', $proyek->id)
            ->where('status', 'Lunas')
            ->sum('amount');
        $availableBudget = $currentProjectBudget - $existingProjectExpenses;

        if ($availableBudget < $totalPembelian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sisa anggaran proyek tidak mencukupi untuk pembelian material ini.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $purchases = [];
            foreach ($request->items as $item) {
                $totalHarga = $item['qty'] * $item['harga'];

                $purchase = Purchasematerial::create([
                    'item' => $item['item'],
                    'type' => $item['type'] ?? null,
                    'spesifikasi' => $item['spesifikasi'] ?? null,
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'unit_id' => $item['unit_id'],
                    'category_id' => $item['category_id'] ?? null, // Ensure handling nullable category_id for services
                    'service_category_id' => $item['service_category_id'] ?? null, // Added service_category_id
                    'merek_id' => $item['merek_id'] ?? null,
                    'deskripsi' => $item['deskripsi'] ?? null,
                    'proyek_id' => $request->proyek_id,
                    'invoice_id' => $request->invoice_id,
                    'total_harga' => $totalHarga,
                    'is_service' => $item['is_service'] ?? false,
                ]);
                $purchases[] = $purchase;
            }

            // Update total_amount invoice after all purchase material saved by observer
            $invoice->total_amount = $invoice->purchaseMaterials()->sum('total_harga');
            $invoice->save();
            $invoice->calculateAllFinancialValues();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Semua item berhasil disimpan', 'data' => PurchaseMaterialResource::collection($purchases)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }
}
