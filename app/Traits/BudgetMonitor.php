<?php

namespace App\Traits;

use App\Models\Proyek;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BudgetExceededNotification;

trait BudgetMonitor
{
    public function checkBudgetExceeded()
    {
        if (!$this instanceof Proyek) {
            return;
        }

        $totalExpenses = $this->expenses()->sum('amount');
        $budgetLimit = $this->anggaran_kontrak;
        
        // Jika pengeluaran melebihi 80% dari anggaran, kirim notifikasi
        if ($totalExpenses >= ($budgetLimit * 0.8)) {
            $percentage = ($totalExpenses / $budgetLimit) * 100;
            $message = "Peringatan: Pengeluaran proyek {$this->nama_proyek} telah mencapai {$percentage}% dari anggaran.";
            
            // Kirim notifikasi ke user yang terkait dengan proyek
            $this->user->notify(new BudgetExceededNotification($this, $message));
        }
    }
} 