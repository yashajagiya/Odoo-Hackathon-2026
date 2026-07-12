<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\FuelLog;
use App\Models\MaintenanceLog;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;

class ReportAggregationService
{
    /**
     * Get dashboard KPIs with optional filters.
     * Cached for 60 seconds per unique filter combination.
     */
    public function getDashboardKPIs(array $filters = []): array
    {
        $cacheKey = 'dashboard_kpis_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 60, function () use ($filters) {
            $vehicleQuery = Vehicle::query();
            $tripQuery = Trip::query();
            $driverQuery = Driver::query();

            // Apply vehicle filters
            if (!empty($filters['type'])) {
                $vehicleQuery->where('type', $filters['type']);
            }
            if (!empty($filters['region'])) {
                $vehicleQuery->where('region', $filters['region']);
            }
            if (!empty($filters['status'])) {
                $vehicleQuery->where('status', $filters['status']);
            }

            $totalVehicles = (clone $vehicleQuery)->count();
            $availableVehicles = (clone $vehicleQuery)->where('status', VehicleStatus::Available)->count();
            $onTripVehicles = (clone $vehicleQuery)->where('status', VehicleStatus::OnTrip)->count();
            $inShopVehicles = (clone $vehicleQuery)->where('status', VehicleStatus::InShop)->count();
            $retiredVehicles = (clone $vehicleQuery)->where('status', VehicleStatus::Retired)->count();

            $activeTrips = $tripQuery->where('status', TripStatus::Dispatched)->count();
            $pendingTrips = Trip::where('status', TripStatus::Draft)->count();
            $completedTrips = Trip::where('status', TripStatus::Completed)->count();

            $driversOnDuty = $driverQuery->where('status', DriverStatus::OnTrip)->count();
            $availableDrivers = Driver::where('status', DriverStatus::Available)->count();
            $totalDrivers = Driver::count();

            // Fleet utilization % = vehicles on trip / (total - retired) * 100
            $activeFleet = $totalVehicles - $retiredVehicles;
            $fleetUtilization = $activeFleet > 0 ? round(($onTripVehicles / $activeFleet) * 100, 2) : 0;

            // Financial KPIs
            $totalRevenue = Trip::where('status', TripStatus::Completed)->sum('revenue');
            $totalFuelCost = FuelLog::sum('total_cost');
            $totalExpenses = Expense::sum('amount');
            $totalMaintenanceCost = MaintenanceLog::sum('cost');

            return [
                'vehicles' => [
                    'total' => $totalVehicles,
                    'available' => $availableVehicles,
                    'on_trip' => $onTripVehicles,
                    'in_shop' => $inShopVehicles,
                    'retired' => $retiredVehicles,
                ],
                'trips' => [
                    'active' => $activeTrips,
                    'pending' => $pendingTrips,
                    'completed' => $completedTrips,
                ],
                'drivers' => [
                    'total' => $totalDrivers,
                    'on_duty' => $driversOnDuty,
                    'available' => $availableDrivers,
                ],
                'fleet_utilization_percent' => $fleetUtilization,
                'financials' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_fuel_cost' => (float) $totalFuelCost,
                    'total_expenses' => (float) $totalExpenses,
                    'total_maintenance_cost' => (float) $totalMaintenanceCost,
                    'net_profit' => (float) ($totalRevenue - $totalFuelCost - $totalExpenses - $totalMaintenanceCost),
                ],
            ];
        });
    }

    /**
     * Get fuel efficiency per vehicle (distance/fuel).
     * Cached for 120 seconds per unique parameter combination.
     */
    public function getFuelEfficiency(?int $vehicleId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = 'report_fuel_efficiency_' . md5(json_encode([$vehicleId, $startDate, $endDate]));

        return Cache::remember($cacheKey, 120, function () use ($vehicleId, $startDate, $endDate) {
            $query = Trip::where('status', TripStatus::Completed)
                ->whereNotNull('actual_distance_km')
                ->whereNotNull('fuel_consumed_liters')
                ->where('fuel_consumed_liters', '>', 0);

            if ($vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            }
            if ($startDate) {
                $query->where('completed_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('completed_at', '<=', $endDate);
            }

            $results = $query->selectRaw('
                    vehicle_id,
                    SUM(actual_distance_km) as total_distance,
                    SUM(fuel_consumed_liters) as total_fuel,
                    ROUND(SUM(actual_distance_km) / SUM(fuel_consumed_liters), 2) as km_per_liter,
                    COUNT(*) as trip_count
                ')
                ->groupBy('vehicle_id')
                ->with('vehicle:id,name,registration_number')
                ->get();

            return $results->map(function ($row) {
                return [
                    'vehicle_id' => $row->vehicle_id,
                    'vehicle_name' => $row->vehicle?->name,
                    'registration_number' => $row->vehicle?->registration_number,
                    'total_distance_km' => (float) $row->total_distance,
                    'total_fuel_liters' => (float) $row->total_fuel,
                    'km_per_liter' => (float) $row->km_per_liter,
                    'trip_count' => $row->trip_count,
                ];
            })->toArray();
        });
    }

    /**
     * Get fleet utilization per vehicle.
     * Cached for 120 seconds per unique parameter combination.
     *
     * Optimized: uses withCount to avoid N+1 loop queries.
     */
    public function getFleetUtilization(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = 'report_fleet_utilization_' . md5(json_encode([$startDate, $endDate]));

        return Cache::remember($cacheKey, 120, function () use ($startDate, $endDate) {
            $vehicles = Vehicle::where('status', '!=', VehicleStatus::Retired)
                ->withCount(['trips as completed_trips_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', TripStatus::Completed);
                    if ($startDate) {
                        $query->where('completed_at', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->where('completed_at', '<=', $endDate);
                    }
                }])
                ->withCount(['trips as days_on_trip_count' => function ($query) use ($startDate, $endDate) {
                    $query->where('status', TripStatus::Completed);
                    if ($startDate) {
                        $query->where('dispatched_at', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->where('completed_at', '<=', $endDate);
                    }
                }])
                ->get();

            return $vehicles->map(function (Vehicle $vehicle) use ($startDate, $endDate) {
                $totalDays = $startDate && $endDate
                    ? max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)))
                    : max(1, $vehicle->created_at->diffInDays(now()));

                // Approximation: 1 trip ≈ 1 day
                $utilization = $totalDays > 0 ? round(($vehicle->days_on_trip_count / $totalDays) * 100, 2) : 0;

                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_name' => $vehicle->name,
                    'registration_number' => $vehicle->registration_number,
                    'status' => $vehicle->status->value,
                    'completed_trips' => $vehicle->completed_trips_count,
                    'utilization_percent' => min(100, $utilization),
                ];
            })->toArray();
        });
    }

    /**
     * Get operational cost per vehicle.
     * Cached for 120 seconds per unique parameter combination.
     *
     * Optimized: uses withSum to load aggregates in a single query
     * instead of N+1 individual sum queries per vehicle.
     */
    public function getOperationalCost(?int $vehicleId = null): array
    {
        $cacheKey = 'report_operational_cost_' . ($vehicleId ?? 'all');

        return Cache::remember($cacheKey, 120, function () use ($vehicleId) {
            $query = Vehicle::query()
                ->withSum('fuelLogs as fuel_cost', 'total_cost')
                ->withSum('expenses as expense_cost', 'amount')
                ->withSum('maintenanceLogs as maintenance_cost', 'cost')
                ->withSum(['trips as total_distance' => function ($q) {
                    $q->where('status', TripStatus::Completed);
                }], 'actual_distance_km');

            if ($vehicleId) {
                $query->where('id', $vehicleId);
            }

            return $query->get()->map(function (Vehicle $vehicle) {
                $fuelCost = (float) ($vehicle->fuel_cost ?? 0);
                $expenseCost = (float) ($vehicle->expense_cost ?? 0);
                $maintenanceCost = (float) ($vehicle->maintenance_cost ?? 0);
                $totalCost = $fuelCost + $expenseCost + $maintenanceCost;
                $totalDistance = (float) ($vehicle->total_distance ?? 0);

                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_name' => $vehicle->name,
                    'registration_number' => $vehicle->registration_number,
                    'fuel_cost' => $fuelCost,
                    'expense_cost' => $expenseCost,
                    'maintenance_cost' => $maintenanceCost,
                    'total_cost' => $totalCost,
                    'total_distance_km' => $totalDistance,
                    'cost_per_km' => $totalDistance > 0 ? round($totalCost / $totalDistance, 2) : 0,
                ];
            })->toArray();
        });
    }

    /**
     * Get vehicle ROI = (revenue - costs) / acquisition_cost * 100.
     * Cached for 120 seconds per unique parameter combination.
     *
     * Optimized: uses withSum to load all aggregates in a single query
     * instead of N+1 individual sum queries per vehicle.
     */
    public function getVehicleROI(?int $vehicleId = null): array
    {
        $cacheKey = 'report_vehicle_roi_' . ($vehicleId ?? 'all');

        return Cache::remember($cacheKey, 120, function () use ($vehicleId) {
            $query = Vehicle::query()
                ->withSum(['trips as total_revenue' => function ($q) {
                    $q->where('status', TripStatus::Completed);
                }], 'revenue')
                ->withSum('fuelLogs as fuel_cost', 'total_cost')
                ->withSum('expenses as expense_cost', 'amount')
                ->withSum('maintenanceLogs as maintenance_cost', 'cost');

            if ($vehicleId) {
                $query->where('id', $vehicleId);
            }

            return $query->get()->map(function (Vehicle $vehicle) {
                $revenue = (float) ($vehicle->total_revenue ?? 0);
                $fuelCost = (float) ($vehicle->fuel_cost ?? 0);
                $expenseCost = (float) ($vehicle->expense_cost ?? 0);
                $maintenanceCost = (float) ($vehicle->maintenance_cost ?? 0);
                $totalCost = $fuelCost + $expenseCost + $maintenanceCost;
                $acquisitionCost = (float) $vehicle->acquisition_cost;

                $roi = $acquisitionCost > 0
                    ? round((($revenue - $totalCost) / $acquisitionCost) * 100, 2)
                    : 0;

                return [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_name' => $vehicle->name,
                    'registration_number' => $vehicle->registration_number,
                    'total_revenue' => $revenue,
                    'total_cost' => $totalCost,
                    'acquisition_cost' => $acquisitionCost,
                    'net_profit' => (float) ($revenue - $totalCost),
                    'roi_percent' => $roi,
                ];
            })->toArray();
        });
    }

    /**
     * Invalidate all report and dashboard caches.
     *
     * Called after trip status changes (dispatch/complete/cancel)
     * to ensure fresh data on the next request.
     */
    public function clearReportCaches(): void
    {
        // Clear known cache keys with broad patterns
        // For dashboard KPIs, we clear the default (no-filter) key
        Cache::forget('dashboard_kpis_' . md5(json_encode([])));

        // Clear report caches for "all vehicles" views
        Cache::forget('report_operational_cost_all');
        Cache::forget('report_vehicle_roi_all');

        // For parameterized caches, we use short TTLs (120s) so they
        // naturally expire quickly. Tagged caching with Redis would
        // allow Cache::tags('reports')->flush() for full invalidation.
    }
}
