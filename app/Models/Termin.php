<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Trackable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Storage;

class Termin extends Model
{
    use HasFactory, Trackable;

    protected $fillable = [
        'proyek_id',
        'invoice_id',
        'nama_termin',
        'jenis_termin',
        'termin_ke',
        'nilai_termin',
        'persentase_dp',
        'nilai_dp',
        'nilai_pelunasan',
        'tanggal_dp',
        'tanggal_pelunasan',
        'tanggal_dp_dibayar',
        'tanggal_pelunasan_dibayar',
        'status_termin',
        'keterangan',
        'bukti_pembayaran',
        'dibayar_oleh',
        'expense_id',
        'status_approval',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'nilai_termin' => 'decimal:2',
        'persentase_dp' => 'decimal:2',
        'nilai_dp' => 'decimal:2',
        'nilai_pelunasan' => 'decimal:2',
        'tanggal_dp' => 'date',
        'tanggal_pelunasan' => 'date',
        'tanggal_dp_dibayar' => 'date',
        'tanggal_pelunasan_dibayar' => 'date',
        'approved_at' => 'datetime',
        'termin_ke' => 'integer',
        'jenis_termin' => 'string',
        'status_termin' => 'string'
    ];

    protected $appends = [
        'total_paid',
        'total_dp_paid',
        'total_pelunasan_paid',
        'remaining_dp',
        'remaining_pelunasan',
        'remaining_total',
        'is_dp',
        'is_pelunasan',
        'is_termin_bertahap',
        'approved_by_name' // Tambahkan atribut approved_by_name
    ];
    public $isUpdatingStatus = false;

    protected $hidden = [
        'isUpdatingStatus',
    ];


    // RELATIONS
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function purchaseMaterials(): HasMany
    {
        return $this->hasMany(PurchaseMaterial::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function pembayar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ACCESSORS
    public function getTotalDpPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_dp_paid", 3600, function () {
            return $this->incomes()
                ->where('type', 'dp')
                ->where('status', 'Diterima')
                ->sum('jumlah') ?? 0;
        });
    }

    public function getTotalPelunasanPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_pelunasan_paid", 3600, function () {
            return $this->incomes()
                ->where('type', 'pelunasan')
                ->where('status', 'Diterima')
                ->sum('jumlah') ?? 0;
        });
    }

    public function getTotalPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_paid", 3600, function () {
            return $this->incomes()
                ->where('status', 'Diterima')
                ->sum('jumlah') ?? 0;
        });
    }

    public function getRemainingDpAttribute()
    {
        return max(0, ($this->nilai_dp ?? 0) - ($this->total_dp_paid ?? 0));
    }

    public function getRemainingPelunasanAttribute()
    {
        return max(0, ($this->nilai_pelunasan ?? 0) - ($this->total_pelunasan_paid ?? 0));
    }

    public function getRemainingTotalAttribute()
    {
        return max(0, ($this->nilai_termin ?? 0) - ($this->total_paid ?? 0));
    }

    public function getIsDpAttribute(): bool
    {
        return $this->jenis_termin === 'DP';
    }

    public function getIsPelunasanAttribute(): bool
    {
        return $this->jenis_termin === 'Pelunasan';
    }

    public function getIsTerminBertahapAttribute(): bool
    {
        return $this->jenis_termin === 'Termin Bertahap';
    }

    public function getApprovedByNameAttribute()
    {
        return $this->approved_by ? optional($this->approvedBy)->name : null;
    }

    // UPDATE STATUS BERDASARKAN PEMBAYARAN
    public function updateStatusFromPayments()
    {
        $totalDpPaid = $this->total_dp_paid;
        $totalPelunasanPaid = $this->total_pelunasan_paid;

        if ($totalDpPaid >= $this->nilai_dp && $totalPelunasanPaid >= $this->nilai_pelunasan) {
            $this->status_termin = 'Lunas';
        } elseif ($totalDpPaid >= $this->nilai_dp) {
            $this->status_termin = 'DP Dibayar';
        } else {
            $this->status_termin = 'Belum Dibayar';
        }

        $this->save();
    }

    // VALIDASI RULES UNTUK REQUEST
    public static function getValidationRules($id = null)
    {
        return [
            'proyek_id' => 'required|exists:proyeks,id',
            'invoice_id' => 'required|exists:invoices,id',
            'nama_termin' => 'required|string|max:255',
            'jenis_termin' => 'required|in:DP,Pelunasan,Termin Bertahap',
            'termin_ke' => 'nullable|integer|min:1',
            'nilai_termin' => 'required|numeric|min:0',
            'persentase_dp' => 'required|numeric|min:0|max:100',
            'nilai_dp' => 'required|numeric|min:0',
            'nilai_pelunasan' => 'required|numeric|min:0',
            'tanggal_dp' => 'nullable|date',
            'tanggal_pelunasan' => 'nullable|date|after_or_equal:tanggal_dp',
            'tanggal_dp_dibayar' => 'nullable|date',
            'tanggal_pelunasan_dibayar' => 'nullable|date|after_or_equal:tanggal_dp_dibayar',
            'status_termin' => 'required|in:Belum Dibayar,DP Dibayar,Lunas',
            'keterangan' => 'nullable|string',
            'bukti_pembayaran' => 'nullable',
            'dibayar_oleh' => 'nullable|exists:users,id'
        ];
    }

    // LIFECYCLE HOOKS
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($termin) {
            try {
                Log::info('Creating new termin:', $termin->toArray());

                // Set nilai DP dan pelunasan jika belum diset
                if (!$termin->nilai_dp && $termin->persentase_dp) {
                    $termin->nilai_dp = round($termin->nilai_termin * ($termin->persentase_dp / 100), 2);
                }
                if (!$termin->nilai_pelunasan) {
                    $termin->nilai_pelunasan = round($termin->nilai_termin - $termin->nilai_dp, 2);
                }

                // Handle bukti pembayaran
                if ($termin->bukti_pembayaran instanceof \Illuminate\Http\UploadedFile) {
                    $file = $termin->bukti_pembayaran;

                    // Validate file size
                    if ($file->getSize() > 2048 * 1024) { // 2MB in bytes
                        throw new \Exception('File size exceeds 2MB limit');
                    }

                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        throw new \Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed');
            }

                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
                    $path = 'uploads/bukti_pembayaran/' . $filename;

                    // Ensure directory exists
                    if (!Storage::exists('uploads/bukti_pembayaran')) {
                        Storage::makeDirectory('uploads/bukti_pembayaran');
                    }

                    // Upload file
                    if (!$file->storeAs('uploads/bukti_pembayaran', $filename)) {
                        throw new \Exception('Failed to store file');
                    }

                    $termin->bukti_pembayaran = $path;
                    Log::info('Bukti pembayaran uploaded:', ['path' => $path]);
                }

                Log::info('Termin creation validation passed');
            } catch (\Exception $e) {
                Log::error('Error in Termin model creating: ' . $e->getMessage());
                throw $e;
            }
        });

        static::saving(function ($termin) {
            // Use a static variable inside the closure to prevent infinite loop
            static $isUpdatingStatus = false;
            if ($isUpdatingStatus) {
                return;
            }
            $isUpdatingStatus = true;

            try {
            $oldStatus = $termin->getOriginal('status_termin');
            $termin->updateStatusFromPayments();
            $isUpdatingStatus = false;

                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                $isUpdatingStatus = false;
                Log::error('Error in Termin model saving: ' . $e->getMessage());
                throw $e;
            }
        });

        static::created(function ($termin) {
            try {
            $termin->clearCache();
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                Log::error('Error in Termin model created: ' . $e->getMessage());
            }
        });

        static::updated(function ($termin) {
            try {
            $termin->clearCache();
            } catch (\Exception $e) {
                Log::error('Error in Termin model updated: ' . $e->getMessage());
            }
        });

        static::deleted(function ($termin) {
            try {
            $termin->clearCache();
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                Log::error('Error in Termin model deleted: ' . $e->getMessage());
            }
        });
    }

    // CLEAR CACHE
    public function clearCache()
    {
        Cache::forget("termin_{$this->id}_total_dp_paid");
        Cache::forget("termin_{$this->id}_total_pelunasan_paid");
        Cache::forget("termin_{$this->id}_total_paid");
    }

    // SCOPES
    public function scopeBelumDibayar($query)
    {
        return $query->where('status_termin', 'Belum Dibayar');
    }

    public function scopeDpDibayar($query)
    {
        return $query->where('status_termin', 'DP Dibayar');
    }

    public function scopeLunas($query)
    {
        return $query->where('status_termin', 'Lunas');
    }

    public function scopeByProyek($query, $proyekId)
    {
        return $query->where('proyek_id', $proyekId);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    // TRANSACTIONS
    public function createExpense()
    {
        return DB::transaction(function () {
            // Cek apakah expense sudah ada
            $expense = Expense::where('source_type', 'termin')
                ->where('source_id', $this->id)
                ->first();

            // Jika belum ada expense, buat baru
            if (!$expense) {
                $expense = Expense::create([
                    'user_id' => auth()->id(),
                    'proyek_id' => $this->proyek_id,
                    'category_id' => null,
                    'amount' => $this->nilai_termin, // Total nilai termin sebagai expense awal
                    'description' => "Pengeluaran Termin {$this->nama_termin}",
                    'transaction_date' => $this->tanggal_dp ?? now(),
                    'status' => 'pending', // Changed from 'Belum Lunas' to 'pending'
                    'payment_method_id' => null,
                    'prepared_fund' => $this->nilai_termin,
                    'source_type' => 'termin',
                    'source_id' => $this->id,
                    'invoice_id' => $this->invoice_id
                ]);

                $this->expense_id = $expense->id;
                $this->save();
            }

            // Update status expense berdasarkan pembayaran
            $totalPaid = $this->total_paid;
            $remainingAmount = $this->nilai_termin - $totalPaid;

            $expense->update([
                'amount' => $this->nilai_termin,
                'prepared_fund' => $remainingAmount,
                'status' => $remainingAmount <= 0 ? 'Lunas' : 'pending', // Changed from 'Belum Lunas' to 'pending'
                'description' => "Pengeluaran Termin {$this->nama_termin} (Sisa: " . number_format($remainingAmount, 2) . ")"
            ]);

            return $expense;
        });
    }

    // Method untuk mencatat income saat pembayaran diterima
    public function recordIncome($type, $amount, $tanggal)
    {
        return DB::transaction(function () use ($type, $amount, $tanggal) {
            // Helper untuk ambil kategori pemasukan default
            $getDefaultKategoriId = function() {
                $kategori = \App\Models\Kategori::where('jenis', 'pemasukan')->orderBy('id')->first();
                return $kategori ? $kategori->id : null;
            };

            // Helper untuk ambil payment method default
            $getDefaultPaymentMethodId = function() {
                $pm = \App\Models\PaymentMethod::where('is_active', true)->orderBy('id')->first();
                return $pm ? $pm->id : null;
            };

            $kategoriId = $this->invoice && $this->invoice->kategori_id
                ? $this->invoice->kategori_id
                : $getDefaultKategoriId();

            $paymentMethodId = $this->invoice && $this->invoice->payment_method_id
                ? $this->invoice->payment_method_id
                : $getDefaultPaymentMethodId();

            if (!$kategoriId || !$paymentMethodId) {
                throw new \Exception('Kategori pemasukan atau metode pembayaran tidak ditemukan');
            }

            // Cek apakah income sudah ada
            $existingIncome = \App\Models\Income::where('termin_id', $this->id)
                ->where('type', $type)
                ->where('status', 'Diterima')
                ->first();

            if (!$existingIncome) {
                return \App\Models\Income::create([
                    'jumlah' => $amount,
                    'status' => 'Diterima',
                    'type' => $type,
                    'termin_id' => $this->id,
                    'proyek_id' => $this->proyek_id,
                    'deskripsi' => "Pembayaran {$type} Termin {$this->nama_termin}",
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'invoice_id' => $this->invoice_id,
                    'kategori_id' => $kategoriId,
                    'payment_method_id' => $paymentMethodId,
                    'tanggal' => $tanggal
                ]);
            }

            return $existingIncome;
        });
    }
}
