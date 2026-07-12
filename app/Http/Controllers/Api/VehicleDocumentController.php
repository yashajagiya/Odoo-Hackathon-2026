<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleDocumentRequest;
use App\Http\Resources\VehicleDocumentResource;
use App\Models\VehicleDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class VehicleDocumentController extends Controller
{
    /**
     * GET /api/vehicle-documents
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = VehicleDocument::with('vehicle');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        // Filter expired documents
        if ($request->boolean('expired_only')) {
            $query->where('expiry_date', '<', now()->toDateString());
        }

        // Filter expiring soon (within N days)
        if ($request->filled('expiring_within_days')) {
            $days = (int) $request->expiring_within_days;
            $query->where('expiry_date', '>=', now()->toDateString())
                  ->where('expiry_date', '<=', now()->addDays($days)->toDateString());
        }

        $sortBy = $request->get('sort_by', 'expiry_date');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 15);

        return VehicleDocumentResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/vehicle-documents — Upload a document.
     */
    public function store(StoreVehicleDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('vehicle-documents', 'local');

        $document = VehicleDocument::create([
            'vehicle_id' => $request->vehicle_id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => new VehicleDocumentResource($document->load('vehicle')),
        ], 201);
    }

    /**
     * GET /api/vehicle-documents/{vehicleDocument}
     */
    public function show(VehicleDocument $vehicleDocument): VehicleDocumentResource
    {
        return new VehicleDocumentResource($vehicleDocument->load('vehicle'));
    }

    /**
     * PUT /api/vehicle-documents/{vehicleDocument}
     */
    public function update(Request $request, VehicleDocument $vehicleDocument): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'sometimes|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
        ]);

        $vehicleDocument->update($validated);

        return response()->json([
            'message' => 'Document updated successfully.',
            'data' => new VehicleDocumentResource($vehicleDocument->fresh()->load('vehicle')),
        ]);
    }

    /**
     * DELETE /api/vehicle-documents/{vehicleDocument}
     */
    public function destroy(VehicleDocument $vehicleDocument): JsonResponse
    {
        // Delete the file from storage
        if ($vehicleDocument->file_path && Storage::disk('local')->exists($vehicleDocument->file_path)) {
            Storage::disk('local')->delete($vehicleDocument->file_path);
        }

        $vehicleDocument->delete();

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    /**
     * GET /api/vehicle-documents/{vehicleDocument}/download
     */
    public function download(VehicleDocument $vehicleDocument)
    {
        if (!$vehicleDocument->file_path || !Storage::disk('local')->exists($vehicleDocument->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk('local')->download($vehicleDocument->file_path);
    }
}
