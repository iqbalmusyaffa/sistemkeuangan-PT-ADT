<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $paymentMethods = PaymentMethod::latest()->get();
            
            return response()->json([
                'success' => true,
                'message' => 'List data metode pembayaran',
                'data' => $paymentMethods
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_metode' => 'required|string|max:50',
                'deskripsi' => 'nullable|string',
                'is_active' => 'required|boolean',
            ]);

            $paymentMethod = new PaymentMethod();
            $paymentMethod->fill($validated);
            $paymentMethod->created_by = Auth::id();
            $paymentMethod->updated_by = Auth::id();
            $paymentMethod->save();

            return response()->json([
                'success' => true,
                'message' => 'Metode pembayaran berhasil ditambahkan',
                'data' => $paymentMethod
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('PaymentMethod store exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan metode pembayaran',
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
            $paymentMethod = PaymentMethod::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Detail metode pembayaran ditemukan',
                'data' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);

            $validated = $request->validate([
                'nama_metode' => 'required|string|max:50',
                'deskripsi' => 'nullable|string',
                'is_active' => 'required|boolean',
            ]);

            $paymentMethod->fill($validated);
            $paymentMethod->updated_by = Auth::id();
            $paymentMethod->save();

            return response()->json([
                'success' => true,
                'message' => 'Metode pembayaran berhasil diperbarui',
                'data' => $paymentMethod
            ]);
        } catch (\Throwable $e) {
            \Log::error('PaymentMethod update exception: ' . json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui metode pembayaran',
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
            $paymentMethod = PaymentMethod::findOrFail($id);
            $paymentMethod->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data metode pembayaran berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
} 