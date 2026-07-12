<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportAggregationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportAggregationService $reportService
    ) {}

    /**
     * GET /api/reports/fuel-efficiency
     */
    public function fuelEfficiency(Request $request): JsonResponse
    {
        $data = $this->reportService->getFuelEfficiency(
            $request->input('vehicle_id'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json([
            'message' => 'Fuel efficiency report generated.',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/reports/fleet-utilization
     */
    public function fleetUtilization(Request $request): JsonResponse
    {
        $data = $this->reportService->getFleetUtilization(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json([
            'message' => 'Fleet utilization report generated.',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/reports/operational-cost
     */
    public function operationalCost(Request $request): JsonResponse
    {
        $data = $this->reportService->getOperationalCost(
            $request->input('vehicle_id')
        );

        return response()->json([
            'message' => 'Operational cost report generated.',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/reports/vehicle-roi
     */
    public function vehicleROI(Request $request): JsonResponse
    {
        $data = $this->reportService->getVehicleROI(
            $request->input('vehicle_id')
        );

        return response()->json([
            'message' => 'Vehicle ROI report generated.',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/reports/export/csv
     * Export trip data as CSV.
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'type' => 'required|in:trips,fuel-logs,expenses,vehicles',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$type}_report.csv",
        ];

        $callback = function () use ($type, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            switch ($type) {
                case 'trips':
                    fputcsv($file, ['ID', 'Source', 'Destination', 'Vehicle', 'Driver', 'Status', 'Distance (km)', 'Fuel (L)', 'Revenue', 'Dispatched', 'Completed']);

                    $query = \App\Models\Trip::with(['vehicle', 'driver']);
                    if ($startDate) $query->where('created_at', '>=', $startDate);
                    if ($endDate) $query->where('created_at', '<=', $endDate);

                    $query->chunk(500, function ($trips) use ($file) {
                        foreach ($trips as $trip) {
                            fputcsv($file, [
                                $trip->id,
                                $trip->source,
                                $trip->destination,
                                $trip->vehicle?->name,
                                $trip->driver?->name,
                                $trip->status?->value,
                                $trip->actual_distance_km,
                                $trip->fuel_consumed_liters,
                                $trip->revenue,
                                $trip->dispatched_at,
                                $trip->completed_at,
                            ]);
                        }
                    });
                    break;

                case 'fuel-logs':
                    fputcsv($file, ['ID', 'Vehicle', 'Liters', 'Cost/Liter', 'Total Cost', 'Date', 'Odometer (km)']);

                    $query = \App\Models\FuelLog::with('vehicle');
                    if ($startDate) $query->where('date', '>=', $startDate);
                    if ($endDate) $query->where('date', '<=', $endDate);

                    $query->chunk(500, function ($logs) use ($file) {
                        foreach ($logs as $log) {
                            fputcsv($file, [
                                $log->id,
                                $log->vehicle?->name,
                                $log->liters,
                                $log->cost_per_liter,
                                $log->total_cost,
                                $log->date?->toDateString(),
                                $log->odometer_km,
                            ]);
                        }
                    });
                    break;

                case 'expenses':
                    fputcsv($file, ['ID', 'Vehicle', 'Type', 'Amount', 'Date', 'Description']);

                    $query = \App\Models\Expense::with('vehicle');
                    if ($startDate) $query->where('date', '>=', $startDate);
                    if ($endDate) $query->where('date', '<=', $endDate);

                    $query->chunk(500, function ($expenses) use ($file) {
                        foreach ($expenses as $expense) {
                            fputcsv($file, [
                                $expense->id,
                                $expense->vehicle?->name,
                                $expense->type?->value,
                                $expense->amount,
                                $expense->date?->toDateString(),
                                $expense->description,
                            ]);
                        }
                    });
                    break;

                case 'vehicles':
                    fputcsv($file, ['ID', 'Registration', 'Name', 'Model', 'Type', 'Status', 'Region', 'Odometer (km)', 'Acquisition Cost']);

                    \App\Models\Vehicle::chunk(500, function ($vehicles) use ($file) {
                        foreach ($vehicles as $vehicle) {
                            fputcsv($file, [
                                $vehicle->id,
                                $vehicle->registration_number,
                                $vehicle->name,
                                $vehicle->model,
                                $vehicle->type,
                                $vehicle->status?->value,
                                $vehicle->region,
                                $vehicle->odometer_km,
                                $vehicle->acquisition_cost,
                            ]);
                        }
                    });
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
