<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    /**
     * GET /api/vehicles
     * List all vehicles with filters, search, sorting, and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Vehicle::query();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return VehicleResource::collection($query->withCount('trips')->paginate($perPage));
    }

    /**
     * POST /api/vehicles
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return response()->json([
            'message' => 'Vehicle created successfully.',
            'data' => new VehicleResource($vehicle),
        ], 201);
    }

    /**
     * GET /api/vehicles/{vehicle}
     */
    public function show(Vehicle $vehicle): VehicleResource
    {
        $vehicle->load(['trips', 'maintenanceLogs', 'fuelLogs', 'expenses', 'documents'])
                ->loadCount('trips');

        return new VehicleResource($vehicle);
    }

    /**
     * PUT /api/vehicles/{vehicle}
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return response()->json([
            'message' => 'Vehicle updated successfully.',
            'data' => new VehicleResource($vehicle->fresh()),
        ]);
    }

    /**
     * DELETE /api/vehicles/{vehicle}
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully.',
        ]);
    }
}
