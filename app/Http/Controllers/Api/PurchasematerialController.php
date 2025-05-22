<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchasematerial;
use App\Models\Project;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Resources\PurchaseMaterialResource;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;

class PurchasematerialController extends Controller
{
    /**
     * Display a listing of all purchase materials.
     */
    public function index(Request $request)
    {
        $query = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory']);

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

            // Calculate total harga
            $totalHarga = $request->qty * $request->harga;

            // Validasi sisa anggaran proyek sebelum insert
            $proyek = \App\Models\Proyek::findOrFail($request->proyek_id);
            if ($proyek->current_budget < $totalHarga) {
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

            // Update invoice total amount
            $invoice = Invoice::findOrFail($request->invoice_id);
            $invoice->total_amount = $invoice->purchaseMaterials()->sum('total_harga');
            $invoice->save();

            // Buat expense otomatis untuk pembelian material
            \App\Models\Expense::createFromPurchase($purchaseMaterial);

            DB::commit();
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

            // Update invoice total amount
            $invoice = $purchaseMaterial->invoice;
            $invoice->total_amount = optional($invoice->purchaseMaterials())->sum('total_harga') ?? 0;
            $invoice->save();

            DB::commit();
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

            $purchaseMaterial->delete();

            // Update invoice total amount
            $invoice->total_amount = $invoice->purchaseMaterials()->sum('total_harga');
            $invoice->save();

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
            $proyeks = Project::select('id', 'nama', 'anggaran')->orderBy('nama')->get();
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
        $purchaseMaterials = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory'])
            ->where('proyek_id', $proyekId)
            ->get();
        return PurchaseMaterialResource::collection($purchaseMaterials);
    }

    /**
     * Get purchase materials by invoice number.
     */
    public function getByInvoice($invoiceId)
    {
        $purchaseMaterials = Purchasematerial::with(['invoice', 'proyek', 'merek', 'unit', 'category', 'serviceCategory'])
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
        'items.*.type' => 'required|string', // jika perlu
        'items.*.spesifikasi' => 'nullable|string', // jika perlu
        'items.*.qty' => 'required|numeric|min:1',
        'items.*.harga' => 'required|numeric|min:0',
        'items.*.unit_id' => 'required|exists:units,id',
        'items.*.category_id' => 'required|exists:kategoris,id',
        'items.*.merek_id' => 'nullable|exists:mereks,id',
        'items.*.deskripsi' => 'nullable|string',
        'items.*.is_service' => 'nullable|boolean',
    ]);

    $invoice = Invoice::findOrFail($request->invoice_id);
    if ($invoice->proyek_id != $request->proyek_id) {
        return response()->json(['error' => 'Invoice dan Proyek tidak cocok'], 422);
    }

    // Validasi sisa anggaran proyek sebelum insert
    $proyek = \App\Models\Proyek::findOrFail($request->proyek_id);
    $totalPembelian = collect($request->items)->sum(function($item) {
        return $item['qty'] * $item['harga'];
    });
    if ($proyek->current_budget < $totalPembelian) {
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
                'category_id' => $item['category_id'],
                'merek_id' => $item['merek_id'] ?? null,
                'deskripsi' => $item['deskripsi'] ?? null,
                'proyek_id' => $request->proyek_id,
                'invoice_id' => $request->invoice_id,
                'total_harga' => $totalHarga,
                'is_service' => $item['is_service'] ?? false,
            ]);
            $purchases[] = $purchase;
        }

        // Buat expense otomatis untuk setiap pembelian material (setelah semua purchase berhasil dibuat)
        foreach ($purchases as $purchase) {
            \App\Models\Expense::createFromPurchase($purchase);
        }

        // Update total_amount invoice setelah semua purchase material disimpan
        $invoice->total_amount = $invoice->purchaseMaterials()->sum('total_harga');
        $invoice->save();

        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Semua item berhasil disimpan']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
    }
}
}
