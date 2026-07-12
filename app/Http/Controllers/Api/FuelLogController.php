<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFuelLogRequest;
use App\Http\Resources\FuelLogResource;
use App\Models\FuelLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FuelLogController extends Controller
{
    /**
     * GET /api/fuel-logs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = FuelLog::with(['vehicle', 'trip']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
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

        return FuelLogResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/fuel-logs
     */
    public function store(StoreFuelLogRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-calculate total_cost if not provided
        if (empty($data['total_cost'])) {
            $data['total_cost'] = $data['liters'] * $data['cost_per_liter'];
        }

        $fuelLog = FuelLog::create($data);

        return response()->json([
            'message' => 'Fuel log created successfully.',
            'data' => new FuelLogResource($fuelLog->load(['vehicle', 'trip'])),
        ], 201);
    }

    /**
     * GET /api/fuel-logs/{fuelLog}
     */
    public function show(FuelLog $fuelLog): FuelLogResource
    {
        return new FuelLogResource($fuelLog->load(['vehicle', 'trip']));
    }

    /**
     * PUT /api/fuel-logs/{fuelLog}
     */
    public function update(Request $request, FuelLog $fuelLog): JsonResponse
    {
        $validated = $request->validate([
            'liters' => 'sometimes|numeric|min:0.01',
            'cost_per_liter' => 'sometimes|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'date' => 'sometimes|date',
            'odometer_km' => 'nullable|numeric|min:0',
        ]);

        $fuelLog->update($validated);

        return response()->json([
            'message' => 'Fuel log updated successfully.',
            'data' => new FuelLogResource($fuelLog->fresh()->load(['vehicle', 'trip'])),
        ]);
    }

    /**
     * DELETE /api/fuel-logs/{fuelLog}
     */
    public function destroy(FuelLog $fuelLog): JsonResponse
    {
        $fuelLog->delete();

        return response()->json([
            'message' => 'Fuel log deleted successfully.',
        ]);
    }
}
