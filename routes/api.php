<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FuelLogController;
use App\Http\Controllers\Api\MaintenanceLogController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| TransitOps REST API routes.
| All routes except login/register require Sanctum authentication.
|
*/

// ── Public Routes ──
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ── Protected Routes ──
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Roles & Users
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);

    // Vehicles
    Route::apiResource('vehicles', VehicleController::class);

    // Drivers
    Route::apiResource('drivers', DriverController::class);

    // Trips
    Route::apiResource('trips', TripController::class);
    Route::post('/trips/{trip}/dispatch', [TripController::class, 'dispatch'])->name('trips.dispatch');
    Route::post('/trips/{trip}/complete', [TripController::class, 'complete'])->name('trips.complete');
    Route::post('/trips/{trip}/cancel', [TripController::class, 'cancel'])->name('trips.cancel');

    // Maintenance Logs
    Route::apiResource('maintenance-logs', MaintenanceLogController::class);
    Route::post('/maintenance-logs/{maintenance_log}/close', [MaintenanceLogController::class, 'close'])->name('maintenance-logs.close');

    // Fuel Logs
    Route::apiResource('fuel-logs', FuelLogController::class);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Vehicle Documents
    Route::apiResource('vehicle-documents', VehicleDocumentController::class);
    Route::get('/vehicle-documents/{vehicle_document}/download', [VehicleDocumentController::class, 'download'])->name('vehicle-documents.download');

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/fuel-efficiency', [ReportController::class, 'fuelEfficiency']);
        Route::get('/fleet-utilization', [ReportController::class, 'fleetUtilization']);
        Route::get('/operational-cost', [ReportController::class, 'operationalCost']);
        Route::get('/vehicle-roi', [ReportController::class, 'vehicleROI']);
        Route::get('/export/csv', [ReportController::class, 'exportCsv']);
    });
});
