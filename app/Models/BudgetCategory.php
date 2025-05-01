<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'name',
        'description',
        'allocated_amount',
        'used_amount',
        'remaining_amount'
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    public function updateAmounts()
    {
        $this->used_amount = $this->transactions()
            ->where('status', 'approved')
            ->where('transaction_type', 'expense')
            ->sum('amount');
        
        $this->remaining_amount = $this->allocated_amount - $this->used_amount;
        $this->save();
    }
} 