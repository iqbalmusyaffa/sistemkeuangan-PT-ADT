<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $userCount = User::count();
        $totalIncome = DB::table('incomes')->sum('jumlah');
        $expenses = \App\Models\Expense::whereIn('status', ['Lunas', 'approved'])->get();
        $totalExpense = $expenses->sum('amount');
        $totalExpenseRemaining = $expenses->sum('prepared_fund');
        // Pajak (jika ada field di table expenses)
        $totalPPN = $expenses->sum('ppn_amount');
        $totalPPhFinal = $expenses->sum('pph_final_amount');
        $totalPPhNonFinal = $expenses->sum('pph_non_final_amount');
            $grandTotalExpense = $totalExpense + $totalPPN + $totalPPhFinal + $totalPPhNonFinal;
                $netIncome = $totalIncome - $grandTotalExpense;

        $paymentMethodCount = DB::table('payment_methods')->count();
        $terminCount = DB::table('termins')->count();
        $piutangCount = DB::table('piutangs')->count();
        $kasbonCount = DB::table('kasbons')->count();
        $invoiceCount = DB::table('invoices')->count();

        $year = now()->year;
        // User baru per bulan
        $userPerMonth = \App\Models\User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');
        // Income per bulan
        $incomePerMonth = DB::table('incomes')
            ->selectRaw('MONTH(tanggal) as month, SUM(jumlah) as total')
            ->whereYear('tanggal', $year)
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total', 'month');
        // Expense per bulan (hanya status Lunas/approved)
        $expensePerMonth = \App\Models\Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)
            ->whereIn('status', ['Lunas', 'approved'])
            ->groupByRaw('MONTH(transaction_date)')
            ->pluck('total', 'month');
        // Format data untuk 12 bulan
        $users = $income = $expense = [];
        for ($i = 1; $i <= 12; $i++) {
            $users[] = (int) ($userPerMonth[$i] ?? 0);
            $income[] = (float) ($incomePerMonth[$i] ?? 0);
            $expense[] = (float) ($expensePerMonth[$i] ?? 0);
        }

        return response()->json([
            'user_count' => $userCount,
             'net_income' => $netIncome,
        'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'total_expense_remaining' => $totalExpenseRemaining,
            'total_ppn' => $totalPPN,
            'total_pph_final' => $totalPPhFinal,
            'total_pph_non_final' => $totalPPhNonFinal,
            'grand_total_expense' => $grandTotalExpense,
            'payment_method_count' => $paymentMethodCount,
            'termin_count' => $terminCount,
            'piutang_count' => $piutangCount,
            'kasbon_count' => $kasbonCount,
            'invoice_count' => $invoiceCount,
            // Ganti key agar konsisten dengan frontend dan chartSummary
            'users' => $users,
            'income' => $income,
            'expense' => $expense,
        ]);
    }

    public function chartSummary()
    {
        $year = request('year', now()->year);

        // User baru per bulan
        $userPerMonth = \App\Models\User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');

        // Income per bulan
        $incomePerMonth = \DB::table('incomes')
            ->selectRaw('MONTH(tanggal) as month, SUM(jumlah) as total')
            ->whereYear('tanggal', $year)
            ->groupByRaw('MONTH(tanggal)')
            ->pluck('total', 'month');

        // Expense per bulan (hanya status Lunas/approved)
        $expensePerMonth = \App\Models\Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)
            ->whereIn('status', ['Lunas', 'approved'])
            ->groupByRaw('MONTH(transaction_date)')
            ->pluck('total', 'month');

        // Format data untuk 12 bulan
        $users = $income = $expense = [];
        for ($i = 1; $i <= 12; $i++) {
            $users[] = (int) ($userPerMonth[$i] ?? 0);
            $income[] = (float) ($incomePerMonth[$i] ?? 0);
            $expense[] = (float) ($expensePerMonth[$i] ?? 0);
        }

        return response()->json([
            'users' => $users,
            'income' => $income,
            'expense' => $expense,
        ]);
    }
}
