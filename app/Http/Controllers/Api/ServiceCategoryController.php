<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\ServiceCategory;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Log;

class ServiceCategoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ServiceCategory::with('unit')->select(['id', 'nama_kategori', 'jenis', 'harga', 'unit_id', 'deskripsi']);
            return DataTables::of($data)->make(true);
        }
        return response()->json(ServiceCategory::with('unit')->get());
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
            $servicecatergory =ServiceCategory::create($validatedData);
            return response()->json($servicecatergory, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menambahkan kategori. Silakan coba lagi nanti.'], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $servicecatergory = ServiceCategory::findOrFail($id);
        return response()->json($servicecatergory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $servicecatergory = ServiceCategory::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|unique:kategoris,nama_kategori,' . $id,
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'harga' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'deskripsi' => 'nullable|string',
        ]);

        $servicecatergory->update($request->all());

        return response()->json($servicecatergory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $servicecatergory = ServiceCategory::findOrFail($id);
        $servicecatergory->delete();
        return response()->json(null, 204);
    }
}
