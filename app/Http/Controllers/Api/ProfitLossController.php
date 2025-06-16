<?php

namespace App\Http\Controllers\Api;

use App\Models\Income;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $totalIncome = Income::whereBetween('tanggal', [$startDate, $endDate])->sum('amount');
        $totalExpense = Expense::whereBetween('tanggal', [$startDate, $endDate])->sum('amount');

        $profitLoss = $totalIncome - $totalExpense;

        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'profit_loss' => $profitLoss,
        ]);
    }
}
