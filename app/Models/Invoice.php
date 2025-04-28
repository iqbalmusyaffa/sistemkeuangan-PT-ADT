<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'amount_paid',
        'notes',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'invoice_date' => 'date'
    ];

    // Relasi ke proyek
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    // Relasi ke purchase materials (jika invoice ini punya banyak item material)
    public function purchaseMaterials()
    {
        return $this->hasMany(PurchaseMaterial::class, 'invoice_id');
    }

    // Relasi ke termin pembayaran (kalau pakai termin)
    public function termins()
    {
        return $this->hasMany(Termin::class);
    }

    // Relasi ke expenses (pencatatan pengeluaran berdasarkan invoice)
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'invoice_id');
    }

    // Determining the status of the invoice based on the paid amount
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

    // Mutator untuk `amount_paid` yang otomatis update status
    public function setAmountPaidAttribute($value)
    {
        $this->attributes['amount_paid'] = $value;
        $this->attributes['status'] = $this->determineStatus(); // Update status setelah amount_paid diubah
    }
}
