<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceLogRequest;
use App\Http\Resources\MaintenanceLogResource;
use App\Models\MaintenanceLog;
use App\Services\MaintenanceStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceLogController extends Controller
{
    public function __construct(
        private MaintenanceStatusService $maintenanceService
    ) {}

    /**
     * GET /api/maintenance-logs
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MaintenanceLog::with('vehicle');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return MaintenanceLogResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/maintenance-logs — Creates log and sets vehicle to In Shop.
     */
    public function store(StoreMaintenanceLogRequest $request): JsonResponse
    {
        $log = $this->maintenanceService->openMaintenance($request->validated());

        return response()->json([
            'message' => 'Maintenance log created. Vehicle status set to In Shop.',
            'data' => new MaintenanceLogResource($log),
        ], 201);
    }

    /**
     * GET /api/maintenance-logs/{maintenanceLog}
     */
    public function show(MaintenanceLog $maintenanceLog): MaintenanceLogResource
    {
        return new MaintenanceLogResource($maintenanceLog->load('vehicle'));
    }

    /**
     * PUT /api/maintenance-logs/{maintenanceLog}
     */
    public function update(Request $request, MaintenanceLog $maintenanceLog): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'cost' => 'nullable|numeric|min:0',
            'end_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $maintenanceLog->update($validated);

        return response()->json([
            'message' => 'Maintenance log updated successfully.',
            'data' => new MaintenanceLogResource($maintenanceLog->fresh()->load('vehicle')),
        ]);
    }

    /**
     * DELETE /api/maintenance-logs/{maintenanceLog}
     */
    public function destroy(MaintenanceLog $maintenanceLog): JsonResponse
    {
        $maintenanceLog->delete();

        return response()->json([
            'message' => 'Maintenance log deleted successfully.',
        ]);
    }

    /**
     * POST /api/maintenance-logs/{maintenanceLog}/close
     * Closes maintenance and restores vehicle to Available.
     */
    public function close(MaintenanceLog $maintenanceLog): JsonResponse
    {
        $log = $this->maintenanceService->closeMaintenance($maintenanceLog);

        return response()->json([
            'message' => 'Maintenance log closed. Vehicle status restored.',
            'data' => new MaintenanceLogResource($log),
        ]);
    }
}
