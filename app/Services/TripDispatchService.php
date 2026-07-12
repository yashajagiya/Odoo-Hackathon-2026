<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripDispatchService
{
    /**
     * Dispatch a trip: validate business rules and atomically set statuses.
     *
     * @throws ValidationException
     */
    public function dispatch(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip) {
            // Lock vehicle and driver rows to prevent race conditions
            $vehicle = $trip->vehicle()->lockForUpdate()->first();
            $driver = $trip->driver()->lockForUpdate()->first();

            // Validate trip is in Draft status
            if ($trip->status !== TripStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => ['Trip must be in Draft status to dispatch. Current status: ' . $trip->status->value],
                ]);
            }

            // Validate vehicle is available
            if ($vehicle->status !== VehicleStatus::Available) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ['Vehicle "' . $vehicle->name . '" is not available. Current status: ' . $vehicle->status->value],
                ]);
            }

            // Validate driver is available
            if ($driver->status !== DriverStatus::Available) {
                throw ValidationException::withMessages([
                    'driver_id' => ['Driver "' . $driver->name . '" is not available. Current status: ' . $driver->status->value],
                ]);
            }

            // Validate driver license is not expired
            if ($driver->license_expiry_date && $driver->license_expiry_date->isPast()) {
                throw ValidationException::withMessages([
                    'driver_id' => ['Driver "' . $driver->name . '" has an expired license (expired: ' . $driver->license_expiry_date->toDateString() . ').'],
                ]);
            }

            // Validate driver is not suspended
            if ($driver->status === DriverStatus::Suspended) {
                throw ValidationException::withMessages([
                    'driver_id' => ['Driver "' . $driver->name . '" is suspended and cannot be assigned to trips.'],
                ]);
            }

            // Validate cargo weight does not exceed vehicle capacity
            if ($trip->cargo_weight_kg > $vehicle->max_load_capacity_kg && $vehicle->max_load_capacity_kg > 0) {
                throw ValidationException::withMessages([
                    'cargo_weight_kg' => [
                        'Cargo weight (' . $trip->cargo_weight_kg . ' kg) exceeds vehicle capacity (' . $vehicle->max_load_capacity_kg . ' kg).',
                    ],
                ]);
            }

            // All validations passed — set statuses atomically
            $trip->update([
                'status' => TripStatus::Dispatched,
                'dispatched_at' => now(),
            ]);

            $vehicle->update(['status' => VehicleStatus::OnTrip]);
            $driver->update(['status' => DriverStatus::OnTrip]);

            return $trip->fresh(['vehicle', 'driver']);
        });
    }

    /**
     * Complete a trip: capture final data and revert statuses.
     *
     * @param  array  $completionData  ['actual_distance_km', 'fuel_consumed_liters', 'revenue']
     * @throws ValidationException
     */
    public function complete(Trip $trip, array $completionData): Trip
    {
        return DB::transaction(function () use ($trip, $completionData) {
            $vehicle = $trip->vehicle()->lockForUpdate()->first();
            $driver = $trip->driver()->lockForUpdate()->first();

            // Validate trip is in Dispatched status
            if ($trip->status !== TripStatus::Dispatched) {
                throw ValidationException::withMessages([
                    'status' => ['Trip must be in Dispatched status to complete. Current status: ' . $trip->status->value],
                ]);
            }

            // Update trip with completion data
            $trip->update([
                'status' => TripStatus::Completed,
                'actual_distance_km' => $completionData['actual_distance_km'] ?? $trip->planned_distance_km,
                'fuel_consumed_liters' => $completionData['fuel_consumed_liters'] ?? null,
                'revenue' => $completionData['revenue'] ?? $trip->revenue,
                'completed_at' => now(),
            ]);

            // Update vehicle odometer
            $actualDistance = $completionData['actual_distance_km'] ?? 0;
            $vehicle->update([
                'status' => VehicleStatus::Available,
                'odometer_km' => $vehicle->odometer_km + $actualDistance,
            ]);

            // Revert driver status
            $driver->update(['status' => DriverStatus::Available]);

            return $trip->fresh(['vehicle', 'driver']);
        });
    }

    /**
     * Cancel a trip: revert vehicle and driver statuses.
     *
     * @throws ValidationException
     */
    public function cancel(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip) {
            $vehicle = $trip->vehicle()->lockForUpdate()->first();
            $driver = $trip->driver()->lockForUpdate()->first();

            // Can cancel from Draft or Dispatched
            if (!in_array($trip->status, [TripStatus::Draft, TripStatus::Dispatched])) {
                throw ValidationException::withMessages([
                    'status' => ['Trip cannot be cancelled. Current status: ' . $trip->status->value],
                ]);
            }

            // If trip was dispatched, revert vehicle and driver statuses
            if ($trip->status === TripStatus::Dispatched) {
                $vehicle->update(['status' => VehicleStatus::Available]);
                $driver->update(['status' => DriverStatus::Available]);
            }

            $trip->update([
                'status' => TripStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $trip->fresh(['vehicle', 'driver']);
        });
    }
}
