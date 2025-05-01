<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'budget_category_id',
        'transaction_type',
        'amount',
        'description',
        'transaction_date',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    protected static function booted()
    {
        static::saved(function ($transaction) {
            if ($transaction->status === 'approved') {
                $transaction->category->updateAmounts();
                $transaction->budget->updateAmounts();
            }
        });
    }
} 