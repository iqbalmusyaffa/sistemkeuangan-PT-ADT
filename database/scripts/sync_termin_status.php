<?php

use Illuminate\Support\Facades\DB;
use App\Models\Termin;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;

// Fetch all termin records
$termins = Termin::all();

foreach ($termins as $termin) {
    DB::transaction(function () use ($termin) {
        // Synchronize termin status with related records
        $termin->updateStatusFromPayments();
    });
}

echo "Database synchronization completed successfully.";
