<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Termin;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $termins = Termin::with('proyek')->orderBy('created_at', 'desc')->get();
        return response()->json($termins);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|exists:proyeks,id',
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
        ]);

        // Calculate DP and Pelunasan values if not provided
        if (!isset($validated['nilai_dp']) || $validated['nilai_dp'] == 0) {
            $validated['nilai_dp'] = $validated['nilai_termin'] * ($validated['dp_percentage'] / 100);
        }
        
        if (!isset($validated['nilai_pelunasan']) || $validated['nilai_pelunasan'] == 0) {
            $validated['nilai_pelunasan'] = $validated['nilai_termin'] - $validated['nilai_dp'];
        }

        $termin = Termin::create($validated);

        return response()->json([
            'message' => 'Termin berhasil dibuat',
            'data' => $termin,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Termin $termin)
    {
        $termin->load('proyek');
        return response()->json($termin);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Termin $termin)
    {
        $validated = $request->validate([
            'proyek_id' => 'required|exists:proyeks,id',
            'nama_termin' => 'required|string|max:255',
            'nilai_termin' => 'required|numeric|min:0',
            'dp_percentage' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
        ]);

        // Calculate DP and Pelunasan values if not provided
        if (!isset($validated['nilai_dp']) || $validated['nilai_dp'] == 0) {
            $validated['nilai_dp'] = $validated['nilai_termin'] * ($validated['dp_percentage'] / 100);
        }
        
        if (!isset($validated['nilai_pelunasan']) || $validated['nilai_pelunasan'] == 0) {
            $validated['nilai_pelunasan'] = $validated['nilai_termin'] - $validated['nilai_dp'];
        }

        $termin->update($validated);

        return response()->json([
            'message' => 'Termin berhasil diupdate',
            'data' => $termin,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Termin $termin)
    {
        $termin->delete();

        return response()->json([
            'message' => 'Termin berhasil dihapus',
        ]);
    }

    /**
     * Get termins by project ID.
     */
    public function getByProject($proyekId)
    {
        $termins = Termin::where('proyek_id', $proyekId)
            ->with('proyek')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'data' => $termins,
            'project' => Proyek::find($proyekId)
        ]);
    }

    /**
     * Update termin status.
     */
    public function updateStatus(Request $request, Termin $termin)
    {
        $validated = $request->validate([
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date',
        ]);

        $termin->update($validated);

        return response()->json([
            'message' => 'Status termin berhasil diupdate',
            'data' => $termin,
        ]);
    }
} 