<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitLossReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'period_type',
        'start_date',
        'end_date',
        'total_income',
        'total_expense',
        'net_profit',
        'income_details',
        'expense_details'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'income_details' => 'array',
        'expense_details' => 'array'
    ];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }
}
