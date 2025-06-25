<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proyek;
use App\Models\ProfitLossReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

        return response()->json([
            'status' => 'success',
            'data' => $reports
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

            // Ambil pendapatan dari tabel `incomes`
            $incomeQuery = DB::table('incomes')
                ->whereBetween('tanggal', [$request->start_date, $request->end_date]);

            // Ambil pengeluaran dari tabel `expenses`
            $expenseQuery = DB::table('expenses')
                ->whereBetween('transaction_date', [$request->start_date, $request->end_date]);

            if ($request->proyek_id) {
                $incomeQuery->where('proyek_id', $request->proyek_id);
                $expenseQuery->where('proyek_id', $request->proyek_id);
            }

            $incomes = $incomeQuery->get();
            $expenses = $expenseQuery->get();

            $totalIncome = $incomes->sum('jumlah');
            $totalExpense = $expenses->sum('amount');
            $netProfit = $totalIncome - $totalExpense;

            $incomeDetails = $incomes->map(fn($i) => [
                'id' => $i->id,
                'description' => $i->deskripsi ?? '',
                'amount' => $i->jumlah,
                'date' => $i->tanggal
            ])->values();

            $expenseDetails = $expenses->map(fn($e) => [
                'id' => $e->id,
                'description' => $e->description ?? '',
                'amount' => $e->amount,
                'date' => $e->transaction_date
            ])->values();

            // Simpan ke tabel profit_loss_reports
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
                'message' => 'Laporan berhasil dibuat',
                'report' => $report
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error generating profit-loss report: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Gagal membuat laporan'], 500);
        }
    }


    public function show(ProfitLossReport $report)
    {
        return response()->json($report->load('proyek'));
    }

    public function summary(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->endOfMonth()->toDateString());

        $totalIncome = DB::table('transactions')
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $totalExpense = DB::table('transactions')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        return response()->json([
            'start_date' => $start,
            'end_date' => $end,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'profit' => $totalIncome - $totalExpense,
        ]);
    }

    public function recap(Request $request)
    {
        $type = $request->input('type', 'monthly');
        $year = $request->input('year', now()->year);

        if ($type === 'monthly') {
            $income = DB::table('transactions')
                ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_income')
                ->where('type', 'income')
                ->whereYear('transaction_date', $year)
                ->groupBy(DB::raw('MONTH(transaction_date)'))
                ->pluck('total_income', 'month');

            $expense = DB::table('transactions')
                ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_expense')
                ->where('type', 'expense')
                ->whereYear('transaction_date', $year)
                ->groupBy(DB::raw('MONTH(transaction_date)'))
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
        } else {
            $income = DB::table('transactions')
                ->selectRaw('YEAR(transaction_date) as year, SUM(amount) as total_income')
                ->where('type', 'income')
                ->groupBy(DB::raw('YEAR(transaction_date)'))
                ->pluck('total_income', 'year');

            $expense = DB::table('transactions')
                ->selectRaw('YEAR(transaction_date) as year, SUM(amount) as total_expense')
                ->where('type', 'expense')
                ->groupBy(DB::raw('YEAR(transaction_date)'))
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

  public function export(Request $request, $format)
{
    $request->validate([
        'proyek_id' => 'nullable|exists:proyeks,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date'
    ]);

    $filters = $request->only(['proyek_id', 'start_date', 'end_date']);

    if ($format === 'excel') {
        return $this->exportToExcel($filters);
    } elseif ($format === 'pdf') {
        return $this->exportToPDF($filters);
    } else {
        return response()->json(['message' => 'Format tidak dikenali'], 400);
    }
}

 public function exportToPDF(array $filters)
{
    $incomeQuery = DB::table('incomes')
        ->whereBetween('tanggal', [$filters['start_date'], $filters['end_date']]);

    $expenseQuery = DB::table('expenses')
        ->whereBetween('transaction_date', [$filters['start_date'], $filters['end_date']]);

    if (!empty($filters['proyek_id'])) {
        $incomeQuery->where('proyek_id', $filters['proyek_id']);
        $expenseQuery->where('proyek_id', $filters['proyek_id']);
    }

    $incomes = $incomeQuery->get()->map(function ($i) {
        return (object)[
            'tanggal' => $i->tanggal,
            'deskripsi' => $i->deskripsi ?? '',
            'jenis' => 'income',
            'jumlah' => $i->jumlah,
        ];
    });

    $expenses = $expenseQuery->get()->map(function ($e) {
        return (object)[
            'tanggal' => $e->transaction_date,
            'deskripsi' => $e->description ?? '',
            'jenis' => 'expense',
            'jumlah' => $e->amount,
        ];
    });

    $transactions = $incomes->concat($expenses)->sortBy('tanggal')->values();
    $totalIncome = $incomes->sum('jumlah');
    $totalExpense = $expenses->sum('jumlah');
    $netProfit = $totalIncome - $totalExpense;

    $pdf = PDF::loadView('labarugi.profit_loss_report', [
        'transactions' => $transactions,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'netProfit' => $netProfit,
        'startDate' => $filters['start_date'],
        'endDate' => $filters['end_date']
    ]);

    return response()->streamDownload(function () use ($pdf) {
        echo $pdf->output();
    }, 'laporan_laba_rugi.pdf');
}
}
