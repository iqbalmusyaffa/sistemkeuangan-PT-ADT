<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ServiceCategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = ServiceCategory::with('unit');
            
            if ($request->has('unit_id')) {
                $query->where('unit_id', $request->unit_id);
            }

            if ($request->ajax() && $request->has('datatables')) {
                return DataTables::of($query)->make(true);
            }

            $categories = $query->get();
            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Error in ServiceCategoryController@index: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memuat data kategori jasa.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'jenis' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $serviceCategory = ServiceCategory::create($validatedData);
            return response()->json($serviceCategory, 201);
        } catch (\Exception $e) {
            Log::error('Error in ServiceCategoryController@store: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menambahkan kategori. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $serviceCategory = ServiceCategory::with('unit')->findOrFail($id);
            return response()->json($serviceCategory);
        } catch (\Exception $e) {
            Log::error('Error in ServiceCategoryController@show: ' . $e->getMessage());
            return response()->json(['error' => 'Kategori tidak ditemukan.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $serviceCategory = ServiceCategory::findOrFail($id);

            $validatedData = $request->validate([
                'nama_kategori' => 'required|string|max:255',
                'jenis' => 'required|string',
                'harga' => 'required|numeric|min:0',
                'unit_id' => 'required|exists:units,id',
                'deskripsi' => 'nullable|string',
            ]);

            $serviceCategory->update($validatedData);
            return response()->json($serviceCategory);
        } catch (\Exception $e) {
            Log::error('Error in ServiceCategoryController@update: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengupdate kategori.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $serviceCategory = ServiceCategory::findOrFail($id);
            $serviceCategory->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error in ServiceCategoryController@destroy: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus kategori.'], 500);
        }
    }
}
