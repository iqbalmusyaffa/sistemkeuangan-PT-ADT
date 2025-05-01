<?php

namespace App\Http\Controllers;

use App\Models\ProfitLossReport;
use App\Models\Project;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProfitLossReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ProfitLossReport::query();

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by period type
        if ($request->has('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        $reports = $query->with('project')->paginate(10);
        $projects = Project::all();

        return response()->json([
            'reports' => $reports,
            'projects' => $projects
        ]);
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'period_type' => 'required|in:weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Get incomes
        $incomes = Income::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->project_id, function ($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->get();

        // Get expenses
        $expenses = Expense::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->project_id, function ($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->get();

        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        $report = ProfitLossReport::create([
            'project_id' => $request->project_id,
            'period_type' => $request->period_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
            'income_details' => $incomes,
            'expense_details' => $expenses
        ]);

        return response()->json([
            'message' => 'Report generated successfully',
            'report' => $report
        ]);
    }

    public function show(ProfitLossReport $report)
    {
        return response()->json($report->load('project'));
    }
} 