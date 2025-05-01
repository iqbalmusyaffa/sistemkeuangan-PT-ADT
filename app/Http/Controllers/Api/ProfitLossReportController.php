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
} 