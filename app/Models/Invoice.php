<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

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
        'status'
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
        'status' => 'string'
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
