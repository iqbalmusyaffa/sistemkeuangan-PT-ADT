<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Http\Resources\KategoriResource;
use Yajra\DataTables\Facades\DataTables;

class KategoriTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Kategori::select(['id', 'nama_kategori', 'jenis', 'deskripsi', 'unit_id']);
            return DataTables::of($data)->make(true);
        }
        $query = Kategori::query();
        if ($request->has('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }
        return KategoriResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'jenis' => 'required|string',
            'deskripsi' => 'nullable|string',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        try {
            $kategori = Kategori::create($validatedData);
            return new KategoriResource($kategori);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menambahkan kategori. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kategori = Kategori::findOrFail($id);
        return new KategoriResource($kategori);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|unique:kategoris,nama_kategori,' . $id,
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'deskripsi' => 'nullable|string',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $kategori->update($request->all());

        return new KategoriResource($kategori);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kategori = Kategori::findOrFail($id);
        $kategori->delete();
        return response()->json(null, 204);
    }
}
