<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Invoice extends Model
{
    use HasFactory;

    // Tambahkan constant status
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'proyek_id',
        'payment_method_id',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'amount_paid',
        'pph_non_final_amount',
        'pph_final_amount',
        'ppn_amount',
        'net_profit',
        'profit_margin_percentage',
        'total_income',
        'total_expenses',
        'profit_loss',
        'profit_loss_percentage',
        'use_ppn',
        'use_pph_non_final',
        'use_pph_final',
        'notes',
        'status',
        'pph_jasa_amount',
        'pph_barang_amount'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'pph_non_final_amount' => 'decimal:2',
        'pph_final_amount' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'profit_margin_percentage' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'profit_loss_percentage' => 'decimal:2',
        'use_ppn' => 'boolean',
        'use_pph_non_final' => 'boolean',
        'use_pph_final' => 'boolean',
        'invoice_date' => 'date',
        'status' => 'string',
        'pph_jasa_amount' => 'decimal:2',
        'pph_barang_amount' => 'decimal:2'
    ];

    // Relationships
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class, 'invoice_id');
    }

    public function termins()
    {
        return $this->hasMany(Termin::class, 'invoice_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'invoice_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class);
    }

    // Status Management
    public function determineStatus()
    {
        if ($this->amount_paid == 0) {
            return 'unpaid';
        } elseif ($this->amount_paid < $this->total_amount) {
            return 'partially_paid';
        } elseif ($this->amount_paid >= $this->total_amount) {
            return 'paid';
        } else {
            return 'unpaid';
        }
    }

    public function setAmountPaidAttribute($value)
    {
        $this->attributes['amount_paid'] = $value;
        $this->attributes['status'] = $this->determineStatus();
    }

    public function updateStatusFromTermins()
    {
        $termins = $this->termins;
        if ($termins->count() === 0) {
            $this->status = 'unpaid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'Lunas')) {
            $this->status = 'paid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'Belum Dibayar')) {
            $this->status = 'unpaid';
        } elseif ($termins->every(fn($t) => $t->status_termin === 'DP Dibayar')) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'partially_paid';
        }
        $this->save();
    }

    // Query Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partially_paid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getTotalTaxAttribute()
    {
        return $this->pph_non_final_amount + $this->pph_final_amount + $this->ppn_amount;
    }

    public function getTotalWithTaxAttribute()
    {
        return $this->total_amount + $this->total_tax;
    }

    public function getTotalBarangAttribute()
    {
        return $this->purchaseMaterials()
            ->where('is_service', false)
            ->sum('total_harga');
    }

    public function getTotalJasaAttribute()
    {
        return $this->purchaseMaterials()
            ->where('is_service', true)
            ->sum('total_harga');
    }

    // Realtime: Total income (laba bersih invoice + income lain yang diterima)
    public function getTotalIncomeAttribute()
    {
        $netProfit = $this->net_profit;
        $otherIncomes = \App\Models\Income::where('proyek_id', $this->proyek_id)
            ->whereNull('invoice_id')
            ->where('status', 'Diterima')
            ->sum('jumlah');
        return $netProfit + $otherIncomes;
    }

    // Realtime: Total pengeluaran (semua expense yang statusnya Lunas)
    public function getTotalExpensesAttribute()
    {
        return \App\Models\Expense::where('proyek_id', $this->proyek_id)
            ->where('status', 'Lunas')
            ->sum('amount');
    }

    // Realtime: Laba/rugi
    public function getProfitLossAttribute()
    {
        return $this->total_income - $this->total_expenses;
    }

    // Realtime: Persentase laba/rugi
    public function getProfitLossPercentageAttribute()
    {
        if ($this->total_expenses == 0) return 0;
        return ($this->profit_loss / $this->total_expenses) * 100;
    }

    // Get all incomes related to this project
    public function getProjectIncomesAttribute()
    {
        return Income::where('proyek_id', $this->proyek_id)->get();
    }

    // Get total income from all sources for this project
    public function getTotalProjectIncomeAttribute()
    {
        $invoiceIncome = $this->total_income;
        $otherIncomes = Income::where('proyek_id', $this->proyek_id)
            ->whereNull('invoice_id')
            ->sum('jumlah');
        return $invoiceIncome + $otherIncomes;
    }

    // Get total expenses for this project
    public function getTotalProjectExpensesAttribute()
    {
        return Expense::where('proyek_id', $this->proyek_id)->sum('amount');
    }

    // Calculate overall project profit/loss
    public function getProjectProfitLossAttribute()
    {
        return $this->total_project_income - $this->total_project_expenses;
    }

    // Calculate overall project profit/loss percentage
    public function getProjectProfitLossPercentageAttribute()
    {
        if ($this->total_project_expenses == 0) return 0;
        return ($this->project_profit_loss / $this->total_project_expenses) * 100;
    }

    // Calculate and update profit/loss
    public function calculateProfitLoss()
    {
        // Calculate total income (net profit from invoice)
        $this->total_income = $this->net_profit;

        // Calculate total expenses
        $this->total_expenses = $this->expenses()->sum('amount');

        // Calculate profit/loss
        $this->profit_loss = $this->total_income - $this->total_expenses;

        // Calculate profit/loss percentage
        $this->profit_loss_percentage = $this->total_expenses > 0
            ? ($this->profit_loss / $this->total_expenses) * 100
            : 0;

        $this->save();
    }

    // Calculate net profit based on profit margin
    public function calculateNetProfit()
    {
        $this->net_profit = $this->total_amount * ($this->profit_margin_percentage / 100);
        $this->save();
    }

    // Calculate project financial summary
    public function getProjectFinancialSummary()
    {
        $totalIncome = $this->total_income;
        $totalExpenses = $this->total_expenses;
        $profitLoss = $this->profit_loss;
        $profitLossPercentage = $this->profit_loss_percentage;

        // Get all termins for this project
        $termins = $this->termins;
        $totalTerminAmount = $termins->sum('nilai_termin');
        $totalPaidTermin = $termins->where('status_termin', 'Lunas')->sum('nilai_termin');
        $totalDPPaid = $termins->where('status_termin', 'DP Dibayar')->sum('nilai_dp');

        // Get all expenses for this project
        $expenses = $this->expenses;
        $totalExpenseAmount = $expenses->sum('amount');

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit_loss' => $profitLoss,
            'profit_loss_percentage' => $profitLossPercentage,
            'total_termin_amount' => $totalTerminAmount,
            'total_paid_termin' => $totalPaidTermin,
            'total_dp_paid' => $totalDPPaid,
            'total_expense_amount' => $totalExpenseAmount,
            'remaining_payment' => $totalTerminAmount - $totalPaidTermin - $totalDPPaid
        ];
    }

    // Update payment status and create expense record
    public function recordPayment($amount, $paymentMethodId = null)
    {
        DB::beginTransaction();
        try {
            // Update invoice payment
            $this->amount_paid += $amount;
            $this->status = $this->determineStatus();
            if ($paymentMethodId) {
                $this->payment_method_id = $paymentMethodId;
            }
            $this->save();

            // Create expense record for the payment
            $expense = Expense::create([
                'user_id' => auth()->id(),
                'proyek_id' => $this->proyek_id,
                'invoice_id' => $this->id,
                'amount' => $amount,
                'description' => "Pembayaran Invoice {$this->invoice_number}",
                'transaction_date' => now(),
                'status' => 'approved',
                'payment_method' => $paymentMethodId ? PaymentMethod::find($paymentMethodId)->name : 'Cash',
                'source_type' => 'invoice',
                'source_id' => $this->id
            ]);

            DB::commit();
            return $expense;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Calculate profit/loss for the project
    public function calculateProjectProfitLoss()
    {
        $totalIncome = $this->total_income;
        $totalExpenses = $this->total_expenses;
        $profitLoss = $totalIncome - $totalExpenses;
        $profitLossPercentage = $totalExpenses > 0 ? ($profitLoss / $totalExpenses) * 100 : 0;

        $this->profit_loss = $profitLoss;
        $this->profit_loss_percentage = $profitLossPercentage;
        $this->save();

        return [
            'profit_loss' => $profitLoss,
            'profit_loss_percentage' => $profitLossPercentage
        ];
    }

    // Get payment status summary
    public function getPaymentStatusSummary()
    {
        $termins = $this->termins;
        $totalTerminAmount = $termins->sum('nilai_termin');
        $totalPaidTermin = $termins->where('status_termin', 'Lunas')->sum('nilai_termin');
        $totalDPPaid = $termins->where('status_termin', 'DP Dibayar')->sum('nilai_dp');
        $remainingPayment = $totalTerminAmount - $totalPaidTermin - $totalDPPaid;

        return [
            'total_amount' => $totalTerminAmount,
            'total_paid' => $totalPaidTermin + $totalDPPaid,
            'remaining_payment' => $remainingPayment,
            'payment_status' => $this->status,
            'termins' => $termins->map(function($termin) {
                return [
                    'nama_termin' => $termin->nama_termin,
                    'nilai_termin' => $termin->nilai_termin,
                    'status' => $termin->status_termin,
                    'tanggal_dp' => $termin->tanggal_dp,
                    'tanggal_pelunasan' => $termin->tanggal_pelunasan
                ];
            })
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($invoice) {
            // Cek perubahan status
            if ($invoice->wasChanged('status')) {
                if ($invoice->status === self::STATUS_PAID) {
                    // Cek income sudah ada?
                    if (!\App\Models\Income::where('invoice_id', $invoice->id)->exists()) {
                        app(\App\Http\Controllers\Api\IncomeController::class)->createFromInvoice($invoice);
                    }
                } elseif ($invoice->status === self::STATUS_UNPAID) {
                    if (!\App\Models\Expense::where('invoice_id', $invoice->id)->exists()) {
                        app(\App\Http\Controllers\Api\ExpenseController::class)->createFromInvoice($invoice);
                    }
                }
            }
        });
    }
}

    // Realtime: Summary laba/rugi proyek
//     public function getProjectFinancialSummary()
//     {
//         $totalProjectIncome = \App\Models\Income::where('proyek_id', $this->proyek_id)
//             ->where('status', 'Diterima')
//             ->sum('jumlah');
//         $totalProjectExpenses = \App\Models\Expense::where('proyek_id', $this->proyek_id)
//             ->where('status', 'Lunas')
//             ->sum('amount');
//         $projectProfitLoss = $totalProjectIncome - $totalProjectExpenses;
//         $projectProfitLossPercentage = $totalProjectExpenses > 0
//             ? ($projectProfitLoss / $totalProjectExpenses) * 100
//             : 0;
//         return [
//             'total_project_income' => $totalProjectIncome,
//             'total_project_expenses' => $totalProjectExpenses,
//             'project_profit_loss' => $projectProfitLoss,
//             'project_profit_loss_percentage' => $projectProfitLossPercentage
//         ];
//     }

//     // Override the save method to ensure calculations are always up to date
//     public function save(array $options = [])
//     {
//         // Calculate net profit if total amount or profit margin changes
//         if ($this->isDirty(['total_amount', 'profit_margin_percentage'])) {
//             $this->calculateNetProfit();
//         }

//         // Calculate profit/loss if relevant fields change
//         if ($this->isDirty(['net_profit', 'total_amount', 'profit_margin_percentage'])) {
//             $this->calculateProfitLoss();
//         }

//         return parent::save($options);
//     }
// }
