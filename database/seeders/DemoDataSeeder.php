<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\ExpenseType;
use App\Enums\MaintenanceStatus;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\FuelLog;
use App\Models\MaintenanceLog;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ──
        $roles = Role::all()->keyBy('slug');

        $fleetManager1 = User::create(['name' => 'Rahul Sharma', 'email' => 'rahul@transitops.com', 'password' => bcrypt('password'), 'role_id' => $roles['fleet-manager']->id]);
        $fleetManager2 = User::create(['name' => 'Priya Patel', 'email' => 'priya@transitops.com', 'password' => bcrypt('password'), 'role_id' => $roles['fleet-manager']->id]);
        $dispatcher1 = User::create(['name' => 'Amit Singh', 'email' => 'amit@transitops.com', 'password' => bcrypt('password'), 'role_id' => $roles['dispatcher']->id]);
        $dispatcher2 = User::create(['name' => 'Neha Gupta', 'email' => 'neha@transitops.com', 'password' => bcrypt('password'), 'role_id' => $roles['dispatcher']->id]);
        $accountant = User::create(['name' => 'Vikram Joshi', 'email' => 'vikram@transitops.com', 'password' => bcrypt('password'), 'role_id' => $roles['accountant']->id]);

        $driverUsers = [];
        $driverNames = ['Ravi Kumar', 'Suresh Yadav', 'Manoj Tiwari', 'Deepak Verma', 'Karan Mehta', 'Sandeep Nair', 'Ajay Reddy', 'Prakash Das'];
        foreach ($driverNames as $name) {
            $driverUsers[] = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@transitops.com',
                'password' => bcrypt('password'),
                'role_id' => $roles['driver']->id,
            ]);
        }

        // ── Vehicles (10) ──
        $vehicles = [
            Vehicle::create(['registration_number' => 'MH-01-AB-1234', 'name' => 'Tata Ace Gold', 'model' => 'Ace Gold Plus', 'type' => 'Mini Truck', 'max_load_capacity_kg' => 750, 'odometer_km' => 45200, 'acquisition_cost' => 650000, 'status' => VehicleStatus::Available, 'region' => 'Mumbai']),
            Vehicle::create(['registration_number' => 'MH-02-CD-5678', 'name' => 'Ashok Leyland Dost', 'model' => 'Dost Plus', 'type' => 'LCV', 'max_load_capacity_kg' => 1500, 'odometer_km' => 78500, 'acquisition_cost' => 850000, 'status' => VehicleStatus::Available, 'region' => 'Pune']),
            Vehicle::create(['registration_number' => 'DL-03-EF-9012', 'name' => 'Mahindra Bolero Pickup', 'model' => 'Bolero Maxi Truck', 'type' => 'Pickup', 'max_load_capacity_kg' => 1250, 'odometer_km' => 62300, 'acquisition_cost' => 780000, 'status' => VehicleStatus::OnTrip, 'region' => 'Delhi']),
            Vehicle::create(['registration_number' => 'KA-04-GH-3456', 'name' => 'Eicher Pro 2049', 'model' => 'Pro 2049', 'type' => 'Medium Truck', 'max_load_capacity_kg' => 5000, 'odometer_km' => 120000, 'acquisition_cost' => 1500000, 'status' => VehicleStatus::Available, 'region' => 'Bangalore']),
            Vehicle::create(['registration_number' => 'TN-05-IJ-7890', 'name' => 'BharatBenz 1217C', 'model' => '1217C', 'type' => 'Heavy Truck', 'max_load_capacity_kg' => 12000, 'odometer_km' => 185000, 'acquisition_cost' => 2200000, 'status' => VehicleStatus::InShop, 'region' => 'Chennai']),
            Vehicle::create(['registration_number' => 'GJ-06-KL-2345', 'name' => 'Tata 407 Gold', 'model' => '407 Gold SFC', 'type' => 'Medium Truck', 'max_load_capacity_kg' => 3500, 'odometer_km' => 95600, 'acquisition_cost' => 1100000, 'status' => VehicleStatus::Available, 'region' => 'Ahmedabad']),
            Vehicle::create(['registration_number' => 'RJ-07-MN-6789', 'name' => 'Mahindra Furio 7', 'model' => 'Furio 7 16', 'type' => 'ICV', 'max_load_capacity_kg' => 7000, 'odometer_km' => 54000, 'acquisition_cost' => 1800000, 'status' => VehicleStatus::Available, 'region' => 'Jaipur']),
            Vehicle::create(['registration_number' => 'UP-08-OP-0123', 'name' => 'Ashok Leyland Boss', 'model' => 'Boss 1616', 'type' => 'Heavy Truck', 'max_load_capacity_kg' => 16000, 'odometer_km' => 210000, 'acquisition_cost' => 2500000, 'status' => VehicleStatus::OnTrip, 'region' => 'Lucknow']),
            Vehicle::create(['registration_number' => 'WB-09-QR-4567', 'name' => 'Tata Ultra T.16', 'model' => 'Ultra T.16 S', 'type' => 'ICV', 'max_load_capacity_kg' => 8500, 'odometer_km' => 72000, 'acquisition_cost' => 1600000, 'status' => VehicleStatus::Available, 'region' => 'Kolkata']),
            Vehicle::create(['registration_number' => 'MP-10-ST-8901', 'name' => 'Eicher Pro 3015', 'model' => 'Pro 3015', 'type' => 'Heavy Truck', 'max_load_capacity_kg' => 15000, 'odometer_km' => 145000, 'acquisition_cost' => 2100000, 'status' => VehicleStatus::Retired, 'region' => 'Bhopal']),
        ];

        // ── Drivers (8) ──
        $drivers = [
            Driver::create(['name' => 'Ravi Kumar', 'license_number' => 'DL-2020-0012345', 'license_category' => 'HMV', 'license_expiry_date' => now()->addMonths(8), 'contact_number' => '9876543210', 'safety_score' => 95.5, 'status' => DriverStatus::Available, 'user_id' => $driverUsers[0]->id]),
            Driver::create(['name' => 'Suresh Yadav', 'license_number' => 'UP-2019-0098765', 'license_category' => 'HMV', 'license_expiry_date' => now()->addDays(28), 'contact_number' => '9876543211', 'safety_score' => 88.0, 'status' => DriverStatus::OnTrip, 'user_id' => $driverUsers[1]->id]),
            Driver::create(['name' => 'Manoj Tiwari', 'license_number' => 'MH-2021-0056789', 'license_category' => 'LMV', 'license_expiry_date' => now()->addMonths(14), 'contact_number' => '9876543212', 'safety_score' => 92.0, 'status' => DriverStatus::Available, 'user_id' => $driverUsers[2]->id]),
            Driver::create(['name' => 'Deepak Verma', 'license_number' => 'DL-2018-0034567', 'license_category' => 'HMV', 'license_expiry_date' => now()->addDays(6), 'contact_number' => '9876543213', 'safety_score' => 78.5, 'status' => DriverStatus::Available, 'user_id' => $driverUsers[3]->id]),
            Driver::create(['name' => 'Karan Mehta', 'license_number' => 'GJ-2022-0023456', 'license_category' => 'HMV', 'license_expiry_date' => now()->addMonths(20), 'contact_number' => '9876543214', 'safety_score' => 97.0, 'status' => DriverStatus::Available, 'user_id' => $driverUsers[4]->id]),
            Driver::create(['name' => 'Sandeep Nair', 'license_number' => 'KA-2020-0045678', 'license_category' => 'LMV', 'license_expiry_date' => now()->addDays(14), 'contact_number' => '9876543215', 'safety_score' => 85.0, 'status' => DriverStatus::OffDuty, 'user_id' => $driverUsers[5]->id]),
            Driver::create(['name' => 'Ajay Reddy', 'license_number' => 'TN-2019-0067890', 'license_category' => 'HMV', 'license_expiry_date' => now()->subDays(10), 'contact_number' => '9876543216', 'safety_score' => 60.0, 'status' => DriverStatus::Suspended, 'user_id' => $driverUsers[6]->id]),
            Driver::create(['name' => 'Prakash Das', 'license_number' => 'WB-2021-0078901', 'license_category' => 'HMV', 'license_expiry_date' => now()->addMonths(6), 'contact_number' => '9876543217', 'safety_score' => 90.0, 'status' => DriverStatus::OnTrip, 'user_id' => $driverUsers[7]->id]),
        ];

        // ── Trips (15) ──
        // Active dispatched trips (matching On Trip vehicles/drivers)
        Trip::create(['source' => 'Delhi', 'destination' => 'Jaipur', 'vehicle_id' => $vehicles[2]->id, 'driver_id' => $drivers[1]->id, 'cargo_weight_kg' => 800, 'planned_distance_km' => 280, 'revenue' => 15000, 'status' => TripStatus::Dispatched, 'dispatched_at' => now()->subHours(6)]);
        Trip::create(['source' => 'Lucknow', 'destination' => 'Varanasi', 'vehicle_id' => $vehicles[7]->id, 'driver_id' => $drivers[7]->id, 'cargo_weight_kg' => 12000, 'planned_distance_km' => 320, 'revenue' => 35000, 'status' => TripStatus::Dispatched, 'dispatched_at' => now()->subHours(10)]);

        // Completed trips
        Trip::create(['source' => 'Mumbai', 'destination' => 'Pune', 'vehicle_id' => $vehicles[0]->id, 'driver_id' => $drivers[0]->id, 'cargo_weight_kg' => 500, 'planned_distance_km' => 150, 'actual_distance_km' => 155, 'fuel_consumed_liters' => 18, 'revenue' => 8000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(3), 'completed_at' => now()->subDays(3)->addHours(5)]);
        Trip::create(['source' => 'Pune', 'destination' => 'Nashik', 'vehicle_id' => $vehicles[1]->id, 'driver_id' => $drivers[2]->id, 'cargo_weight_kg' => 1200, 'planned_distance_km' => 210, 'actual_distance_km' => 215, 'fuel_consumed_liters' => 28, 'revenue' => 12000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(5), 'completed_at' => now()->subDays(5)->addHours(7)]);
        Trip::create(['source' => 'Bangalore', 'destination' => 'Mysore', 'vehicle_id' => $vehicles[3]->id, 'driver_id' => $drivers[4]->id, 'cargo_weight_kg' => 3500, 'planned_distance_km' => 150, 'actual_distance_km' => 148, 'fuel_consumed_liters' => 22, 'revenue' => 18000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(7), 'completed_at' => now()->subDays(7)->addHours(4)]);
        Trip::create(['source' => 'Ahmedabad', 'destination' => 'Vadodara', 'vehicle_id' => $vehicles[5]->id, 'driver_id' => $drivers[0]->id, 'cargo_weight_kg' => 2800, 'planned_distance_km' => 110, 'actual_distance_km' => 112, 'fuel_consumed_liters' => 16, 'revenue' => 9500, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(10), 'completed_at' => now()->subDays(10)->addHours(3)]);
        Trip::create(['source' => 'Jaipur', 'destination' => 'Udaipur', 'vehicle_id' => $vehicles[6]->id, 'driver_id' => $drivers[2]->id, 'cargo_weight_kg' => 5000, 'planned_distance_km' => 400, 'actual_distance_km' => 410, 'fuel_consumed_liters' => 55, 'revenue' => 28000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(12), 'completed_at' => now()->subDays(11)]);
        Trip::create(['source' => 'Kolkata', 'destination' => 'Siliguri', 'vehicle_id' => $vehicles[8]->id, 'driver_id' => $drivers[4]->id, 'cargo_weight_kg' => 7000, 'planned_distance_km' => 580, 'actual_distance_km' => 590, 'fuel_consumed_liters' => 75, 'revenue' => 42000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(15), 'completed_at' => now()->subDays(14)]);
        Trip::create(['source' => 'Chennai', 'destination' => 'Coimbatore', 'vehicle_id' => $vehicles[4]->id, 'driver_id' => $drivers[7]->id, 'cargo_weight_kg' => 10000, 'planned_distance_km' => 510, 'actual_distance_km' => 505, 'fuel_consumed_liters' => 68, 'revenue' => 38000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(20), 'completed_at' => now()->subDays(19)]);
        Trip::create(['source' => 'Mumbai', 'destination' => 'Goa', 'vehicle_id' => $vehicles[0]->id, 'driver_id' => $drivers[0]->id, 'cargo_weight_kg' => 600, 'planned_distance_km' => 590, 'actual_distance_km' => 600, 'fuel_consumed_liters' => 70, 'revenue' => 25000, 'status' => TripStatus::Completed, 'dispatched_at' => now()->subDays(25), 'completed_at' => now()->subDays(24)]);

        // Draft trips
        Trip::create(['source' => 'Delhi', 'destination' => 'Agra', 'vehicle_id' => $vehicles[3]->id, 'driver_id' => $drivers[0]->id, 'cargo_weight_kg' => 4000, 'planned_distance_km' => 230, 'revenue' => 16000, 'status' => TripStatus::Draft]);
        Trip::create(['source' => 'Mumbai', 'destination' => 'Surat', 'vehicle_id' => $vehicles[5]->id, 'driver_id' => $drivers[4]->id, 'cargo_weight_kg' => 3000, 'planned_distance_km' => 300, 'revenue' => 20000, 'status' => TripStatus::Draft]);

        // Cancelled trips
        Trip::create(['source' => 'Pune', 'destination' => 'Hyderabad', 'vehicle_id' => $vehicles[1]->id, 'driver_id' => $drivers[2]->id, 'cargo_weight_kg' => 1000, 'planned_distance_km' => 560, 'revenue' => 30000, 'status' => TripStatus::Cancelled, 'cancelled_at' => now()->subDays(2)]);
        Trip::create(['source' => 'Bangalore', 'destination' => 'Goa', 'vehicle_id' => $vehicles[8]->id, 'driver_id' => $drivers[5]->id, 'cargo_weight_kg' => 6000, 'planned_distance_km' => 560, 'revenue' => 32000, 'status' => TripStatus::Cancelled, 'cancelled_at' => now()->subDays(8)]);

        // ── Maintenance Logs ──
        MaintenanceLog::create(['vehicle_id' => $vehicles[4]->id, 'description' => 'Engine overhaul - piston rings replacement', 'cost' => 45000, 'start_date' => now()->subDays(3), 'status' => MaintenanceStatus::Open, 'notes' => 'Expected completion in 5 days']);
        MaintenanceLog::create(['vehicle_id' => $vehicles[0]->id, 'description' => 'Regular service - oil change, filter replacement', 'cost' => 5500, 'start_date' => now()->subDays(15), 'end_date' => now()->subDays(14), 'status' => MaintenanceStatus::Closed, 'notes' => 'Routine 10k km service']);
        MaintenanceLog::create(['vehicle_id' => $vehicles[3]->id, 'description' => 'Brake pad replacement and wheel alignment', 'cost' => 12000, 'start_date' => now()->subDays(20), 'end_date' => now()->subDays(19), 'status' => MaintenanceStatus::Closed]);
        MaintenanceLog::create(['vehicle_id' => $vehicles[7]->id, 'description' => 'Transmission fluid change', 'cost' => 8000, 'start_date' => now()->subDays(30), 'end_date' => now()->subDays(29), 'status' => MaintenanceStatus::Closed]);

        // ── Fuel Logs ──
        FuelLog::create(['vehicle_id' => $vehicles[0]->id, 'trip_id' => 3, 'liters' => 18, 'cost_per_liter' => 105.50, 'total_cost' => 1899, 'date' => now()->subDays(3), 'odometer_km' => 45200]);
        FuelLog::create(['vehicle_id' => $vehicles[1]->id, 'trip_id' => 4, 'liters' => 28, 'cost_per_liter' => 104.80, 'total_cost' => 2934.40, 'date' => now()->subDays(5), 'odometer_km' => 78500]);
        FuelLog::create(['vehicle_id' => $vehicles[3]->id, 'trip_id' => 5, 'liters' => 22, 'cost_per_liter' => 106.20, 'total_cost' => 2336.40, 'date' => now()->subDays(7), 'odometer_km' => 120000]);
        FuelLog::create(['vehicle_id' => $vehicles[5]->id, 'trip_id' => 6, 'liters' => 16, 'cost_per_liter' => 105.00, 'total_cost' => 1680, 'date' => now()->subDays(10), 'odometer_km' => 95600]);
        FuelLog::create(['vehicle_id' => $vehicles[6]->id, 'trip_id' => 7, 'liters' => 55, 'cost_per_liter' => 104.50, 'total_cost' => 5747.50, 'date' => now()->subDays(12), 'odometer_km' => 54000]);
        FuelLog::create(['vehicle_id' => $vehicles[8]->id, 'trip_id' => 8, 'liters' => 75, 'cost_per_liter' => 106.00, 'total_cost' => 7950, 'date' => now()->subDays(15), 'odometer_km' => 72000]);

        // ── Expenses ──
        Expense::create(['vehicle_id' => $vehicles[0]->id, 'trip_id' => 3, 'type' => ExpenseType::Tolls, 'amount' => 350, 'date' => now()->subDays(3), 'description' => 'Mumbai-Pune Expressway toll']);
        Expense::create(['vehicle_id' => $vehicles[3]->id, 'trip_id' => 5, 'type' => ExpenseType::Tolls, 'amount' => 280, 'date' => now()->subDays(7), 'description' => 'Bangalore-Mysore highway toll']);
        Expense::create(['vehicle_id' => $vehicles[6]->id, 'trip_id' => 7, 'type' => ExpenseType::Tolls, 'amount' => 850, 'date' => now()->subDays(12), 'description' => 'NH-48 multiple toll plazas']);
        Expense::create(['vehicle_id' => $vehicles[4]->id, 'type' => ExpenseType::Repairs, 'amount' => 45000, 'date' => now()->subDays(3), 'description' => 'Engine overhaul parts cost']);
        Expense::create(['vehicle_id' => $vehicles[0]->id, 'type' => ExpenseType::Fines, 'amount' => 2000, 'date' => now()->subDays(18), 'description' => 'Overloading fine - Pune checkpoint']);

        // ── Vehicle Documents ──
        VehicleDocument::create(['vehicle_id' => $vehicles[0]->id, 'document_type' => 'Insurance', 'file_path' => 'vehicle-documents/insurance_mh01ab1234.pdf', 'issue_date' => now()->subMonths(6), 'expiry_date' => now()->addMonths(6)]);
        VehicleDocument::create(['vehicle_id' => $vehicles[0]->id, 'document_type' => 'Registration', 'file_path' => 'vehicle-documents/rc_mh01ab1234.pdf', 'issue_date' => now()->subYears(2), 'expiry_date' => now()->addYears(13)]);
        VehicleDocument::create(['vehicle_id' => $vehicles[1]->id, 'document_type' => 'Insurance', 'file_path' => 'vehicle-documents/insurance_mh02cd5678.pdf', 'issue_date' => now()->subMonths(9), 'expiry_date' => now()->addDays(20)]);
        VehicleDocument::create(['vehicle_id' => $vehicles[3]->id, 'document_type' => 'Fitness Certificate', 'file_path' => 'vehicle-documents/fitness_ka04gh3456.pdf', 'issue_date' => now()->subMonths(10), 'expiry_date' => now()->addMonths(2)]);
        VehicleDocument::create(['vehicle_id' => $vehicles[4]->id, 'document_type' => 'Insurance', 'file_path' => 'vehicle-documents/insurance_tn05ij7890.pdf', 'issue_date' => now()->subMonths(11), 'expiry_date' => now()->subDays(5)]);
    }
}
