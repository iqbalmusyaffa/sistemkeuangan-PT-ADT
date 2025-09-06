<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseMaterial;
use App\Models\Kategori;
use App\Models\ServiceCategory;
use App\Models\Merek;
use App\Models\Proyek;
use App\Models\Unit;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Auth;

class PurchasematerialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if (!$request->has('proyek_id')) {
                return response()->json([
                    'error' => 'Proyek ID harus dipilih'
                ], 400);
            }

            $proyekId = $request->proyek_id;
            $proyek = Proyek::find($proyekId);

            if (!$proyek) {
                return response()->json([
                    'error' => 'Proyek tidak ditemukan'
                ], 404);
            }

            $purchases = PurchaseMaterial::with(['unit', 'merek', 'category', 'serviceCategory', 'proyek'])
                ->where('proyek_id', $proyekId)
                ->get()
                ->map(function ($purchase) {
                    $category = $purchase->is_service ? $purchase->serviceCategory : $purchase->category;
                    return array_merge($purchase->toArray(), [
                        'category_name' => $category ? $category->nama_kategori : null
                    ]);
                });

            return response()->json([
                'proyek' => $proyek,
                'purchases' => $purchases
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PurchasematerialController@index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data'
            ], 500);
        }
    }
public function datatables(Request $request)
{
    try {
        // Ambil data relasi yang dibutuhkan
        $query = PurchaseMaterial::with(['unit', 'merek', 'category', 'serviceCategory', 'proyek']);

        // Logging proyek_id untuk debug
        \Log::info('Request proyek_id: ' . $request->proyek_id);

        // Filter berdasarkan proyek jika tersedia
        if ($request->filled('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()

            // Nama proyek (diambil dari relasi 'proyek')
            ->addColumn('nama_proyek', fn($row) => $row->proyek->nama_proyek ?? '-')

            // Nama customer (juga dari tabel proyek, karena nama_customer adalah kolom di tabel proyeks)
            ->addColumn('nama_customer', fn($row) => $row->proyek->nama_customer ?? '-')

            // Merek
            ->addColumn('merek', fn($row) => $row->merek->name ?? '-')

            // Unit
            ->addColumn('unit', fn($row) => $row->unit->unit_name ?? '-')

            // Kategori (berbeda jika jasa vs material)
            ->addColumn('category_name', function ($row) {
                return $row->is_service
                    ? ($row->serviceCategory->nama_kategori ?? '-')
                    : ($row->category->nama_kategori ?? '-');
            })

            // Status: Jasa / Material
            ->addColumn('status', function ($row) {
                return $row->is_service ? 'Jasa' : 'Material';
            })

            // Aksi: Tombol Edit & Delete
            ->addColumn('aksi', function ($row) {
                return '
                    <button class="btn btn-sm btn-primary" onclick="editPurchase(' . $row->id . ')">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deletePurchase(' . $row->id . ')">Hapus</button>
                ';
            })

            // Aktifkan HTML pada kolom-kolom custom
            ->rawColumns(['nama_proyek', 'nama_customer', 'merek', 'unit', 'category_name', 'aksi'])

            ->make(true);

    } catch (\Exception $e) {
        \Log::error('PurchasematerialController@datatables error: ' . $e->getMessage());
        return response()->json([
            'error' => 'Gagal memuat data pembelian: ' . $e->getMessage()
        ], 500);
    }
}



public function show($id)
{
    try {
        $purchase = PurchaseMaterial::with([
            'unit',
            'merek',
            'category',
            'serviceCategory',
            'proyek'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $purchase
        ]);
    } catch (\Exception $e) {
        \Log::error('PurchasematerialController@show error: ' . $e->getMessage());
        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }
}

    /**
     * Store a newly created resource in storage.
     */
//    public function store(Request $request)
// {
//     try {
//         // Validasi awal supaya unit_id, proyek_id, dsb. valid dulu
//         $validated = $request->validate([
//             'item' => 'required|string|max:255',
//             'type' => 'required|string|max:255',
//             'spesifikasi' => 'nullable|string',
//             'unit_id' => 'required|exists:units,id',
//             'qty' => 'required|integer|min:1',
//             'harga' => 'required|numeric|min:0',
//             'deskripsi' => 'nullable|string',
//             'proyek_id' => 'required|exists:proyeks,id',
//             'invoice_id' => 'nullable|exists:invoices,id',
//         ]);

//         DB::beginTransaction();

//         $unit = Unit::findOrFail($validated['unit_id']);
//         $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

//         // Validasi tambahan
//         $additional = $isService
//             ? $request->validate([
//                 'service_category_id' => 'required|exists:service_categories,id',
//                 'merek_id' => 'nullable|exists:mereks,id'
//             ])
//             : $request->validate([
//                 'category_id' => 'required|exists:kategoris,id',
//                 'merek_id' => 'required|exists:mereks,id'
//             ]);

//         $validated += $additional;

//         $total_harga = $validated['qty'] * $validated['harga'];

//         $data = [
//             'item' => $validated['item'],
//             'type' => $validated['type'],
//             'spesifikasi' => $validated['spesifikasi'] ?? null,
//             'unit_id' => $validated['unit_id'],
//             'qty' => $validated['qty'],
//             'harga' => $validated['harga'],
//             'total_harga' => $total_harga,
//             'deskripsi' => $validated['deskripsi'] ?? null,
//             'proyek_id' => $validated['proyek_id'],
//             'is_service' => $isService,
//             'invoice_id' => $validated['invoice_id'] ?? null,
//         ];

//         if ($isService) {
//             $data['service_category_id'] = $validated['service_category_id'];
//             $data['category_id'] = null;
//             $data['merek_id'] = $validated['merek_id'] ?? Merek::firstOrCreate(
//                 ['name' => '-'],
//                 ['description' => 'Default merek for services']
//             )->id;
//         } else {
//             $data['category_id'] = $validated['category_id'];
//             $data['service_category_id'] = null;
//             $data['merek_id'] = $validated['merek_id'];
//         }

//         $purchase = PurchaseMaterial::create($data);

//         $expense = new \App\Models\Expense([
//             'user_id' => auth()->id(),
//             'proyek_id' => $purchase->proyek_id,
//             'category_id' => $isService ? null : $purchase->category_id,
//             'service_category_id' => $isService ? $purchase->service_category_id : null,
//             'amount' => $purchase->total_harga,
//             'description' => "Pembelian " . $purchase->item . " - " . $purchase->deskripsi,
//             'transaction_date' => now(),
//             'status' => 'Pending',
//             'payment_method' => null,
//             'prepared_fund' => $purchase->total_harga,
//             'source_type' => 'purchase',
//             'source_id' => $purchase->id
//         ]);
//         $expense->save();

//         $purchase->expense_id = $expense->id;
//         $purchase->save();

//         DB::commit();

//         return response()->json([
//             'status' => 'success',
//             'data' => $purchase
//         ], 201);
//     } catch (ValidationException $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Validasi gagal.',
//             'errors' => $e->errors()
//         ], 422);
//     } catch (ModelNotFoundException $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Data tidak ditemukan.',
//             'error' => $e->getMessage()
//         ], 404);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         \Log::error('Error in PurchasematerialController@store: ' . $e->getMessage());
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Gagal menambahkan pembelian material.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'item' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'qty' => 'required|integer|min:1',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        DB::beginTransaction();

        $unit = Unit::findOrFail($validated['unit_id']);
        $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

        $additional = $isService
            ? $request->validate([
                'service_category_id' => 'required|exists:service_categories,id',
                'merek_id' => 'nullable|exists:mereks,id'
            ])
            : $request->validate([
                'category_id' => 'required|exists:kategoris,id',
                'merek_id' => 'required|exists:mereks,id'
            ]);

        $validated += $additional;

        $data = [
            'item' => $validated['item'],
            'type' => $validated['type'],
            'spesifikasi' => $validated['spesifikasi'] ?? null,
            'unit_id' => $validated['unit_id'],
            'qty' => $validated['qty'],
            'harga' => $validated['harga'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'proyek_id' => $validated['proyek_id'],
            'is_service' => $isService,
            'invoice_id' => $validated['invoice_id'] ?? null,
        ];

        if ($isService) {
            $data['service_category_id'] = $validated['service_category_id'];
            $data['category_id'] = null;
            $data['merek_id'] = $validated['merek_id'] ?? Merek::firstOrCreate(
                ['name' => '-'],
                ['description' => 'Default merek for services']
            )->id;
        } else {
            $data['category_id'] = $validated['category_id'];
            $data['service_category_id'] = null;
            $data['merek_id'] = $validated['merek_id'];
        }

        $purchase = PurchaseMaterial::create($data); // Otomatis trigger expense dari model

        DB::commit();

        return response()->json([
            'status' => 'success',
            'data' => $purchase
        ], 201);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in PurchasematerialController@store: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menambahkan pembelian material.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update the specified resource in storage.
     */
//     public function update(Request $request, string $id)
// {
//     try {
//         // Validasi awal agar unit_id, proyek_id, dll diperiksa dulu
//         $baseRules = [
//             'item' => 'required|string|max:255',
//             'type' => 'required|string|max:255',
//             'spesifikasi' => 'nullable|string',
//             'unit_id' => 'required|exists:units,id',
//             'qty' => 'required|integer|min:1',
//             'harga' => 'required|numeric|min:0',
//             'deskripsi' => 'nullable|string',
//             'proyek_id' => 'required|exists:proyeks,id',
//             'invoice_id' => 'nullable|exists:invoices,id'
//         ];

//         $validated = $request->validate($baseRules);

//         DB::beginTransaction();

//         $purchasematerial = PurchaseMaterial::findOrFail($id);

//         $unit = Unit::findOrFail($validated['unit_id']);
//         $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

//         // Validasi tambahan tergantung jenis unit
//         $additional = $isService
//             ? $request->validate([
//                 'service_category_id' => 'required|exists:service_categories,id',
//                 'merek_id' => 'nullable|exists:mereks,id'
//             ])
//             : $request->validate([
//                 'category_id' => 'required|exists:kategoris,id',
//                 'merek_id' => 'required|exists:mereks,id'
//             ]);

//         $validated += $additional;

//         $total_harga = $validated['qty'] * $validated['harga'];

//         $data = [
//             'item' => $validated['item'],
//             'type' => $validated['type'],
//             'spesifikasi' => $validated['spesifikasi'] ?? null,
//             'unit_id' => $validated['unit_id'],
//             'qty' => $validated['qty'],
//             'harga' => $validated['harga'],
//             'total_harga' => $total_harga,
//             'deskripsi' => $validated['deskripsi'] ?? null,
//             'proyek_id' => $validated['proyek_id'],
//             'is_service' => $isService,
//             'invoice_id' => $validated['invoice_id'] ?? null,
//         ];

//         if ($isService) {
//             $data['service_category_id'] = $validated['service_category_id'];
//             $data['category_id'] = null;
//             $data['merek_id'] = $validated['merek_id'] ?? Merek::firstOrCreate(
//                 ['name' => '-'],
//                 ['description' => 'Default merek for services']
//             )->id;
//         } else {
//             $data['category_id'] = $validated['category_id'];
//             $data['service_category_id'] = null;
//             $data['merek_id'] = $validated['merek_id'];
//         }

//         $purchasematerial->update($data);

//         // Update atau buat expense
//         if ($purchasematerial->expense_id) {
//             $expense = \App\Models\Expense::find($purchasematerial->expense_id);
//             if ($expense) {
//                 $expense->update([
//                     'proyek_id' => $purchasematerial->proyek_id,
//                     'category_id' => $isService ? null : $purchasematerial->category_id,
//                     'service_category_id' => $isService ? $purchasematerial->service_category_id : null,
//                     'amount' => $purchasematerial->total_harga,
//                     'description' => "Pembelian " . $purchasematerial->item . " - " . $purchasematerial->deskripsi,
//                     'prepared_fund' => $purchasematerial->total_harga
//                 ]);
//             } else {
//                 $expense = \App\Models\Expense::create([
//                     'user_id' => auth()->id(),
//                     'proyek_id' => $purchasematerial->proyek_id,
//                     'category_id' => $isService ? null : $purchasematerial->category_id,
//                     'service_category_id' => $isService ? $purchasematerial->service_category_id : null,
//                     'amount' => $purchasematerial->total_harga,
//                     'description' => "Pembelian " . $purchasematerial->item . " - " . $purchasematerial->deskripsi,
//                     'transaction_date' => now(),
//                     'status' => 'Pending',
//                     'payment_method' => null,
//                     'prepared_fund' => $purchasematerial->total_harga,
//                     'source_type' => 'purchase',
//                     'source_id' => $purchasematerial->id
//                 ]);
//                 $purchasematerial->expense_id = $expense->id;
//                 $purchasematerial->save();
//             }
//         }

//         DB::commit();
//         return response()->json([
//             'status' => 'success',
//             'data' => $purchasematerial
//         ]);
//     } catch (ValidationException $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Validasi gagal.',
//             'errors' => $e->errors()
//         ], 422);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         \Log::error('Error in PurchasematerialController@update: ' . $e->getMessage());
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Gagal mengupdate pembelian material.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
public function update(Request $request, string $id)
{
    try {
        $baseRules = [
            'item' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'spesifikasi' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'qty' => 'required|integer|min:1',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'nullable|exists:invoices,id'
        ];

        $validated = $request->validate($baseRules);

        DB::beginTransaction();

        $purchasematerial = PurchaseMaterial::findOrFail($id);
        $unit = Unit::findOrFail($validated['unit_id']);
        $isService = in_array(strtolower($unit->unit_name), ['jasa', 'set', 'transaksi']);

        $additional = $isService
            ? $request->validate([
                'service_category_id' => 'required|exists:service_categories,id',
                'merek_id' => 'nullable|exists:mereks,id'
            ])
            : $request->validate([
                'category_id' => 'required|exists:kategoris,id',
                'merek_id' => 'required|exists:mereks,id'
            ]);

        $validated += $additional;

        $data = [
            'item' => $validated['item'],
            'type' => $validated['type'],
            'spesifikasi' => $validated['spesifikasi'] ?? null,
            'unit_id' => $validated['unit_id'],
            'qty' => $validated['qty'],
            'harga' => $validated['harga'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'proyek_id' => $validated['proyek_id'],
            'is_service' => $isService,
            'invoice_id' => $validated['invoice_id'] ?? null,
        ];

        if ($isService) {
            $data['service_category_id'] = $validated['service_category_id'];
            $data['category_id'] = null;
            $data['merek_id'] = $validated['merek_id'] ?? Merek::firstOrCreate(
                ['name' => '-'],
                ['description' => 'Default merek for services']
            )->id;
        } else {
            $data['category_id'] = $validated['category_id'];
            $data['service_category_id'] = null;
            $data['merek_id'] = $validated['merek_id'];
        }

        $purchasematerial->update($data); // Akan memicu update expense dari model

        DB::commit();
        return response()->json([
            'status' => 'success',
            'data' => $purchasematerial
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error in PurchasematerialController@update: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengupdate pembelian material.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
   public function destroy(string $id)
{
    try {
        $purchasematerial = PurchaseMaterial::findOrFail($id);
        $purchasematerial->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dihapus.'
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error in PurchasematerialController@destroy: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal menghapus data.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function getProyeks(Request $request)
    {
        $proyeks = Proyek::all();
        return response()->json($proyeks);
    }

    public function getByProyek($proyekId)
    {
        $purchases = PurchasMaterial::with(['proyek', 'invoice'])
            ->where('proyek_id', $proyekId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function getByInvoice($invoiceId)
    {
        $purchases = PurchaseMaterial::with(['proyek', 'invoice'])
            ->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }
     public function getServiceCategory($id)
{
    $purchase = PurchaseMaterial::with('serviceCategory')->find($id);

    if (!$purchase) {
        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'service_category_id' => $purchase->service_category_id,
        'nama_kategori' => $purchase->serviceCategory->nama_kategori,
    ]);
}
}
