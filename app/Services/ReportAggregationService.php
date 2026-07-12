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
     */
    public function getFuelEfficiency(?int $vehicleId = null, ?string $startDate = null, ?string $endDate = null): array
    {
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
    }

    /**
     * Get fleet utilization per vehicle.
     */
    public function getFleetUtilization(?string $startDate = null, ?string $endDate = null): array
    {
        $vehicles = Vehicle::where('status', '!=', VehicleStatus::Retired)->get();

        return $vehicles->map(function (Vehicle $vehicle) use ($startDate, $endDate) {
            $query = Trip::where('vehicle_id', $vehicle->id)
                ->where('status', TripStatus::Completed);

            if ($startDate) {
                $query->where('completed_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('completed_at', '<=', $endDate);
            }

            $completedTrips = $query->count();
            $totalDays = $startDate && $endDate
                ? max(1, now()->parse($startDate)->diffInDays(now()->parse($endDate)))
                : max(1, $vehicle->created_at->diffInDays(now()));

            $daysOnTrip = Trip::where('vehicle_id', $vehicle->id)
                ->where('status', TripStatus::Completed)
                ->when($startDate, fn($q) => $q->where('dispatched_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('completed_at', '<=', $endDate))
                ->count(); // Approximation: 1 trip ≈ 1 day

            $utilization = $totalDays > 0 ? round(($daysOnTrip / $totalDays) * 100, 2) : 0;

            return [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'registration_number' => $vehicle->registration_number,
                'status' => $vehicle->status->value,
                'completed_trips' => $completedTrips,
                'utilization_percent' => min(100, $utilization),
            ];
        })->toArray();
    }

    /**
     * Get operational cost per vehicle.
     */
    public function getOperationalCost(?int $vehicleId = null): array
    {
        $query = Vehicle::query();
        if ($vehicleId) {
            $query->where('id', $vehicleId);
        }

        return $query->get()->map(function (Vehicle $vehicle) {
            $fuelCost = $vehicle->fuelLogs()->sum('total_cost');
            $expenseCost = $vehicle->expenses()->sum('amount');
            $maintenanceCost = $vehicle->maintenanceLogs()->sum('cost');
            $totalCost = $fuelCost + $expenseCost + $maintenanceCost;
            $totalDistance = $vehicle->trips()->where('status', TripStatus::Completed)->sum('actual_distance_km');

            return [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'registration_number' => $vehicle->registration_number,
                'fuel_cost' => (float) $fuelCost,
                'expense_cost' => (float) $expenseCost,
                'maintenance_cost' => (float) $maintenanceCost,
                'total_cost' => (float) $totalCost,
                'total_distance_km' => (float) $totalDistance,
                'cost_per_km' => $totalDistance > 0 ? round($totalCost / $totalDistance, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get vehicle ROI = (revenue - costs) / acquisition_cost * 100.
     */
    public function getVehicleROI(?int $vehicleId = null): array
    {
        $query = Vehicle::query();
        if ($vehicleId) {
            $query->where('id', $vehicleId);
        }

        return $query->get()->map(function (Vehicle $vehicle) {
            $revenue = $vehicle->trips()->where('status', TripStatus::Completed)->sum('revenue');
            $fuelCost = $vehicle->fuelLogs()->sum('total_cost');
            $expenseCost = $vehicle->expenses()->sum('amount');
            $maintenanceCost = $vehicle->maintenanceLogs()->sum('cost');
            $totalCost = $fuelCost + $expenseCost + $maintenanceCost;
            $acquisitionCost = (float) $vehicle->acquisition_cost;

            $roi = $acquisitionCost > 0
                ? round((($revenue - $totalCost) / $acquisitionCost) * 100, 2)
                : 0;

            return [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'registration_number' => $vehicle->registration_number,
                'total_revenue' => (float) $revenue,
                'total_cost' => (float) $totalCost,
                'acquisition_cost' => $acquisitionCost,
                'net_profit' => (float) ($revenue - $totalCost),
                'roi_percent' => $roi,
            ];
        })->toArray();
    }
}
