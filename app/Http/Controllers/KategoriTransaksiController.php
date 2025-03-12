<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
class KategoriTransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Kategori::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|unique:kategori_transaksi',
            'jenis' => 'required|in:pemasukan,pengeluaran',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori = Kategori::create($request->all());

        return response()->json($kategori, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json($kategori);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_kategori' => 'required|unique:kategori_transaksi,nama_kategori,' . $kategori->id,
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
        $kategori->delete();
        return response()->json(null, 204);
    }
}
