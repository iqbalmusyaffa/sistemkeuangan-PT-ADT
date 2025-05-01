<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'name',
        'description',
        'total_amount',
        'used_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'categories'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'categories' => 'array'
    ];

    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
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
        
        $this->remaining_amount = $this->total_amount - $this->used_amount;
        $this->save();
    }
} 