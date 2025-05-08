<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyek;
use App\Models\ProfitLossReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ProfitLossReport::query()->with(['proyek']);

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        if ($request->has('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        $reports = $query->paginate(10);
        $proyeks = Proyek::all();

        return response()->json([
            'reports' => $reports,
            'proyeks' => $proyeks
        ]);
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'proyek_id' => 'nullable|exists:proyeks,id',
            'period_type' => 'required|in:weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        try {
            DB::beginTransaction();

            // Get transactions for the period
            $query = DB::table('transactions')
                ->whereBetween('transaction_date', [$request->start_date, $request->end_date]);

            if ($request->proyek_id) {
                $query->where('proyek_id', $request->proyek_id);
            }

            $transactions = $query->get();

            // Calculate totals
            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
            $netProfit = $totalIncome - $totalExpense;

            // Get transaction details
            $incomeDetails = $transactions->where('type', 'income')
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'date' => $transaction->transaction_date
                    ];
                })->values();

            $expenseDetails = $transactions->where('type', 'expense')
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'date' => $transaction->transaction_date
                    ];
                })->values();

            // Create report
            $report = ProfitLossReport::create([
                'proyek_id' => $request->proyek_id,
                'period_type' => $request->period_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
                'income_details' => $incomeDetails,
                'expense_details' => $expenseDetails
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Report generated successfully',
                'report' => $report
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate report'], 500);
        }
    }

    public function show(ProfitLossReport $report)
    {
        return response()->json($report->load('proyek'));
    }

    // Laba Rugi summary untuk periode tertentu
    public function summary(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->endOfMonth()->toDateString());

        $totalIncome = \DB::table('incomes')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $totalExpense = \DB::table('expenses')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $profit = $totalIncome - $totalExpense;

        return response()->json([
            'start_date' => $start,
            'end_date' => $end,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'profit' => $profit,
        ]);
    }

    // Rekap per bulan/tahun
    public function recap(Request $request)
    {
        $type = $request->input('type', 'monthly'); // 'monthly' atau 'yearly'
        $year = $request->input('year', now()->year);

        if ($type === 'monthly') {
            $income = \DB::table('incomes')
                ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_income')
                ->whereYear('transaction_date', $year)
                ->groupBy(\DB::raw('MONTH(transaction_date)'))
                ->pluck('total_income', 'month');

            $expense = \DB::table('expenses')
                ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_expense')
                ->whereYear('transaction_date', $year)
                ->groupBy(\DB::raw('MONTH(transaction_date)'))
                ->pluck('total_expense', 'month');

            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $inc = $income[$i] ?? 0;
                $exp = $expense[$i] ?? 0;
                $result[] = [
                    'month' => $i,
                    'total_income' => $inc,
                    'total_expense' => $exp,
                    'profit' => $inc - $exp,
                ];
            }
            return response()->json($result);
        } else { // yearly
            $income = \DB::table('incomes')
                ->selectRaw('YEAR(transaction_date) as year, SUM(amount) as total_income')
                ->groupBy(\DB::raw('YEAR(transaction_date)'))
                ->pluck('total_income', 'year');

            $expense = \DB::table('expenses')
                ->selectRaw('YEAR(transaction_date) as year, SUM(amount) as total_expense')
                ->groupBy(\DB::raw('YEAR(transaction_date)'))
                ->pluck('total_expense', 'year');

            $years = array_unique(array_merge(array_keys($income->toArray()), array_keys($expense->toArray())));
            sort($years);

            $result = [];
            foreach ($years as $y) {
                $inc = $income[$y] ?? 0;
                $exp = $expense[$y] ?? 0;
                $result[] = [
                    'year' => $y,
                    'total_income' => $inc,
                    'total_expense' => $exp,
                    'profit' => $inc - $exp,
                ];
            }
            return response()->json($result);
        }
    }
} 