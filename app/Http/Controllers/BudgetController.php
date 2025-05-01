<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BudgetTransaction;
use App\Models\Proyek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::query()->with(['proyek', 'categories']);

        if ($request->has('proyek_id')) {
            $query->where('proyek_id', $request->proyek_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        $budgets = $query->paginate(10);
        $proyeks = Proyek::all();

        return response()->json([
            'budgets' => $budgets,
            'proyeks' => $proyeks
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'proyek_id' => 'nullable|exists:proyeks,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.description' => 'nullable|string',
            'categories.*.allocated_amount' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $budget = Budget::create([
                'proyek_id' => $request->proyek_id,
                'name' => $request->name,
                'description' => $request->description,
                'total_amount' => $request->total_amount,
                'used_amount' => 0,
                'remaining_amount' => $request->total_amount,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'active'
            ]);

            foreach ($request->categories as $category) {
                BudgetCategory::create([
                    'budget_id' => $budget->id,
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'allocated_amount' => $category['allocated_amount'],
                    'used_amount' => 0,
                    'remaining_amount' => $category['allocated_amount']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Budget created successfully',
                'budget' => $budget->load('categories')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create budget'], 500);
        }
    }

    public function show(Budget $budget)
    {
        return response()->json($budget->load(['proyek', 'categories', 'transactions']));
    }

    public function update(Request $request, Budget $budget)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,cancelled'
        ]);

        $budget->update($request->only(['name', 'description', 'status']));

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget
        ]);
    }

    public function addTransaction(Request $request, Budget $budget)
    {
        $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'transaction_type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'transaction_date' => 'required|date'
        ]);

        $transaction = BudgetTransaction::create([
            'budget_id' => $budget->id,
            'budget_category_id' => $request->budget_category_id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Transaction added successfully',
            'transaction' => $transaction
        ]);
    }

    public function approveTransaction(BudgetTransaction $transaction)
    {
        $transaction->update(['status' => 'approved']);
        return response()->json(['message' => 'Transaction approved successfully']);
    }

    public function rejectTransaction(BudgetTransaction $transaction)
    {
        $transaction->update(['status' => 'rejected']);
        return response()->json(['message' => 'Transaction rejected successfully']);
    }
} 