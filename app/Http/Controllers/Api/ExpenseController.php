<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    /**
     * GET /api/expenses
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Expense::with(['vehicle', 'trip']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $sortBy = $request->get('sort_by', 'date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return ExpenseResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/expenses
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = Expense::create($request->validated());

        return response()->json([
            'message' => 'Expense created successfully.',
            'data' => new ExpenseResource($expense->load(['vehicle', 'trip'])),
        ], 201);
    }

    /**
     * GET /api/expenses/{expense}
     */
    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load(['vehicle', 'trip']));
    }

    /**
     * PUT /api/expenses/{expense}
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'receipt_path' => 'nullable|string|max:255',
        ]);

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'data' => new ExpenseResource($expense->fresh()->load(['vehicle', 'trip'])),
        ]);
    }

    /**
     * DELETE /api/expenses/{expense}
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully.',
        ]);
    }
}
