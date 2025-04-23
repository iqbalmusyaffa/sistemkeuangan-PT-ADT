<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyek;
use Illuminate\Http\Request;

class ProyekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proyeks = Proyek::all();
        return response()->json([
            'status' => 'success',
            'data' => $proyeks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|unique:proyeks,email',
            'lokasi' => 'nullable|string',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        $proyek = Proyek::create($validated);

        return response()->json([
            'message' => 'Proyek berhasil dibuat',
            'data' => $proyek,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Proyek $proyek)
    {
        return response()->json($proyek);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proyek $proyek)
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|unique:proyeks,email,' . $proyek->id,
            'lokasi' => 'nullable|string',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        $proyek->update($validated);

        return response()->json([
            'message' => 'Proyek berhasil diupdate',
            'data' => $proyek,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyek $proyek)
    {
        $proyek->delete();

        return response()->json([
            'message' => 'Proyek berhasil dihapus',
        ]);
    }
}
