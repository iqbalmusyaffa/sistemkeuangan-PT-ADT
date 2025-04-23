<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchasematerial;
use App\Models\Kategori;
use App\Models\Merek;
use App\Models\Proyek;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

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
            
            // Verify proyek exists
            $proyek = Proyek::find($proyekId);
            if (!$proyek) {
                return response()->json([
                    'error' => 'Proyek tidak ditemukan'
                ], 404);
            }

            // Get purchases for this proyek only
            $purchases = Purchasematerial::with(['unit', 'merek', 'category', 'proyek'])
                ->where('proyek_id', $proyekId)
                ->get();

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
            // Check if the category is a service
            $category = Kategori::find($request->category_id);
            $isService = $category && str_contains(strtolower($category->nama_kategori), 'jasa');

            // Define validation rules
            $rules = [
                'item' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'spesifikasi' => 'nullable|string',
                'unit_id' => 'required|exists:units,id',
                'category_id' => 'required|exists:kategoris,id',
                'qty' => 'required|integer',
                'harga' => 'required|numeric',
                'deskripsi' => 'nullable|string',
                'proyek_id' => 'required|exists:proyeks,id',
            ];

            // Add merek_id validation based on category type
            if (!$isService) {
                $rules['merek_id'] = 'required|exists:mereks,id';
            } else {
                $rules['merek_id'] = 'nullable|exists:mereks,id';
            }

            $validatedData = $request->validate($rules);

            // Calculate total_harga
            $total_harga = $validatedData['qty'] * $validatedData['harga'];

            // Prepare data for creation
            $data = [
                'item' => $validatedData['item'],
                'type' => $validatedData['type'],
                'spesifikasi' => $validatedData['spesifikasi'] ?? null,
                'unit_id' => $validatedData['unit_id'],
                'category_id' => $validatedData['category_id'],
                'qty' => $validatedData['qty'],
                'harga' => $validatedData['harga'],
                'total_harga' => $total_harga,
                'deskripsi' => $validatedData['deskripsi'] ?? null,
                'proyek_id' => $validatedData['proyek_id'],
            ];

            // Handle merek_id for service categories
            if ($isService) {
                if (isset($validatedData['merek_id'])) {
                    $merek = Merek::find($validatedData['merek_id']);
                    if ($merek && $merek->name === '-') {
                        $data['merek_id'] = $merek->id;
                    } else {
                        // If merek is not '-', find or create the '-' merek
                        $defaultMerek = Merek::where('name', '-')->first();
                        if ($defaultMerek) {
                            $data['merek_id'] = $defaultMerek->id;
                        } else {
                            $data['merek_id'] = null;
                        }
                    }
                } else {
                    // If no merek selected, find or create the '-' merek
                    $defaultMerek = Merek::where('name', '-')->first();
                    if ($defaultMerek) {
                        $data['merek_id'] = $defaultMerek->id;
                    } else {
                        $data['merek_id'] = null;
                    }
                }
            } else {
                $data['merek_id'] = $validatedData['merek_id'];
            }

            $purchasematerial = Purchasematerial::create($data);

            return response()->json($purchasematerial, 201);
        } catch (\Exception $e) {
            Log::error('Error in PurchasematerialController@store: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menambahkan Pembelian material. Silakan coba lagi nanti.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $purchasematerial = Purchasematerial::findOrFail($id);

            // Check if the category is a service
            $category = Kategori::find($request->category_id);
            $isService = $category && str_contains(strtolower($category->nama_kategori), 'jasa');

            // Define validation rules
            $rules = [
                'item' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'spesifikasi' => 'nullable|string',
                'unit_id' => 'required|exists:units,id',
                'category_id' => 'required|exists:kategoris,id',
                'qty' => 'required|integer',
                'harga' => 'required|numeric',
                'deskripsi' => 'nullable|string',
                'proyek_id' => 'required|exists:proyeks,id',
            ];

            // Add merek_id validation based on category type
            if (!$isService) {
                $rules['merek_id'] = 'required|exists:mereks,id';
            } else {
                $rules['merek_id'] = 'nullable|exists:mereks,id';
            }

            $validatedData = $request->validate($rules);

            // Calculate total_harga
            $total_harga = $validatedData['qty'] * $validatedData['harga'];

            // Prepare data for update
            $data = [
                'item' => $validatedData['item'],
                'type' => $validatedData['type'],
                'spesifikasi' => $validatedData['spesifikasi'] ?? null,
                'unit_id' => $validatedData['unit_id'],
                'category_id' => $validatedData['category_id'],
                'qty' => $validatedData['qty'],
                'harga' => $validatedData['harga'],
                'total_harga' => $total_harga,
                'deskripsi' => $validatedData['deskripsi'] ?? null,
                'proyek_id' => $validatedData['proyek_id'],
            ];

            // Handle merek_id for service categories
            if ($isService) {
                if (isset($validatedData['merek_id'])) {
                    $merek = Merek::find($validatedData['merek_id']);
                    if ($merek && $merek->name === '-') {
                        $data['merek_id'] = $merek->id;
                    } else {
                        // If merek is not '-', find or create the '-' merek
                        $defaultMerek = Merek::where('name', '-')->first();
                        if ($defaultMerek) {
                            $data['merek_id'] = $defaultMerek->id;
                        } else {
                            $data['merek_id'] = null;
                        }
                    }
                } else {
                    // If no merek selected, find or create the '-' merek
                    $defaultMerek = Merek::where('name', '-')->first();
                    if ($defaultMerek) {
                        $data['merek_id'] = $defaultMerek->id;
                    } else {
                        $data['merek_id'] = null;
                    }
                }
            } else {
                $data['merek_id'] = $validatedData['merek_id'];
            }

            $purchasematerial->update($data);

            return response()->json($purchasematerial);
        } catch (\Exception $e) {
            Log::error('Error in PurchasematerialController@update: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengupdate Pembelian material. Silakan coba lagi nanti.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $purchasematerial = Purchasematerial::findOrFail($id);
            $purchasematerial->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
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
}
