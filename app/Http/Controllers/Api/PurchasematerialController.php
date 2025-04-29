<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchasematerial;
use App\Models\Kategori;
use App\Models\ServiceCategory;
use App\Models\Merek;
use App\Models\Proyek;
use App\Models\Unit;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PurchasematerialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if (!$request->has('proyek_id')) {
                return response()->json([
                    'error' => 'Proyek ID harus dipilih'
                ], 400);
            }

            $proyekId = $request->proyek_id;
            $proyek = Proyek::find($proyekId);
            
            if (!$proyek) {
                return response()->json([
                    'error' => 'Proyek tidak ditemukan'
                ], 404);
            }

            $purchases = Purchasematerial::with(['unit', 'merek', 'category', 'serviceCategory', 'proyek'])
                ->where('proyek_id', $proyekId)
                ->get()
                ->map(function ($purchase) {
                    $category = $purchase->is_service ? $purchase->serviceCategory : $purchase->category;
                    return array_merge($purchase->toArray(), [
                        'category_name' => $category ? $category->nama_kategori : null
                    ]);
                });

            return response()->json([
                'proyek' => $proyek,
                'purchases' => $purchases
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PurchasematerialController@index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data'
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

            // Check if the unit is a service type
            $unit = Unit::findOrFail($request->unit_id);
            $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

            // Define validation rules
            $rules = [
                'item' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'spesifikasi' => 'nullable|string',
                'unit_id' => 'required|exists:units,id',
                'qty' => 'required|integer|min:1',
                'harga' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'nullable|exists:invoices,id'
            ];

            // Add category validation based on type
            if ($isService) {
                $rules['service_category_id'] = 'required|exists:service_categories,id';
                $rules['merek_id'] = 'nullable|exists:mereks,id';
            } else {
                $rules['category_id'] = 'required|exists:kategoris,id';
                $rules['merek_id'] = 'required|exists:mereks,id';
            }

            $validatedData = $request->validate($rules);

            // Calculate total_harga
            $total_harga = $validatedData['qty'] * $validatedData['harga'];

            // Validasi anggaran proyek
            $proyek = \App\Models\Proyek::find($validatedData['proyek_id']);
            $totalPembelian = \App\Models\Purchasematerial::where('proyek_id', $validatedData['proyek_id'])->sum('total_harga');
            if ($proyek && ($totalPembelian + $total_harga) > $proyek->anggaran_kontrak) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Total pembelian melebihi anggaran proyek'
                ], 422);
            }

            // Prepare base data
            $data = [
                'item' => $validatedData['item'],
                'type' => $validatedData['type'],
                'spesifikasi' => $validatedData['spesifikasi'] ?? null,
                'unit_id' => $validatedData['unit_id'],
                'qty' => $validatedData['qty'],
                'harga' => $validatedData['harga'],
                'total_harga' => $total_harga,
                'deskripsi' => $validatedData['deskripsi'] ?? null,
                'proyek_id' => $validatedData['proyek_id'],
                'is_service' => $isService,
                'invoice_id' => $validatedData['invoice_id'] ?? null
            ];

            if ($isService) {
                $data['service_category_id'] = $validatedData['service_category_id'];
                $data['category_id'] = null;
                // Handle merek for service (use default '-' merek or null)
                $defaultMerek = Merek::firstOrCreate(
                    ['name' => '-'],
                    ['description' => 'Default merek for services']
                );
                $data['merek_id'] = $defaultMerek->id;
            } else {
                $data['category_id'] = $validatedData['category_id'];
                $data['service_category_id'] = null;
                $data['merek_id'] = $validatedData['merek_id'];
            }

            $purchasematerial = Purchasematerial::create($data);

            // Create corresponding expense record
            $expense = new \App\Models\Expense([
                'user_id' => auth()->id(),
                'proyek_id' => $purchasematerial->proyek_id,
                'category_id' => $isService ? null : $purchasematerial->category_id,
                'service_category_id' => $isService ? $purchasematerial->service_category_id : null,
                'amount' => $purchasematerial->total_harga,
                'description' => "Pembelian " . $purchasematerial->item . " - " . $purchasematerial->deskripsi,
                'transaction_date' => now(),
                'status' => 'Pending',
                'payment_method' => null,
                'prepared_fund' => $purchasematerial->total_harga,
                'source_type' => 'purchase',
                'source_id' => $purchasematerial->id
            ]);
            $expense->save();

            // Update purchase with expense_id
            $purchasematerial->expense_id = $expense->id;
            $purchasematerial->save();
            
            DB::commit();
            return response()->json($purchasematerial, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PurchasematerialController@store: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menambahkan Pembelian material. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $purchasematerial = Purchasematerial::findOrFail($id);
            
            // Check if the unit is a service type
            $unit = Unit::findOrFail($request->unit_id);
            $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

            // Define validation rules
            $rules = [
                'item' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'spesifikasi' => 'nullable|string',
                'unit_id' => 'required|exists:units,id',
                'qty' => 'required|integer|min:1',
                'harga' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string',
                'proyek_id' => 'required|exists:proyeks,id',
                'invoice_id' => 'nullable|exists:invoices,id'
            ];

            // Add category validation based on type
            if ($isService) {
                $rules['service_category_id'] = 'required|exists:service_categories,id';
                $rules['merek_id'] = 'nullable|exists:mereks,id';
            } else {
                $rules['category_id'] = 'required|exists:kategoris,id';
                $rules['merek_id'] = 'required|exists:mereks,id';
            }

            $validatedData = $request->validate($rules);

            // Calculate total_harga
            $total_harga = $validatedData['qty'] * $validatedData['harga'];

            // Prepare base data
            $data = [
                'item' => $validatedData['item'],
                'type' => $validatedData['type'],
                'spesifikasi' => $validatedData['spesifikasi'] ?? null,
                'unit_id' => $validatedData['unit_id'],
                'qty' => $validatedData['qty'],
                'harga' => $validatedData['harga'],
                'total_harga' => $total_harga,
                'deskripsi' => $validatedData['deskripsi'] ?? null,
                'proyek_id' => $validatedData['proyek_id'],
                'is_service' => $isService,
                'invoice_id' => $validatedData['invoice_id'] ?? null
            ];

            if ($isService) {
                $data['service_category_id'] = $validatedData['service_category_id'];
                $data['category_id'] = null;
                // Handle merek for service (use default '-' merek or null)
                $defaultMerek = Merek::firstOrCreate(
                    ['name' => '-'],
                    ['description' => 'Default merek for services']
                );
                $data['merek_id'] = $defaultMerek->id;
            } else {
                $data['category_id'] = $validatedData['category_id'];
                $data['service_category_id'] = null;
                $data['merek_id'] = $validatedData['merek_id'];
            }

            $purchasematerial->update($data);

            // Update or create corresponding expense record
            if ($purchasematerial->expense_id) {
                $expense = \App\Models\Expense::find($purchasematerial->expense_id);
                if ($expense) {
                    $expense->update([
                        'proyek_id' => $purchasematerial->proyek_id,
                        'category_id' => $isService ? null : $purchasematerial->category_id,
                        'service_category_id' => $isService ? $purchasematerial->service_category_id : null,
                        'amount' => $purchasematerial->total_harga,
                        'description' => "Pembelian " . $purchasematerial->item . " - " . $purchasematerial->deskripsi,
                        'prepared_fund' => $purchasematerial->total_harga
                    ]);
                } else {
                    // Create new expense if the previous one was deleted
                    $expense = new \App\Models\Expense([
                        'user_id' => auth()->id(),
                        'proyek_id' => $purchasematerial->proyek_id,
                        'category_id' => $isService ? null : $purchasematerial->category_id,
                        'service_category_id' => $isService ? $purchasematerial->service_category_id : null,
                        'amount' => $purchasematerial->total_harga,
                        'description' => "Pembelian " . $purchasematerial->item . " - " . $purchasematerial->deskripsi,
                        'transaction_date' => now(),
                        'status' => 'Pending',
                        'payment_method' => null,
                        'prepared_fund' => $purchasematerial->total_harga,
                        'source_type' => 'purchase',
                        'source_id' => $purchasematerial->id
                    ]);
                    $expense->save();
                    $purchasematerial->expense_id = $expense->id;
                    $purchasematerial->save();
                }
            }
            
            DB::commit();
            return response()->json($purchasematerial);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PurchasematerialController@update: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengupdate Pembelian material. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            
            $purchasematerial = Purchasematerial::findOrFail($id);
            
            // Delete associated expense if exists
            if ($purchasematerial->expense_id) {
                \App\Models\Expense::where('id', $purchasematerial->expense_id)->delete();
            }
            
            $purchasematerial->delete();
            
            DB::commit();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PurchasematerialController@destroy: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menghapus Pembelian material. Silakan coba lagi nanti.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProyeks(Request $request)
    {
        $proyeks = Proyek::all();
        return response()->json($proyeks);
    }

    public function getByProyek($proyekId)
    {
        $purchases = Purchasematerial::with(['proyek', 'invoice'])
            ->where('proyek_id', $proyekId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function getByInvoice($invoiceId)
    {
        $purchases = Purchasematerial::with(['proyek', 'invoice'])
            ->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }
}
