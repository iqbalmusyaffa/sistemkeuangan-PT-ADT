<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchasematerial;
use Yajra\DataTables\Facades\DataTables;

class PurchasematerialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Purchasematerial::with(['unit', 'merek'])->select(['id', 'item', 'merek_id', 'type', 'spesifikasi', 'unit_id', 'qty', 'harga', 'total_harga', 'deskripsi']);
            return DataTables::of($data)->make(true);
        }
        return response()->json(Purchasematerial::with(['unit', 'merek'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'item' => 'required|string|max:255',
            'merek_id' => 'required|exists:mereks,id',
            'type' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'qty' => 'required|integer',
            'harga' => 'required|numeric',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $total_harga = $validatedData['qty'] * $validatedData['harga'];
            $validatedData['total_harga'] = $total_harga;

            $purchasematerial = Purchasematerial::create($validatedData);
            return response()->json($purchasematerial, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menambahkan Pembelian material. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $purchasematerial = Purchasematerial::with(['unit', 'merek'])->findOrFail($id);
        return response()->json($purchasematerial);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $purchasematerial = Purchasematerial::findOrFail($id);

        $validatedData = $request->validate([
            'item' => 'required|string|max:255',
            'merek_id' => 'required|exists:mereks,id',
            'type' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'qty' => 'required|integer',
            'harga' => 'required|numeric',
            'deskripsi' => 'nullable|string',
        ]);

        $total_harga = $validatedData['qty'] * $validatedData['harga'];
        $validatedData['total_harga'] = $total_harga;

        $purchasematerial->update($validatedData);

        return response()->json($purchasematerial);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $purchasematerial = Purchasematerial::findOrFail($id);
        $purchasematerial->delete();
        return response()->json(null, 204);
    }
}
