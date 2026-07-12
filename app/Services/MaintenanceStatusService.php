<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\VehicleStatus;
use App\Models\MaintenanceLog;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceStatusService
{
    /**
     * Open a maintenance log and set vehicle to In Shop.
     *
     * @throws ValidationException
     */
    public function openMaintenance(array $data): MaintenanceLog
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::lockForUpdate()->findOrFail($data['vehicle_id']);

            // Validate vehicle is not on an active trip
            if ($vehicle->status === VehicleStatus::OnTrip) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ['Vehicle "' . $vehicle->name . '" is currently on a trip and cannot be put in maintenance.'],
                ]);
            }

            // Validate vehicle is not retired
            if ($vehicle->status === VehicleStatus::Retired) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ['Vehicle "' . $vehicle->name . '" is retired and cannot be put in maintenance.'],
                ]);
            }

            // Create the maintenance log
            $log = MaintenanceLog::create([
                'vehicle_id' => $data['vehicle_id'],
                'description' => $data['description'],
                'cost' => $data['cost'] ?? 0,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? null,
                'status' => MaintenanceStatus::Open,
                'notes' => $data['notes'] ?? null,
            ]);

            // Set vehicle to In Shop
            $vehicle->update(['status' => VehicleStatus::InShop]);

            return $log->fresh('vehicle');
        });
    }

    /**
     * Close a maintenance log and restore vehicle to Available (unless Retired).
     *
     * @throws ValidationException
     */
    public function closeMaintenance(MaintenanceLog $log): MaintenanceLog
    {
        return DB::transaction(function () use ($log) {
            if ($log->status !== MaintenanceStatus::Open) {
                throw ValidationException::withMessages([
                    'status' => ['Maintenance log is already closed.'],
                ]);
            }

            $vehicle = Vehicle::lockForUpdate()->findOrFail($log->vehicle_id);

            // Close the maintenance log
            $log->update([
                'status' => MaintenanceStatus::Closed,
                'end_date' => $log->end_date ?? now()->toDateString(),
            ]);

            // Restore vehicle status unless it's Retired
            if ($vehicle->status !== VehicleStatus::Retired) {
                // Check if there are other open maintenance logs for this vehicle
                $otherOpenLogs = MaintenanceLog::where('vehicle_id', $vehicle->id)
                    ->where('id', '!=', $log->id)
                    ->where('status', MaintenanceStatus::Open)
                    ->exists();

                if (!$otherOpenLogs) {
                    $vehicle->update(['status' => VehicleStatus::Available]);
                }
            }

            return $log->fresh('vehicle');
        });
    }
}
