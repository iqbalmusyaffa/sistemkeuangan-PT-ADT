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
        'is_termin_bertahap'
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

    public function approvedByUser()
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
                ->sum('jumlah');
        });
    }

    public function getTotalPelunasanPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_pelunasan_paid", 3600, function () {
            return $this->incomes()
                ->where('type', 'pelunasan')
                ->where('status', 'Diterima')
                ->sum('jumlah');
        });
    }

    public function getTotalPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_paid", 3600, function () {
            return $this->incomes()
                ->where('status', 'Diterima')
                ->sum('jumlah');
        });
    }

    public function getRemainingDpAttribute()
    {
        return max(0, $this->nilai_dp - $this->total_dp_paid);
    }

    public function getRemainingPelunasanAttribute()
    {
        return max(0, $this->nilai_pelunasan - $this->total_pelunasan_paid);
    }

    public function getRemainingTotalAttribute()
    {
        return max(0, $this->nilai_termin - $this->total_paid);
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

        static::updating(function ($termin) {
            try {
                Log::info('Updating termin:', [
                    'id' => $termin->id,
                    'changes' => $termin->getDirty()
                ]);

            // Update tanggal pembayaran berdasarkan status
            if ($termin->status_termin === 'DP Dibayar' && !$termin->tanggal_dp_dibayar) {
                $termin->tanggal_dp_dibayar = now();
            }
            if ($termin->status_termin === 'Lunas' && !$termin->tanggal_pelunasan_dibayar) {
                $termin->tanggal_pelunasan_dibayar = now();
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

                    // Delete old file if exists
                    if ($termin->getOriginal('bukti_pembayaran')) {
                        Storage::delete($termin->getOriginal('bukti_pembayaran'));
                    }

                    $termin->bukti_pembayaran = $path;
                    Log::info('Bukti pembayaran uploaded:', ['path' => $path]);
                }

                Log::info('Termin update validation passed');
            } catch (\Exception $e) {
                Log::error('Error in Termin model updating: ' . $e->getMessage());
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

            $oldStatus = $termin->getOriginal('status_termin');
            $termin->updateStatusFromPayments();
            $isUpdatingStatus = false;

            if ($termin->status_termin !== $oldStatus && $termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
            }
        });

        static::created(function ($termin) {
            $termin->clearCache();
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
            }
        });

        static::updated(function ($termin) {
            $termin->clearCache();
        });

        static::deleted(function ($termin) {
            $termin->clearCache();
            if ($termin->invoice) {
                $termin->invoice->updateStatusFromTermins();
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
            $expense = Expense::create([
                'user_id' => auth()->id(),
                'proyek_id' => $this->proyek_id,
                'category_id' => null,
                'amount' => $this->status_termin === 'Lunas' ? $this->nilai_pelunasan : $this->nilai_dp,
                'description' => $this->status_termin === 'Lunas'
                    ? "Pelunasan Termin {$this->nama_termin}"
                    : "Pembayaran DP Termin {$this->nama_termin}",
                'transaction_date' => $this->status_termin === 'Lunas'
                    ? $this->tanggal_pelunasan
                    : $this->tanggal_dp,
                'status' => 'Lunas',
                'payment_method' => null,
                'prepared_fund' => $this->status_termin === 'Lunas'
                    ? $this->nilai_pelunasan
                    : $this->nilai_dp,
                'source_type' => 'termin',
                'source_id' => $this->id
            ]);

            $this->expense_id = $expense->id;
            $this->save();

            return $expense;
        });
    }
}
