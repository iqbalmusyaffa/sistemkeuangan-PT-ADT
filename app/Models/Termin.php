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
        'target_progress',
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
        'target_progress' => 'integer',
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
        'approved_by_name',
        'project_progress' // Add project_progress to appends
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
                ->where('status_approval', 'approved')
                ->whereIn('status', ['DP Dibayar', 'Lunas', 'Diterima'])
                ->sum('jumlah') ?? 0;
        });
    }

    public function getTotalPelunasanPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_pelunasan_paid", 3600, function () {
            return $this->incomes()
                ->where('type', 'pelunasan')
                ->where('status_approval', 'approved')
                ->whereIn('status', ['Lunas', 'Diterima'])
                ->sum('jumlah') ?? 0;
        });
    }

    public function getTotalPaidAttribute()
    {
        return Cache::remember("termin_{$this->id}_total_paid", 3600, function () {
            return $this->incomes()
                ->where('status_approval', 'approved')
                ->whereIn('status', ['DP Dibayar', 'Lunas', 'Diterima'])
                ->sum('jumlah') ?? 0;
        });
    }

    public function getRemainingDpAttribute()
    {
        // Jika DP tidak ada, sisa DP = 0
        if (($this->nilai_dp ?? 0) == 0) {
            return 0;
        }
        // Jika sudah lunas, sisa DP = 0
        if ($this->status_termin === 'Lunas') {
            return 0;
        }
        // Jika DP sudah dibayar penuh, sisa DP = 0
        if ($this->total_dp_paid >= $this->nilai_dp) {
            return 0;
        }
        // Jika belum dibayar sama sekali, sisa DP = nilai DP
        if (($this->total_dp_paid ?? 0) == 0) {
            return $this->nilai_dp ?? 0;
        }
        // Jika dibayar sebagian, sisa DP = nilai DP - total DP paid
        return max(0, ($this->nilai_dp ?? 0) - ($this->total_dp_paid ?? 0));
    }

    public function getRemainingPelunasanAttribute()
    {
        // Jika pelunasan tidak ada, sisa pelunasan = 0
        if (($this->nilai_pelunasan ?? 0) == 0) {
            return 0;
        }
        // Jika sudah lunas, sisa pelunasan = 0
        if ($this->status_termin === 'Lunas') {
            return 0;
        }
        // Jika pelunasan sudah dibayar penuh, sisa pelunasan = 0
        if ($this->total_pelunasan_paid >= $this->nilai_pelunasan) {
            return 0;
        }
        // Jika belum dibayar sama sekali, sisa pelunasan = nilai pelunasan
        if (($this->total_pelunasan_paid ?? 0) == 0) {
            return $this->nilai_pelunasan ?? 0;
        }
        // Jika dibayar sebagian, sisa pelunasan = nilai pelunasan - total pelunasan paid
        return max(0, ($this->nilai_pelunasan ?? 0) - ($this->total_pelunasan_paid ?? 0));
    }

    public function getRemainingTotalAttribute()
    {
        // Jika sudah lunas, sisa termin = 0
        if ($this->status_termin === 'Lunas') {
            return 0;
        }
        // Jika belum dibayar sama sekali, sisa termin = nilai termin
        if (($this->total_paid ?? 0) == 0) {
            return $this->nilai_termin ?? 0;
        }
        // Jika dibayar sebagian, sisa termin = nilai termin - total paid
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

    // Accessor to get project progress
    public function getProjectProgressAttribute()
    {
        return $this->proyek->progress;
    }

    // UPDATE STATUS BERDASARKAN PEMBAYARAN
    public function updateStatusFromPayments($force = false)
    {
        // Hitung total pembayaran DP dan pelunasan
        $totalDpPaid = $this->total_dp_paid;
        $totalPelunasanPaid = $this->total_pelunasan_paid;

        $newStatus = 'Belum Dibayar';

        if ($this->nilai_termin == 0) {
            $newStatus = 'Belum Dibayar';
        } elseif (
            $totalDpPaid > 0 && $totalDpPaid < $this->nilai_dp
        ) {
            $newStatus = 'DP Sebagian';
        } elseif (
            $totalDpPaid >= $this->nilai_dp && $this->nilai_dp > 0 && $this->nilai_pelunasan == 0
        ) {
            $newStatus = 'DP Dibayar';
        } elseif (
            $totalDpPaid >= $this->nilai_dp && $totalPelunasanPaid > 0 && $totalPelunasanPaid < $this->nilai_pelunasan
        ) {
            $newStatus = 'Pelunasan Sebagian';
        } elseif (
            $totalDpPaid >= $this->nilai_dp && $totalPelunasanPaid >= $this->nilai_pelunasan && $this->nilai_pelunasan > 0
        ) {
            $newStatus = 'Lunas';
        }

        // Transparansi: Pastikan income DP SELALU ada jika status sudah DP Dibayar atau Lunas
        if (in_array($newStatus, ['DP Dibayar', 'Lunas'])) {
            // Cek income DP
            $dpIncomeExists = $this->incomes()->where('type', 'dp')->exists();
            if (!$dpIncomeExists && $this->nilai_dp > 0) {
                try {
                    $this->recordIncome('dp', $this->nilai_dp, $this->tanggal_dp ?? now(), 'Auto income DP (transparansi)');
                } catch (\Exception $e) {
                    \Log::error('[Termin] Auto-create income DP gagal: ' . $e->getMessage());
                }
            }
        }

        // Best practice: Setelah DP Dibayar, Pelunasan Sebagian, atau Lunas, SELALU buat income pelunasan (pending) jika belum ada, meskipun pelunasan belum dibayar
        if (in_array($newStatus, ['DP Dibayar', 'Pelunasan Sebagian', 'Lunas'])) {
            $pelunasanIncomeExists = $this->incomes()->where('type', 'pelunasan')->exists();
            if (!$pelunasanIncomeExists && $this->nilai_pelunasan > 0) {
                try {
                    // Buat income pelunasan dengan status pending, amount = nilai_pelunasan
                    $this->recordIncome('pelunasan', $this->nilai_pelunasan, $this->tanggal_pelunasan ?? now(), 'Auto income pelunasan (audit trail, DP sudah dibayar)');
                } catch (\Exception $e) {
                    \Log::error('[Termin] Auto-create income pelunasan (audit trail) gagal: ' . $e->getMessage());
                }
            }
            // Setelah DP Dibayar, Pelunasan Sebagian, atau Lunas, buat termin pelunasan jika belum ada (otomatis split termin)
            if (
                $this->jenis_termin === 'DP' &&
                in_array($newStatus, ['DP Dibayar', 'Pelunasan Sebagian', 'Lunas']) &&
                $this->nilai_pelunasan > 0
            ) {
                // Cek apakah sudah ada termin pelunasan untuk invoice/proyek ini
                $pelunasanTerminExists = self::where('invoice_id', $this->invoice_id)
                    ->where('jenis_termin', 'Pelunasan')
                    ->where('termin_ke', $this->termin_ke)
                    ->exists();

                if (!$pelunasanTerminExists) {
                    \Log::info('[Termin][DEBUG] Akan create termin pelunasan', [
                        'proyek_id' => $this->proyek_id,
                        'invoice_id' => $this->invoice_id,
                        'termin_ke' => $this->termin_ke,
                        'nilai_pelunasan' => $this->nilai_pelunasan,
                        'tanggal_pelunasan' => $this->tanggal_pelunasan,
                    ]);
                    try {
                        $pelunasanTermin = self::create([
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'nama_termin' => $this->nama_termin . ' - Pelunasan',
                            'jenis_termin' => 'Pelunasan',
                            'termin_ke' => $this->termin_ke,
                            'nilai_termin' => $this->nilai_pelunasan,
                            'persentase_dp' => 0,
                            'nilai_dp' => 0,
                            'nilai_pelunasan' => $this->nilai_pelunasan,
                            'tanggal_dp' => null,
                            'tanggal_pelunasan' => $this->tanggal_pelunasan ?? now(),
                            'tanggal_dp_dibayar' => null,
                            'tanggal_pelunasan_dibayar' => null,
                            'status_termin' => 'Belum Dibayar',
                            'keterangan' => 'Auto-create termin pelunasan setelah DP lunas',
                            'bukti_pembayaran' => null,
                            'dibayar_oleh' => null,
                            'status_approval' => 'pending',
                            'approved_by' => null,
                            'approved_at' => null,
                        ]);
                        \Log::info('[Termin] Auto-create termin pelunasan berhasil', [
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'nama_termin' => $this->nama_termin . ' - Pelunasan',
                            'termin_id' => $pelunasanTermin->id,
                        ]);
                        // Langsung buat expense untuk termin pelunasan
                        $pelunasanTermin->createExpense();
                    } catch (\Exception $e) {
                        \Log::error('[Termin] Auto-create termin pelunasan gagal: ' . $e->getMessage(), [
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'termin_ke' => $this->termin_ke,
                            'nilai_pelunasan' => $this->nilai_pelunasan,
                            'tanggal_pelunasan' => $this->tanggal_pelunasan,
                        ]);
                    }
                }
            }
        }

        // Update status jika berubah
        if ($this->status_termin !== $newStatus || $force) {
            $this->isUpdatingStatus = true;
            $this->status_termin = $newStatus;
            $this->saveQuietly();
            $this->isUpdatingStatus = false;

            // Jika status baru DP Dibayar, Pelunasan Sebagian, atau Lunas, dan jenis termin DP, pastikan termin pelunasan dibuat
            if (
                $this->jenis_termin === 'DP' &&
                in_array($newStatus, ['DP Dibayar', 'Pelunasan Sebagian', 'Lunas']) &&
                $this->nilai_pelunasan > 0
            ) {
                $pelunasanTerminExists = self::where('invoice_id', $this->invoice_id)
                    ->where('jenis_termin', 'Pelunasan')
                    ->where('termin_ke', $this->termin_ke)
                    ->exists();
                if (!$pelunasanTerminExists) {
                    try {
                        $pelunasanTermin = self::create([
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'nama_termin' => $this->nama_termin . ' - Pelunasan',
                            'jenis_termin' => 'Pelunasan',
                            'termin_ke' => $this->termin_ke,
                            'nilai_termin' => $this->nilai_pelunasan,
                            'persentase_dp' => 0,
                            'nilai_dp' => 0,
                            'nilai_pelunasan' => $this->nilai_pelunasan,
                            'tanggal_dp' => null,
                            'tanggal_pelunasan' => $this->tanggal_pelunasan ?? now(),
                            'tanggal_dp_dibayar' => null,
                            'tanggal_pelunasan_dibayar' => null,
                            'status_termin' => 'Belum Dibayar',
                            'keterangan' => 'Auto-create termin pelunasan setelah DP lunas',
                            'bukti_pembayaran' => null,
                            'dibayar_oleh' => null,
                            'status_approval' => 'pending',
                            'approved_by' => null,
                            'approved_at' => null,
                        ]);
                        \Log::info('[Termin] Auto-create termin pelunasan berhasil (status update)', [
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'nama_termin' => $this->nama_termin . ' - Pelunasan',
                            'termin_id' => $pelunasanTermin->id,
                        ]);
                        // Langsung buat expense untuk termin pelunasan
                        $pelunasanTermin->createExpense();
                    } catch (\Exception $e) {
                        \Log::error('[Termin] Auto-create termin pelunasan gagal (status update): ' . $e->getMessage(), [
                            'proyek_id' => $this->proyek_id,
                            'invoice_id' => $this->invoice_id,
                            'termin_ke' => $this->termin_ke,
                            'nilai_pelunasan' => $this->nilai_pelunasan,
                            'tanggal_pelunasan' => $this->tanggal_pelunasan,
                        ]);
                    }
                }
            }
        }
    }

    // New helper method to consolidate updates
    public function updateRelatedRecordsAfterStatusChange()
    {
        // Update all income related to this termin
        foreach ($this->incomes as $income) {
            $expectedIncomeStatus = 'pending';
            // Status income mengikuti status termin
            if ($this->status_termin === 'DP Sebagian') {
                $expectedIncomeStatus = 'DP Sebagian';
            } elseif ($this->status_termin === 'DP Dibayar') {
                $expectedIncomeStatus = 'DP Dibayar';
            } elseif ($this->status_termin === 'Pelunasan Sebagian') {
                $expectedIncomeStatus = 'Pelunasan Sebagian';
            } elseif ($this->status_termin === 'Lunas') {
                $expectedIncomeStatus = 'Lunas';
            } elseif ($this->status_termin === 'Belum Dibayar') {
                $expectedIncomeStatus = 'pending';
            }

            // Update status_approval income jika status termin sudah lunas/dibayar
            $expectedApprovalStatus = $income->status_approval;
            if (in_array($this->status_termin, [
                'DP Dibayar', 'Lunas', 'DP Sebagian', 'Pelunasan Sebagian'])) {
                if ($income->status_approval !== 'approved') {
                    $expectedApprovalStatus = 'approved';
                }
            } else {
                if ($income->status_approval !== 'pending') {
                    $expectedApprovalStatus = 'pending';
                }
            }

            $needSave = false;
            if ($income->status !== $expectedIncomeStatus) {
                Log::info('[Termin] Updating associated income status', [
                    'income_id' => $income->id,
                    'old' => $income->status,
                    'new' => $expectedIncomeStatus
                ]);
                $income->status = $expectedIncomeStatus;
                $needSave = true;
            }
            if ($income->status_approval !== $expectedApprovalStatus) {
                Log::info('[Termin] Updating associated income approval', [
                    'income_id' => $income->id,
                    'old' => $income->status_approval,
                    'new' => $expectedApprovalStatus
                ]);
                $income->status_approval = $expectedApprovalStatus;
                $needSave = true;
            }
            if ($needSave) {
                $income->saveQuietly();
            }
        }

        // Update or create expense, status & approval ikut status termin
        $expense = \App\Models\Expense::where('source_type', 'termin')->where('source_id', $this->id)->first();
        $expenseStatus = 'pending';
        if ($this->status_termin === 'DP Sebagian') {
            $expenseStatus = 'DP Sebagian';
        } elseif ($this->status_termin === 'DP Dibayar') {
            $expenseStatus = 'DP Dibayar';
        } elseif ($this->status_termin === 'Pelunasan Sebagian') {
            $expenseStatus = 'Pelunasan Sebagian';
        } elseif ($this->status_termin === 'Lunas') {
            $expenseStatus = 'Lunas';
        } elseif ($this->status_termin === 'Belum Dibayar') {
            $expenseStatus = 'pending';
        }
        $expenseData = [
            'user_id' => auth()->id() ?? $this->created_by,
            'proyek_id' => $this->proyek_id,
            'category_id' => null,
            'service_category_id' => null,
            'amount' => $this->nilai_termin,
            'description' => "Pengeluaran Termin {$this->nama_termin} untuk proyek " . optional($this->proyek)->nama_proyek,
            'transaction_date' => $this->tanggal_pelunasan_dibayar
                ?? $this->tanggal_dp_dibayar
                ?? $this->tanggal_pelunasan
                ?? $this->tanggal_dp
                ?? now(),
            'status' => $expenseStatus,
            'status_approval' => in_array($this->status_termin, [
                'Lunas', 'DP Dibayar', 'DP Sebagian', 'Pelunasan Sebagian']) ? 'approved' : 'pending',
            'source_type' => 'termin',
            'source_id' => $this->id,
            'prepared_fund' => $this->nilai_termin,
            'payment_method_id' => $this->invoice ? $this->invoice->payment_method_id : null,
            'invoice_id' => $this->invoice_id
        ];
        if ($expense) {
            $expense->update($expenseData);
        } else {
            $expense = \App\Models\Expense::create($expenseData);
            $this->expense_id = $expense->id;
            $this->saveQuietly();
        }
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

        static::updated(function ($termin) {
            if ($termin->isUpdatingStatus) {
                return;
            }
            try {
                $termin->clearCache();
                // Selalu update income/expense terkait
                $termin->updateRelatedRecordsAfterStatusChange();
                // Selalu update status invoice
                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                \Log::error('Error in Termin model updated: ' . $e->getMessage());
            }
        });

        static::created(function ($termin) {
            try {
                $termin->clearCache();
                $termin->updateRelatedRecordsAfterStatusChange();
                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                \Log::error('Error in Termin model created: ' . $e->getMessage());
            }
        });

        static::deleting(function ($termin) {
            // Pastikan relasi incomes dan expense ikut terhapus (cascade)
            try {
                $termin->clearCache();
                // Hapus semua income terkait
                foreach ($termin->incomes as $income) {
                    $income->delete();
                }
                // Hapus expense terkait jika ada
                if ($termin->expense) {
                    $termin->expense->delete();
                }
                // Hapus file bukti jika ada
                if ($termin->bukti_pembayaran) {
                    \Storage::delete($termin->bukti_pembayaran);
                }
            } catch (\Exception $e) {
                \Log::error('Error in Termin model deleting: ' . $e->getMessage());
            }
        });
        static::deleted(function ($termin) {
            try {
                // Update status invoice setelah termin dihapus
                if ($termin->invoice) {
                    $termin->invoice->updateStatusFromTermins();
                }
            } catch (\Exception $e) {
                \Log::error('Error in Termin model deleted: ' . $e->getMessage());
            }
        });
    }
    protected static function booted()
    {
        parent::boot();

        static::creating(function ($termin) {
            try {
                Log::info('Creating new termin:', $termin->toArray());

                // Set nilai DP and pelunasan if not set
                if (is_null($termin->nilai_dp) && $termin->persentase_dp) {
                    $termin->nilai_dp = round($termin->nilai_termin * ($termin->persentase_dp / 100), 2);
                }
                if (is_null($termin->nilai_pelunasan)) {
                    $termin->nilai_pelunasan = round($termin->nilai_termin - ($termin->nilai_dp ?? 0), 2);
                }

                // Handle bukti pembayaran if it's an UploadedFile
                if ($termin->bukti_pembayaran instanceof \Illuminate\Http\UploadedFile) {
                    $file = $termin->bukti_pembayaran;
                    if ($file->getSize() > 2048 * 1024) { // 2MB in bytes
                        throw new \Exception('File size exceeds 2MB limit');
                    }
                    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        throw new \Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed');
                    }
                    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
                    $path = 'uploads/bukti_pembayaran/' . $filename;
                    if (!Storage::exists('uploads/bukti_pembayaran')) {
                        Storage::makeDirectory('uploads/bukti_pembayaran');
                    }
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
        static::saved(function ($termin) {
            if ($termin->proyek) {
                $termin->proyek->updateProgressFromTermins();
            }
        });

        static::created(function ($termin) {
            try {
                $termin->clearCache();
                $termin->createExpense(); // Create expense immediately

                // Call updateStatusFromPayments after creation
                $termin->isUpdatingStatus = true;
                $termin->updateStatusFromPayments();
                $termin->isUpdatingStatus = false;

                // The updateStatusFromPayments method will handle income creation/update
                // if the status dictates it. No need to duplicate here.

            } catch (\Exception $e) {
                Log::error('Error in Termin model created: ' . $e->getMessage());
            }
        });

        static::updated(function ($termin) {
            if ($termin->isUpdatingStatus) {
                return;
            }

            try {
                $termin->clearCache();
                // Tidak lagi memanggil updateStatusFromPayments() otomatis di sini.
                // Sinkronisasi status hanya terjadi saat approval (approveIncome/approveExpense).

                // Update status invoice otomatis (jika memang ingin invoice tetap update saat termin berubah)
                if ($termin->invoice) {
                    $invoice = $termin->invoice;
                    $termins = $invoice->termins()->where('nilai_termin', '>', 0)->get();
                    $total = $termins->count();
                    $lunas = $termins->where('status_termin', 'Lunas')->count();
                    $dpdibayar = $termins->where('status_termin', 'DP Dibayar')->count();
                    $totalNilaiPelunasan = $termins->sum('nilai_pelunasan');
                    $totalPaid = $termins->sum(function($t) {
                        // Total uang yang benar-benar diterima (DP + pelunasan)
                        return ($t->total_dp_paid ?? 0) + ($t->total_pelunasan_paid ?? 0);
                    });

                    if ($totalPaid >= $invoice->total_amount && $invoice->total_amount > 0) {
                        // Sudah terima uang 100% dari seluruh termin
                        $invoice->status = 'paid';
                        $invoice->amount_paid = $invoice->total_amount;
                    } else if ($dpdibayar > 0 || $lunas > 0) {
                        // Ada termin sudah DP atau Lunas, tapi belum semua uang diterima
                        $invoice->status = 'partially_paid';
                        $invoice->amount_paid = $totalPaid;
                    } else {
                        $invoice->status = 'unpaid';
                        $invoice->amount_paid = 0;
                    }
                    $invoice->save();
                    Log::info('[Termin] Update status invoice otomatis', [
                        'invoice_id' => $invoice->id,
                        'status' => $invoice->status,
                        'amount_paid' => $invoice->amount_paid,
                    ]);
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
        // Clear related cache
        if ($this->invoice) {
            Cache::forget("invoice_{$this->invoice->id}_status");
        }
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
                'transaction_date' => $this->tanggal_pelunasan_dibayar
                    ?? $this->tanggal_dp_dibayar
                    ?? $this->tanggal_pelunasan
                    ?? $this->tanggal_dp
                    ?? now(),
                // Status expense hanya 'pending' atau 'Lunas'
                'status' => ($this->status_termin === 'Lunas' || $this->status_termin === 'DP Dibayar') ? 'Lunas' : 'pending',
                'payment_method_id' => $this->invoice->payment_method_id ?? null, // Get from invoice or default
                'prepared_fund' => $this->nilai_termin, // Total value of termin as prepared fund
                'source_type' => 'termin',
                'source_id' => $this->id,
                'invoice_id' => $this->invoice_id
            ];

            Log::info('[Termin] createExpense called', [
                'termin_id' => $this->id,
                'status_termin' => $this->status_termin,
                'existing_expense_id' => $expense ? $expense->id : null,
                'expense_status_to_set' => $expenseData['status'],
                'expenseData' => $expenseData,
            ]);

            if (!$expense) {
                // Create new expense
                $expense = Expense::create($expenseData);
                $this->expense_id = $expense->id;
                $this->saveQuietly(); // Save expense_id without re-triggering hooks
                Log::info('[Termin] createExpense created new expense', [
                    'termin_id' => $this->id,
                    'expense_id' => $expense->id,
                    'expense_status' => $expense->status,
                ]);
            } else {
                // Update existing expense
                $oldStatus = $expense->status;
                $expense->update($expenseData);
                Log::info('[Termin] createExpense updated existing expense', [
                    'termin_id' => $this->id,
                    'expense_id' => $expense->id,
                    'old_status' => $oldStatus,
                    'new_status' => $expense->status,
                ]);
            }

            // Extra error logging if status is not as expected
            if (!in_array($expense->status, ['Lunas', 'pending'])) {
                Log::error('[Termin] createExpense: Unexpected expense status after save', [
                    'termin_id' => $this->id,
                    'expense_id' => $expense->id,
                    'expense_status' => $expense->status,
                ]);
            }

            return $expense;
        });
    }

    // Method untuk mencatat income SETIAP pembayaran diterima (selalu buat baris baru, mendukung upload bukti & approval admin)
    public function recordIncome($type, $amount, $tanggal, $keterangan = null, $buktiFile = null)
    {
        return DB::transaction(function () use ($type, $amount, $tanggal, $keterangan, $buktiFile) {
            // Cegah pembayaran jika termin sudah lunas
            if ($this->status_termin === 'Lunas') {
                throw new \Exception('Termin sudah Lunas, tidak bisa menerima pembayaran baru.');
            }
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

            // Pastikan invoice sudah di-load (eager load lebih baik di controller/service)
            $invoice = $this->invoice ?? $this->invoice()->first();
            $kategoriId = $invoice && $invoice->kategori_id
                ? $invoice->kategori_id
                : $getDefaultKategoriId();

            $paymentMethodId = $invoice && $invoice->payment_method_id
                ? $invoice->payment_method_id
                : $getDefaultPaymentMethodId();

            // Validasi amount tidak boleh melebihi sisa DP/pelunasan
            if ($type === 'dp' && $amount > $this->remaining_dp) {
                throw new \Exception('Jumlah DP melebihi sisa DP termin');
            }
            if ($type === 'pelunasan' && $amount > $this->remaining_pelunasan) {
                throw new \Exception('Jumlah pelunasan melebihi sisa pelunasan termin');
            }

            if (!$kategoriId || !$paymentMethodId) {
                Log::error('[Termin] Gagal create income: kategori atau payment method tidak ditemukan', [
                    'termin_id' => $this->id,
                    'kategori_id' => $kategoriId,
                    'payment_method_id' => $paymentMethodId,
                    'type' => $type,
                ]);
                throw new \Exception('Kategori pemasukan atau metode pembayaran tidak ditemukan');
            }


            // Status income dan approval: selalu pending setelah upload bukti
            $incomeStatus = 'pending';
            $incomeApproval = 'pending';
            $buktiPath = null;

            // Handle upload file bukti jika ada
            if ($buktiFile instanceof \Illuminate\Http\UploadedFile) {
                if ($buktiFile->getSize() > 2048 * 1024) {
                    throw new \Exception('File size exceeds 2MB limit');
                }
                $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!in_array($buktiFile->getMimeType(), $allowedTypes)) {
                    throw new \Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed');
                }
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $buktiFile->getClientOriginalName());
                $path = 'uploads/bukti_pembayaran/' . $filename;
                if (!Storage::exists('uploads/bukti_pembayaran')) {
                    Storage::makeDirectory('uploads/bukti_pembayaran');
                }
                if (!$buktiFile->storeAs('uploads/bukti_pembayaran', $filename)) {
                    throw new \Exception('Failed to store file');
                }
                $buktiPath = $path;
            }

            $incomeData = [
                'jumlah' => $amount,
                'status' => $incomeStatus,
                'status_approval' => $incomeApproval,
                'bukti' => $buktiPath,
                'type' => $type,
                'termin_id' => $this->id,
                'proyek_id' => $this->proyek_id,
                'deskripsi' => $keterangan ?? "Pembayaran {$type} Termin {$this->nama_termin}",
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'invoice_id' => $this->invoice_id,
                'kategori_id' => $kategoriId,
                'payment_method_id' => $paymentMethodId,
                'tanggal' => $tanggal
            ];

            try {
                $income = \App\Models\Income::create($incomeData);
                Log::info('[Termin] recordIncome: Income created', [
                    'termin_id' => $this->id,
                    'income_id' => $income->id,
                    'type' => $type,
                    'amount' => $amount,
                    'tanggal' => $tanggal
                ]);
                return $income;
            } catch (\Exception $e) {
                Log::error('[Termin] Gagal create income (recordIncome)', [
                    'termin_id' => $this->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'data' => $incomeData
                ]);
                throw $e;
            }
        });
    }

    // Method untuk mencatat expense SETIAP pencairan dana termin (selalu buat baris baru, mendukung upload bukti & approval admin)
    public function recordExpense($type, $amount, $tanggal, $keterangan = null, $buktiFile = null)
    {
        return DB::transaction(function () use ($type, $amount, $tanggal, $keterangan, $buktiFile) {
            $expenseStatus = 'pending';
            $expenseApproval = 'pending';
            $buktiPath = null;

            // Handle upload file bukti jika ada
            if ($buktiFile instanceof \Illuminate\Http\UploadedFile) {
                if ($buktiFile->getSize() > 2048 * 1024) {
                    throw new \Exception('File size exceeds 2MB limit');
                }
                $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!in_array($buktiFile->getMimeType(), $allowedTypes)) {
                    throw new \Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed');
                }
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $buktiFile->getClientOriginalName());
                $path = 'uploads/bukti_pembayaran/' . $filename;
                if (!Storage::exists('uploads/bukti_pembayaran')) {
                    Storage::makeDirectory('uploads/bukti_pembayaran');
                }
                if (!$buktiFile->storeAs('uploads/bukti_pembayaran', $filename)) {
                    throw new \Exception('Failed to store file');
                }
                $buktiPath = $path;
            }

            $expenseData = [
                'user_id' => auth()->id() ?? $this->created_by,
                'proyek_id' => $this->proyek_id,
                'category_id' => null,
                'service_category_id' => null,
                'amount' => $amount,
                'description' => $keterangan ?? "Pengeluaran {$type} Termin {$this->nama_termin}",
                'transaction_date' => $tanggal,
                'status' => $expenseStatus,
                'status_approval' => $expenseApproval,
                'bukti' => $buktiPath,
                'payment_method_id' => $this->invoice ? $this->invoice->payment_method_id : null,
                'prepared_fund' => $amount,
                'source_type' => 'termin',
                'source_id' => $this->id,
                'invoice_id' => $this->invoice_id
            ];
            try {
                $expense = \App\Models\Expense::create($expenseData);
                Log::info('[Termin] recordExpense: Expense created', [
                    'termin_id' => $this->id,
                    'expense_id' => $expense->id,
                    'type' => $type,
                    'amount' => $amount,
                    'tanggal' => $tanggal
                ]);
                return $expense;
            } catch (\Exception $e) {
                Log::error('[Termin] Gagal create expense (recordExpense)', [
                    'termin_id' => $this->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'data' => $expenseData
                ]);
                throw $e;
            }
        });
    }

    // Method untuk approval admin pada income (setelah admin cek bukti)
    public function approveIncome($incomeId)
    {
        return \DB::transaction(function () use ($incomeId) {
            $income = \App\Models\Income::findOrFail($incomeId);
            if ($income->status_approval !== 'pending') {
                throw new \Exception('Income sudah di-approve atau ditolak');
            }
            $income->status_approval = 'approved';
            $income->approved_by = auth()->id(); // Catat siapa yang approve
            $income->approved_at = now();        // Catat waktu approval
            // Ubah status sesuai jenis pembayaran termin
            if ($income->type === 'dp') {
                $income->status = 'DP Dibayar';
            } elseif ($income->type === 'pelunasan') {
                $income->status = 'Lunas';
            } else {
                $income->status = 'Diterima';
            }
            $income->save();
            // Setelah approval, update status termin dan sinkronisasi
            $this->clearCache();
            $this->updateStatusFromPayments(true);
            $this->updateRelatedRecordsAfterStatusChange();
            if ($this->invoice) {
                $this->invoice->updateStatusFromTermins();
            }
            return $income;
        });
    }

    // Method untuk approval admin pada expense (setelah admin cek bukti)
    public function approveExpense($expenseId)
    {
        return \DB::transaction(function () use ($expenseId) {
            $expense = \App\Models\Expense::findOrFail($expenseId);
            if ($expense->status_approval !== 'pending') {
                throw new \Exception('Expense sudah di-approve atau ditolak');
            }
            $expense->status_approval = 'approved';
            $expense->approved_by = auth()->id(); // Catat siapa yang approve
            $expense->approved_at = now();        // Catat waktu approval
            // Ubah status sesuai jenis pembayaran termin
            if ($expense->type === 'dp') {
                $expense->status = 'DP Dibayar';
            } elseif ($expense->type === 'pelunasan') {
                $expense->status = 'Lunas';
            } else {
                $expense->status = 'Lunas';
            }
            $expense->save();
            // Setelah approval, update status termin dan sinkronisasi
            $this->clearCache();
            $this->updateStatusFromPayments(true);
            $this->updateRelatedRecordsAfterStatusChange();
            if ($this->invoice) {
                $this->invoice->updateStatusFromTermins();
            }
            return $expense;
});
    }
  public function approve()
    {
        return \DB::transaction(function () {
            // Cek jika termin sudah pernah disetujui
            if ($this->status_approval === 'approved') {
                throw new \Exception('Termin ini sudah pernah disetujui sebelumnya.');
            }

            // Langkah 1: Setujui Termin
            $this->status_approval = 'approved';
            $this->approved_by = auth()->id();
            $this->approved_at = now();
            Log::info("[Termin Approve] Menyetujui Termin ID: {$this->id}");

            // Langkah 2: Setujui Expense terkait
            $expense = $this->expense; // Menggunakan relasi
            if ($expense) {
                $expense->status_approval = 'approved';
                $expense->approved_by = auth()->id();
                $expense->approved_at = now();

                // Status 'lunas' atau 'pending' pada expense akan di-handle oleh
                // method updateRelatedRecordsAfterStatusChange() saat ada pembayaran (income) yang masuk dan di-approve.
                // Jadi di sini kita hanya approve status persetujuannya.
                $expense->save();
                Log::info("[Termin Approve] Menyetujui Expense ID: {$expense->id} yang terhubung dengan Termin ID: {$this->id}");
            } else {
                 Log::warning("[Termin Approve] Tidak ditemukan expense yang terhubung dengan Termin ID: {$this->id}. Ini seharusnya tidak terjadi jika termin dibuat dengan benar.");
            }

            $this->save(); // Simpan perubahan pada termin
            $this->clearCache(); // Hapus cache terkait

            return $this;
        });
    }
    /**
     * Approve this Termin and automatically approve all related incomes (and optionally expenses).
     * This allows approval to be done from Termin only, syncing all statuses.
     *
     * @param bool $approveExpenses If true, also approve related expenses (default: false)
     * @return void
     */
    public function approveAllRelated($approveExpenses = false)
    {
        \DB::transaction(function () use ($approveExpenses) {
            // Approve this termin if not already
            if ($this->status_approval !== 'approved') {
                $this->status_approval = 'approved';
                $this->approved_by = auth()->id();
                $this->approved_at = now();
                $this->save();
            }

            // Buat income sesuai jenis termin
            if ($this->jenis_termin === 'DP' && $this->nilai_dp > 0 && !$this->incomes()->where('type', 'dp')->exists()) {
                $this->recordIncome('dp', $this->nilai_dp, $this->tanggal_dp ?? now(), 'Auto income DP by Termin approval');
            } elseif ($this->jenis_termin === 'Pelunasan' && $this->nilai_pelunasan > 0 && !$this->incomes()->where('type', 'pelunasan')->exists()) {
                $this->recordIncome('pelunasan', $this->nilai_pelunasan, $this->tanggal_pelunasan ?? now(), 'Auto income pelunasan by Termin approval');
            } elseif ($this->jenis_termin === 'Termin Bertahap') {
                // Untuk termin bertahap, buat income dp/pelunasan jika belum ada
                if ($this->nilai_dp > 0 && !$this->incomes()->where('type', 'dp')->exists()) {
                    $this->recordIncome('dp', $this->nilai_dp, $this->tanggal_dp ?? now(), 'Auto income DP by Termin approval');
                }
                if ($this->nilai_pelunasan > 0 && !$this->incomes()->where('type', 'pelunasan')->exists()) {
                    $this->recordIncome('pelunasan', $this->nilai_pelunasan, $this->tanggal_pelunasan ?? now(), 'Auto income pelunasan by Termin approval');
                }
            }

            // Approve all related incomes that are still pending
            foreach ($this->incomes as $income) {
                if ($income->status_approval === 'pending') {
                    $income->status_approval = 'approved';
                    $income->approved_by = auth()->id();
                    $income->approved_at = now();
                    // Set status sesuai tipe
                    if ($income->type === 'dp') {
                        $income->status = 'DP Dibayar';
                    } elseif ($income->type === 'pelunasan') {
                        $income->status = 'Lunas';
                    } else {
                        $income->status = 'Diterima';
                    }
                    $income->save();
                }
            }

            // Optionally approve related expenses
            if ($approveExpenses) {
                $expenses = \App\Models\Expense::where('source_type', 'termin')->where('source_id', $this->id)->get();
                foreach ($expenses as $expense) {
                    if ($expense->status_approval === 'pending') {
                        $expense->status_approval = 'approved';
                        $expense->approved_by = auth()->id();
                        $expense->approved_at = now();
                        // Set status sesuai tipe
                        if ($expense->type === 'dp') {
                            $expense->status = 'DP Dibayar';
                        } elseif ($expense->type === 'pelunasan') {
                            $expense->status = 'Lunas';
                        } else {
                            $expense->status = 'Lunas';
                        }
                        $expense->save();
                    }
                }
            }

            // Sync all statuses
            $this->clearCache();
            $this->updateStatusFromPayments(true);
            $this->updateRelatedRecordsAfterStatusChange();
            if ($this->invoice) {
                $this->invoice->updateStatusFromTermins();
            }
            if ($this->proyek) {
    $this->proyek->updateProgressFromTermins(); // <-- ini yang Anda tambahkan
}
        });
    }
}
