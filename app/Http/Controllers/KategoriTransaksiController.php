<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use Yajra\DataTables\Facades\DataTables;

class KategoriTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Kategori::select(['id', 'nama_kategori', 'jenis', 'deskripsi']);
            return DataTables::of($data)->make(true);
        }
        return response()->json(Kategori::all());
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
        ]);

        try {
            $kategori = Kategori::create($validatedData);
            return response()->json($kategori, 201);
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
        return response()->json($kategori);
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
        ]);

        $kategori->update($request->all());

        return response()->json($kategori);
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
