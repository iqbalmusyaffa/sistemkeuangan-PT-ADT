<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merek;
use App\Http\Resources\MerekResource;
use Yajra\DataTables\Facades\DataTables;

class MerekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Merek::select(['id', 'name', 'deskripsi']);
            return DataTables::of($data)->make(true);
        }
        return MerekResource::collection(Merek::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Check if brand already exists
            $existingBrand = Merek::where('name', $validatedData['name'])->first();
            
            if ($existingBrand) {
                return new MerekResource($existingBrand);
            }

            $merek = Merek::create($validatedData);
            return new MerekResource($merek);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menambahkan merek. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Find or create a brand
     */
    public function findOrCreate(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $merek = Merek::firstOrCreate(
                ['name' => $validatedData['name']],
                $validatedData
            );
            
            return new MerekResource($merek);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memproses merek. Silakan coba lagi nanti.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $merek = Merek::findOrFail($id);
        return new MerekResource($merek);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $merek = Merek::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|unique:mereks,name,' . $id,
        ]);

        $merek->update($validated);

        return new MerekResource($merek);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $merek = Merek::findOrFail($id);
        $merek->delete();
        return response()->json(null, 204);
    }
}
