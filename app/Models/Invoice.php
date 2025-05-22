<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use App\Models\{Proyek, PurchaseMaterial, Termin, Expense, Income, PaymentMethod};

class Invoice extends Model
{
    use HasFactory;

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
        'pph_barang_amount',
        'grand_total',
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
        'pph_barang_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
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
        return $this->belongsTo(PaymentMethod::class);
    }

    // Status management
    public function determineStatus(): string
    {
        if ($this->amount_paid <= 0) {
            return self::STATUS_UNPAID;
        } elseif ($this->amount_paid < $this->total_amount) {
            return self::STATUS_PARTIALLY_PAID;
        }
        return self::STATUS_PAID;
    }

    // Setter amount_paid tanpa auto save, update status saja
    public function setAmountPaidAttribute($value)
    {
        $this->attributes['amount_paid'] = $value;
        // update status attribute in memory
        $this->attributes['status'] = $this->determineStatus();
    }
    public function updateAmountPaid($value)
    {
        $this->amount_paid = $value;
        $this->status = $this->determineStatus();
        $this->save();
    }


    public function updateStatusFromTermins(): void
    {
        try {
            $termins = $this->termins()->get();

            if ($termins->isEmpty()) {
                $this->status = self::STATUS_UNPAID;
            } else {
                $allLunas = $termins->every(fn($t) => $t->status_termin === 'Lunas');
                $allBelumDibayar = $termins->every(fn($t) => $t->status_termin === 'Belum Dibayar');
                $allDpDibayar = $termins->every(fn($t) => $t->status_termin === 'DP Dibayar');

                if ($allLunas) {
                    $this->status = self::STATUS_PAID;
                } elseif ($allBelumDibayar) {
                    $this->status = self::STATUS_UNPAID;
                } else {
                    // Termins mixed or partially paid cases
                    $this->status = self::STATUS_PARTIALLY_PAID;
                }
            }

            $this->save();

            if ($this->proyek) {
                $this->proyek->updateStatusFromInvoices();
            }
        } catch (\Exception $e) {
            Log::error('Error in updateStatusFromTermins: ' . $e->getMessage(), [
                'invoice_id' => $this->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', self::STATUS_PARTIALLY_PAID);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // Accessors
    public function getTotalTaxAttribute(): float
    {
        return ($this->pph_non_final_amount ?? 0) + ($this->pph_final_amount ?? 0) + ($this->ppn_amount ?? 0);
    }

    public function getTotalWithTaxAttribute(): float
    {
        return ($this->total_amount ?? 0) + $this->total_tax;
    }

    public function getTotalBarangAttribute(): float
    {
        return $this->purchaseMaterials()
            ->where('is_service', false)
            ->sum('total_harga');
    }

    public function getTotalJasaAttribute(): float
    {
        return $this->purchaseMaterials()
            ->where('is_service', true)
            ->sum('total_harga');
    }

    public function getTotalIncomeAttribute(): float
    {
        $netProfit = $this->net_profit ?? 0;
        $otherIncomes = Income::where('proyek_id', $this->proyek_id)
            ->whereNull('invoice_id')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        return $netProfit + $otherIncomes;
    }

    public function getTotalExpensesAttribute(): float
    {
        return Expense::where('proyek_id', $this->proyek_id)
            ->where('status', 'Lunas')
            ->sum('amount');
    }

    public function getProfitLossAttribute(): float
    {
        return $this->total_income - $this->total_expenses;
    }

    public function getProfitLossPercentageAttribute(): float
    {
        if (($this->total_expenses ?? 0) == 0) return 0;
        return ($this->profit_loss / $this->total_expenses) * 100;
    }

    public function getProjectIncomesAttribute()
    {
        return Income::where('proyek_id', $this->proyek_id)->get();
    }

    public function getTotalProjectIncomeAttribute(): float
    {
        $invoiceIncome = $this->total_income;
        $otherIncomes = Income::where('proyek_id', $this->proyek_id)
            ->whereNull('invoice_id')
            ->where('status', 'Diterima')
            ->sum('jumlah');

        return $invoiceIncome + $otherIncomes;
    }

    public function getTotalProjectExpensesAttribute(): float
    {
        return Expense::where('proyek_id', $this->proyek_id)
            ->where('status', 'Lunas')
            ->sum('amount');
    }

    public function getProjectProfitLossAttribute(): float
    {
        return $this->total_project_income - $this->total_project_expenses;
    }

    public function getProjectProfitLossPercentageAttribute(): float
    {
        if (($this->total_project_expenses ?? 0) == 0) return 0;
        return ($this->project_profit_loss / $this->total_project_expenses) * 100;
    }

    // Manual calculations
    public function calculateProfitLoss(): void
    {
        $this->total_income = $this->net_profit ?? 0;
        $this->total_expenses = $this->expenses()->where('status', 'Lunas')->sum('amount');
        $this->profit_loss = $this->total_income - $this->total_expenses;
        $this->profit_loss_percentage = $this->total_expenses > 0
            ? ($this->profit_loss / $this->total_expenses) * 100
            : 0;
        $this->save();
    }

    public function calculateTaxes(): void
    {
        // Subtotal barang & jasa
        $subtotalBarang = $this->purchaseMaterials()->where('is_service', false)->sum('total_harga');
        $subtotalJasa = $this->purchaseMaterials()->where('is_service', true)->sum('total_harga');

        // PPH Non Final: barang 1.5%, jasa 2%
        $this->pph_non_final_amount = $this->use_pph_non_final
            ? ($subtotalBarang * 0.015) + ($subtotalJasa * 0.02)
            : 0;

        // PPH Final: 22% dari laba bersih
        $this->pph_final_amount = $this->use_pph_final
            ? (($this->net_profit ?? 0) * 0.22)
            : 0;

        // PPN (11% dari total_amount)
        $this->ppn_amount = $this->use_ppn ? ($this->total_amount * 0.11) : 0;

        $this->pph_barang_amount = $subtotalBarang * 0.015;
        $this->pph_jasa_amount = $subtotalJasa * 0.02;

        $this->save();
    }

    public function calculateNetProfit(): void
    {
        // Net profit is based on total_amount multiplied by profit margin percentage
        $this->net_profit = ($this->total_amount ?? 0) * (($this->profit_margin_percentage ?? 30) / 100);
        $this->save();
    }

    public function calculateGrandTotal(): float
    {
        Log::info('Calculating Grand Total for Invoice ID: ' . $this->id);
        Log::info('  Initial total_amount: ' . $this->total_amount);
        Log::info('  use_ppn: ' . ($this->use_ppn ? 'true' : 'false'));
        Log::info('  use_pph_non_final: ' . ($this->use_pph_non_final ? 'true' : 'false'));
        Log::info('  use_pph_final: ' . ($this->use_pph_final ? 'true' : 'false'));
        Log::info('  ppn_amount: ' . $this->ppn_amount);
        Log::info('  pph_non_final_amount: ' . $this->pph_non_final_amount);
        Log::info('  pph_final_amount: ' . $this->pph_final_amount);

        $total = $this->total_amount ?? 0;

        // Add PPN if enabled
        if ($this->use_ppn) {
            $total += $this->ppn_amount;
        }

        // Subtract PPH Non Final (goods and services)
        $total -= $this->pph_non_final_amount;

        // Subtract PPH Final if enabled
        if ($this->use_pph_final) {
            $total -= $this->pph_final_amount;
        }

        $this->grand_total = $total;
        Log::info('  Calculated grand_total: ' . $this->grand_total);
        $this->save();

        return $this->grand_total;
    }

    // Add this method to calculate all financial values
    public function calculateAllFinancialValues(): void
    {
        $this->calculateNetProfit();
        $this->calculateTaxes();
        $this->calculateGrandTotal();
        $this->calculateProfitLoss();
    }

    public function getProjectFinancialSummary(): array
    {
        $termins = $this->termins;
        $totalTerminAmount = $termins->sum('nilai_termin');
        $totalPaidTermin = $termins->where('status_termin', 'Lunas')->sum('nilai_termin');
        $totalDPPaid = $termins->where('status_termin', 'DP Dibayar')->sum('nilai_dp');
        $totalExpenseAmount = $this->expenses->sum('amount');

        return [
            'total_income' => $this->total_income,
            'total_expenses' => $this->total_expenses,
            'profit_loss' => $this->profit_loss,
            'profit_loss_percentage' => $this->profit_loss_percentage,
            'total_termin_amount' => $totalTerminAmount,
            'total_paid_termin' => $totalPaidTermin,
            'total_dp_paid' => $totalDPPaid,
            'total_expense_amount' => $totalExpenseAmount,
            'remaining_payment' => $totalTerminAmount - $totalPaidTermin - $totalDPPaid,
        ];
    }

    // Kurangi anggaran proyek berdasarkan nilai termin
    public function reduceProjectBudget(float $terminValue): bool
    {
        $proyek = $this->proyek;

        if ($proyek->budget_adjusted !== null) {
            if ($proyek->budget_adjusted < $terminValue) {
                throw new \Exception('Anggaran proyek tidak mencukupi untuk nilai termin ini.');
            }
            $proyek->budget_adjusted -= $terminValue;
        } else {
            if ($proyek->anggaran_kontrak < $terminValue) {
                throw new \Exception('Anggaran proyek tidak mencukupi untuk nilai termin ini.');
            }
            $proyek->anggaran_kontrak -= $terminValue;
        }
        return $proyek->save();
    }
}
