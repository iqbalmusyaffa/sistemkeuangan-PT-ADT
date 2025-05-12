<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProyekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $proyeks = Proyek::with(['expenses'])->get()->map(function ($proyek) {
                $proyek->total_expenses = $proyek->expenses()->sum('amount');
                return $proyek;
            });

            return response()->json([
                'status' => 'success',
                'data' => $proyeks
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
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
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $proyek = Proyek::create($validated);
            $proyek->total_expenses = 0; // Proyek baru belum memiliki pengeluaran

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil dibuat',
                'data' => $proyek,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proyek $proyek)
    {
        try {
            $proyek->total_expenses = $proyek->expenses()->sum('amount');
            $proyek->budget_percentage = ($proyek->total_expenses / $proyek->anggaran_kontrak) * 100;

            return response()->json([
                'status' => 'success',
                'data' => $proyek
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proyek tidak ditemukan.',
                'details' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
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
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string',
        ]);

        // Validasi backend: anggaran_kontrak tidak boleh lebih kecil dari total expenses
        $totalExpenses = $proyek->expenses()->sum('amount');
        if ($validated['anggaran_kontrak'] < $totalExpenses) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nilai kontrak lebih kecil dari total pengeluaran proyek. Silakan periksa kembali.'
            ], 422);
        }

        try {
            $proyek->update($validated);
            $proyek->total_expenses = $totalExpenses;
            $proyek->budget_percentage = ($totalExpenses / $proyek->anggaran_kontrak) * 100;

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil diperbarui',
                'data' => $proyek,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proyek $proyek)
    {
        try {
            $proyek->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Proyek berhasil dihapus',
            ], 204);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus proyek.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
