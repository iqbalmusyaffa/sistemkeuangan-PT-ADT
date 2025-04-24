<?php

namespace App\Http\Controllers;

use App\Models\Proyek;
use App\Models\Termin;
use App\Models\PurchaseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProyekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $proyeks = Proyek::with(['termins', 'purchaseMaterials'])->get();
            return response()->json([
                'status' => 'success',
                'data' => $proyeks
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@index: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_customer' => 'required|string|max:255',
            'nama_proyek' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'lokasi' => 'required|string',
            'anggaran_kontrak' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status_project' => 'required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proyek = Proyek::create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Project created successfully',
                'data' => $proyek
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $proyek = Proyek::with(['termins', 'purchaseMaterials'])->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $proyek
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@show: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_customer' => 'sometimes|required|string|max:255',
            'nama_proyek' => 'sometimes|required|string|max:255',
            'nama_perusahaan' => 'sometimes|required|string|max:255',
            'alamat' => 'sometimes|required|string',
            'no_telp' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'lokasi' => 'sometimes|required|string',
            'anggaran_kontrak' => 'sometimes|required|numeric|min:0',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'status_project' => 'sometimes|required|in:Berjalan,Selesai,Batal',
            'deskripsi' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proyek = Proyek::findOrFail($id);
            $proyek->update($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'data' => $proyek
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $proyek = Proyek::findOrFail($id);
            $proyek->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@destroy: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTermins($id)
    {
        try {
            $termins = Termin::where('proyek_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $termins
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@getTermins: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch termins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPurchaseMaterials($id)
    {
        try {
            $purchaseMaterials = PurchaseMaterial::where('proyek_id', $id)
                ->with('material')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $purchaseMaterials
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProyekController@getPurchaseMaterials: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch purchase materials',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 