<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Income;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'nama_metode',
        'deskripsi',
        'is_active',
    ];

    /**
     * Relasi ke pemasukan.
     */
    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Relasi ke user yang membuat data.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang memperbarui data.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Mengambil semua metode pembayaran yang aktif.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActivePaymentMethods()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * Mengambil semua metode pembayaran yang tidak aktif.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getInactivePaymentMethods()
    {
        return self::where('is_active', false)->get();
    }

    /**
     * Mengambil semua metode pembayaran yang aktif dan tidak aktif.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllPaymentMethods()
    {
        return self::all();
    }

    /**
     * Mengambil metode pembayaran berdasarkan ID.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getPaymentMethodById($id)
    {
        return self::find($id);
    }
}
