<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function summary()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau superadmin yang dapat melihat dashboard.'
            ], 403);
        }

        // Filter dari request
        $statuses = explode(',', request('status', 'Lunas,approved,DP Dibayar'));
        $proyekId = request('proyek_id');
        $kategori = request('kategori', 'semua');

        $userCount = User::count();

        // Income query
        $incomeQuery = DB::table('incomes')->whereIn('status', $statuses);
        if ($proyekId) $incomeQuery->where('proyek_id', $proyekId);
        $totalIncome = ($kategori === 'income' || $kategori === 'semua') ? $incomeQuery->sum('jumlah') : 0;

        // Expense query
        $expenseQuery = Expense::whereIn('status', $statuses);
        if ($proyekId) $expenseQuery->where('proyek_id', $proyekId);
        $expenses = ($kategori === 'expense' || $kategori === 'semua') ? $expenseQuery->get() : collect();

        $totalExpense = $expenses->sum('amount');
        $totalExpenseRemaining = $expenses->sum('prepared_fund');
        $totalPPN = $expenses->sum('ppn_amount');
        $totalPPhFinal = $expenses->sum('pph_final_amount');
        $totalPPhNonFinal = $expenses->sum('pph_non_final_amount');
        $grandTotalExpense = $totalExpense + $totalPPN + $totalPPhFinal + $totalPPhNonFinal;

        $netIncome = $totalIncome - $grandTotalExpense;

        // Jumlah entitas lain (opsional filter proyek jika applicable)
        $paymentMethodCount = DB::table('payment_methods')->count();
        $terminCount = DB::table('termins')->when($proyekId, fn($q) => $q->where('proyek_id', $proyekId))->count();
        $invoiceCount = ($kategori === 'invoice' || $kategori === 'semua')
            ? DB::table('invoices')->when($proyekId, fn($q) => $q->where('proyek_id', $proyekId))->count()
            : 0;

        // Monthly chart
        $year = now()->year;
        $userPerMonth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');

        $incomePerMonth = ($kategori === 'income' || $kategori === 'semua')
            ? DB::table('incomes')
                ->selectRaw('MONTH(tanggal) as month, SUM(jumlah) as total')
                ->whereYear('tanggal', $year)
                ->whereIn('status', $statuses)
                ->when($proyekId, fn($q) => $q->where('proyek_id', $proyekId))
                ->groupByRaw('MONTH(tanggal)')
                ->pluck('total', 'month')
            : collect();

        $expensePerMonth = ($kategori === 'expense' || $kategori === 'semua')
            ? Expense::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
                ->whereYear('transaction_date', $year)
                ->whereIn('status', $statuses)
                ->when($proyekId, fn($q) => $q->where('proyek_id', $proyekId))
                ->groupByRaw('MONTH(transaction_date)')
                ->pluck('total', 'month')
            : collect();

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
            'invoice_count' => $invoiceCount,
            'users' => $users,
            'income' => $income,
            'expense' => $expense,
        ]);
    }
}
