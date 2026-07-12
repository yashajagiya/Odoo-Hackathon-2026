<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteTripRequest;
use App\Http\Requests\StoreTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripController extends Controller
{
    public function __construct(
        private TripDispatchService $tripService
    ) {}

    /**
     * GET /api/trips
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Trip::with(['vehicle', 'driver']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return TripResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/trips — Creates a trip in Draft status.
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = Trip::create(array_merge($request->validated(), [
            'status' => 'Draft',
        ]));

        return response()->json([
            'message' => 'Trip created in Draft status.',
            'data' => new TripResource($trip->load(['vehicle', 'driver'])),
        ], 201);
    }

    /**
     * GET /api/trips/{trip}
     */
    public function show(Trip $trip): TripResource
    {
        return new TripResource($trip->load(['vehicle', 'driver', 'fuelLogs', 'expenses']));
    }

    /**
     * PUT /api/trips/{trip}
     */
    public function update(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'cargo_weight_kg' => 'nullable|numeric|min:0',
            'planned_distance_km' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
        ]);

        $trip->update($validated);

        return response()->json([
            'message' => 'Trip updated successfully.',
            'data' => new TripResource($trip->fresh()->load(['vehicle', 'driver'])),
        ]);
    }

    /**
     * DELETE /api/trips/{trip}
     */
    public function destroy(Trip $trip): JsonResponse
    {
        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted successfully.',
        ]);
    }

    /**
     * POST /api/trips/{trip}/dispatch
     * Dispatch a trip — validates business rules and atomically sets statuses.
     */
    public function dispatch(Trip $trip): JsonResponse
    {
        $trip = $this->tripService->dispatch($trip);

        return response()->json([
            'message' => 'Trip dispatched successfully.',
            'data' => new TripResource($trip),
        ]);
    }

    /**
     * POST /api/trips/{trip}/complete
     * Complete a trip — captures final data and reverts statuses.
     */
    public function complete(CompleteTripRequest $request, Trip $trip): JsonResponse
    {
        $trip = $this->tripService->complete($trip, $request->validated());

        return response()->json([
            'message' => 'Trip completed successfully.',
            'data' => new TripResource($trip),
        ]);
    }

    /**
     * POST /api/trips/{trip}/cancel
     * Cancel a trip — reverts vehicle and driver statuses.
     */
    public function cancel(Trip $trip): JsonResponse
    {
        $trip = $this->tripService->cancel($trip);

        return response()->json([
            'message' => 'Trip cancelled successfully.',
            'data' => new TripResource($trip),
        ]);
    }
}
