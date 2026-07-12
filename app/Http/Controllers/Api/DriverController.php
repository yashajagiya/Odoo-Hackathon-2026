<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DriverController extends Controller
{
    /**
     * GET /api/drivers
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Driver::with('user');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('license_category')) {
            $query->where('license_category', $request->license_category);
        }

        // Filter dispatchable drivers (available + valid license)
        if ($request->boolean('dispatchable')) {
            $query->dispatchable();
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return DriverResource::collection($query->withCount('trips')->paginate($perPage));
    }

    /**
     * POST /api/drivers
     */
    public function store(StoreDriverRequest $request): JsonResponse
    {
        $driver = Driver::create($request->validated());

        return response()->json([
            'message' => 'Driver created successfully.',
            'data' => new DriverResource($driver->load('user')),
        ], 201);
    }

    /**
     * GET /api/drivers/{driver}
     */
    public function show(Driver $driver): DriverResource
    {
        return new DriverResource($driver->load(['user', 'trips']));
    }

    /**
     * PUT /api/drivers/{driver}
     */
    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $driver->update($request->validated());

        return response()->json([
            'message' => 'Driver updated successfully.',
            'data' => new DriverResource($driver->fresh()->load('user')),
        ]);
    }

    /**
     * DELETE /api/drivers/{driver}
     */
    public function destroy(Driver $driver): JsonResponse
    {
        $driver->delete();

        return response()->json([
            'message' => 'Driver deleted successfully.',
        ]);
    }
}
