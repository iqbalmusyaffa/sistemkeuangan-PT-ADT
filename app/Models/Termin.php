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
        'approved_by_name'
    ];

    // Added a property to control recursion in hooks
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
        Log::info('[Termin] updateStatusFromPayments START', ['termin_id' => $this->id, 'status_termin' => $this->status_termin]);
        $totalDpPaid = $this->total_dp_paid;
        $totalPelunasanPaid = $this->total_pelunasan_paid;

        $newStatus = $this->status_termin; // Keep current status if no changes

        if ($totalDpPaid >= $this->nilai_dp && $totalPelunasanPaid >= $this->nilai_pelunasan) {
            $newStatus = 'Lunas';
        } elseif ($totalDpPaid >= $this->nilai_dp) {
            $newStatus = 'DP Dibayar';
        } else {
            $newStatus = 'Belum Dibayar';
        }

        $statusChanged = false;
        if ($this->status_termin !== $newStatus) {
            Log::info('[Termin] Status termin berubah', ['termin_id' => $this->id, 'old' => $this->status_termin, 'new' => $newStatus]);
            $this->status_termin = $newStatus;
            $this->clearCache();
            $this->saveQuietly(); // Use saveQuietly to prevent re-triggering hooks
            $statusChanged = true;
        }

        // Selalu update invoice, income, dan expense setelah status termin dihitung
        if ($this->invoice) {
            $this->invoice->refresh();
            $this->invoice->updateStatusFromTermins();
        }
        // Update semua income terkait termin ini agar status income sesuai status termin
        foreach ($this->incomes as $income) {
            if ($income->status !== 'Diterima' && in_array($this->status_termin, ['DP Dibayar', 'Lunas'])) {
                Log::info('[Termin] Update income status to Diterima', ['income_id' => $income->id, 'termin_id' => $this->id]);
                $income->status = 'Diterima';
                $income->save();
            }
            if ($income->status === 'Diterima' && $this->status_termin === 'Belum Dibayar') {
                Log::info('[Termin] Update income status to Pending', ['income_id' => $income->id, 'termin_id' => $this->id]);
                $income->status = 'Pending';
                $income->save();
            }
        }
        // Update expense termin
        if ($this->expense) {
            $newExpenseStatus = $this->status_termin === 'Lunas' ? 'Lunas' : 'pending';
            if ($this->expense->status !== $newExpenseStatus) {
                Log::info('[Termin] Update expense status', ['expense_id' => $this->expense->id, 'old' => $this->expense->status, 'new' => $newExpenseStatus]);
                $this->expense->status = $newExpenseStatus;
                $this->expense->save();
            }
        }
        Log::info('[Termin] updateStatusFromPayments END', ['termin_id' => $this->id, 'status_termin' => $this->status_termin]);
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

                // Handle bukti pembayaran if it's an UploadedFile
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
            // Prevent infinite loop if already updating status
            if ($termin->isUpdatingStatus) {
                return;
            }

            try {
                // The status_termin might be updated by user input or payment logic.
                // We let the updateStatusFromPayments method in the updated hook handle it,
                // unless explicitly setting status in this request is desired to override.
                // For now, let the updated hook handle the side effects.
            } catch (\Exception $e) {
                Log::error('Error in Termin model saving: ' . $e->getMessage());
                throw $e;
            }
        });

        static::created(function ($termin) {
            try {
                $termin->clearCache();
                // Ensure expense and income are created/updated after initial creation
                $termin->createExpense();

                if ($termin->status_termin === 'DP Dibayar' || $termin->status_termin === 'Lunas') {
                    // Record DP income if applicable (pakai tanggal_dp_dibayar jika ada, fallback ke tanggal_dp)
                    $tanggalDp = $termin->tanggal_dp_dibayar ?: $termin->tanggal_dp;
                    if ($termin->nilai_dp > 0 && $tanggalDp) {
                        $dpIncomeExists = $termin->incomes()->where('type', 'dp')->exists();
                        if (!$dpIncomeExists) {
                            try {
                                $termin->recordIncome('dp', $termin->nilai_dp, $tanggalDp);
                            } catch (\Exception $e) {
                                Log::error('[Termin] Gagal create income DP (created hook)', ['termin_id' => $termin->id, 'error' => $e->getMessage()]);
                            }
                        }
                    }
                    // Record Pelunasan income if status is Lunas (pakai tanggal_pelunasan_dibayar jika ada, fallback ke tanggal_pelunasan)
                    $tanggalPelunasan = $termin->tanggal_pelunasan_dibayar ?: $termin->tanggal_pelunasan;
                    if ($termin->status_termin === 'Lunas' && $termin->nilai_pelunasan > 0 && $tanggalPelunasan) {
                        try {
                            $termin->recordIncome('pelunasan', $termin->nilai_pelunasan, $tanggalPelunasan);
                        } catch (\Exception $e) {
                            Log::error('[Termin] Gagal create income pelunasan (created hook)', ['termin_id' => $termin->id, 'error' => $e->getMessage()]);
                        }
                    }
                }

                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                Log::error('Error in Termin model created: ' . $e->getMessage());
            }
        });

        static::updated(function ($termin) {
            // Prevent infinite loop if already updating status
            if ($termin->isUpdatingStatus) {
                return;
            }

            try {
                $termin->clearCache();

                // If the status_termin was directly changed, update related records
                // This ensures consistency even if updateStatusFromPayments didn't run in `saving`
                if ($termin->isDirty('status_termin') || $termin->isDirty('status_approval')) {
                    $termin->createExpense(); // This will create or update the associated expense

                    // Record or update income based on the new status
                    if ($termin->status_termin === 'DP Dibayar') {
                        $termin->recordIncome('dp', $termin->nilai_dp, $termin->tanggal_dp_dibayar ?? now());
                        // If it was Lunas before and changed to DP Dibayar, you might need to adjust Pelunasan income
                    } elseif ($termin->status_termin === 'Lunas') {
                        // Ensure DP income is recorded if it wasn't before
                        if ($termin->nilai_dp > 0 && !$termin->incomes()->where('type', 'dp')->exists()) {
                             $termin->recordIncome('dp', $termin->nilai_dp, $termin->tanggal_dp_dibayar ?? now());
                        }
                        $termin->recordIncome('pelunasan', $termin->nilai_pelunasan, $termin->tanggal_pelunasan_dibayar ?? now());
                    } else { // Belum Dibayar or Rejected
                        // If status goes back to unpaid, remove incomes (if they exist)
                        $termin->incomes()->delete(); // DANGER: this will delete all incomes for this termin!
                                                     // Consider soft deleting or only deleting if status changes back
                                                     // from DP Dibayar or Lunas to Belum Dibayar.
                    }
                }

                // Always update invoice status and project status (cascading updates)
                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                    if ($termin->invoice->proyek) {
                        $termin->invoice->proyek->updateStatusFromInvoices();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error in Termin model updated: ' . $e->getMessage());
            }
        });

        static::deleted(function ($termin) {
            try {
                $termin->clearCache();
                // Delete associated expense and income records
                if ($termin->expense) {
                    $termin->expense->delete();
                }
                $termin->incomes()->delete(); // Delete all incomes for this termin

                if ($termin->bukti_pembayaran) {
                    Storage::delete($termin->bukti_pembayaran);
                }

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

            // Prepare common data for expense
            $expenseData = [
                'user_id' => auth()->id(),
                'proyek_id' => $this->proyek_id,
                'category_id' => null, // Assuming general expense or category might be handled by invoice
                'amount' => $this->nilai_termin, // Total nilai termin as initial expense
                'description' => "Pengeluaran Termin {$this->nama_termin}",
                'transaction_date' => $this->tanggal_dp ?? now(), // Use tanggal_dp or current date
                // Status expense akan Lunas jika termin DP Dibayar atau Lunas
                'status' => in_array($this->status_termin, ['DP Dibayar', 'Lunas']) ? 'Lunas' : 'pending',
                'payment_method_id' => $this->invoice->payment_method_id ?? null, // Get from invoice or default
                'prepared_fund' => $this->nilai_termin, // Total value of termin as prepared fund
                'source_type' => 'termin',
                'source_id' => $this->id,
                'invoice_id' => $this->invoice_id
            ];

            if (!$expense) {
                // Create new expense
                $expense = Expense::create($expenseData);
                $this->expense_id = $expense->id;
                $this->saveQuietly(); // Save expense_id without re-triggering hooks
            } else {
                // Update existing expense
                $expense->update($expenseData);
            }

            return $expense;
        });
    }

    // Method untuk mencatat income saat pembayaran diterima
    public function recordIncome($type, $amount, $tanggal)
    {
        return DB::transaction(function () use ($type, $amount, $tanggal) {
            // Helper to get default income category
            $getDefaultKategoriId = function() {
                $kategori = \App\Models\Kategori::where('jenis', 'pemasukan')->orderBy('id')->first();
                return $kategori ? $kategori->id : null;
            };

            // Helper to get default payment method
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
                Log::error('[Termin] Gagal create income: kategori atau payment method tidak ditemukan', [
                    'termin_id' => $this->id,
                    'kategori_id' => $kategoriId,
                    'payment_method_id' => $paymentMethodId,
                    'type' => $type,
                ]);
                throw new \Exception('Kategori pemasukan atau metode pembayaran tidak ditemukan');
            }

            // Check if income of this type for this termin already exists
            $existingIncome = \App\Models\Income::where('termin_id', $this->id)
                ->where('type', $type)
                ->where('status', 'Diterima') // Only consider received incomes
                ->first();

            $incomeData = [
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
            ];

            if (!$existingIncome) {
                try {
                    return \App\Models\Income::create($incomeData);
                } catch (\Exception $e) {
                    Log::error('[Termin] Gagal create income (recordIncome)', [
                        'termin_id' => $this->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                        'data' => $incomeData
                    ]);
                    throw $e;
                }
            } else {
                // If it exists, update it to ensure amount and date are current
                try {
                    $existingIncome->update($incomeData);
                } catch (\Exception $e) {
                    Log::error('[Termin] Gagal update income (recordIncome)', [
                        'termin_id' => $this->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                        'data' => $incomeData
                    ]);
                    throw $e;
                }
            }

            return $existingIncome;
        });
    }
}
