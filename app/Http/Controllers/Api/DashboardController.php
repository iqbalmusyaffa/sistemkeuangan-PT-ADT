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
        $totalExpense = DB::table('expenses')->sum('amount');
        return response()->json([
            'user_count' => $userCount,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
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

        // Expense per bulan
        $expensePerMonth = \DB::table('expenses')
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->whereYear('transaction_date', $year)
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