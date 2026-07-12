<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    |
    | Each role has a slug, display name, description, and an array of
    | permissions. Use '*' for wildcard (super admin) access.
    |
    */

    'roles' => [
        [
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full system access with all permissions',
            'permissions' => ['*'],
        ],
        [
            'name' => 'Fleet Manager',
            'slug' => 'fleet-manager',
            'description' => 'Manages vehicles, drivers, maintenance, and reports',
            'permissions' => [
                'vehicles.*',
                'drivers.*',
                'maintenance.*',
                'fuel-logs.*',
                'expenses.*',
                'documents.*',
                'reports.*',
                'dashboard.view',
                'trips.view',
            ],
        ],
        [
            'name' => 'Dispatcher',
            'slug' => 'dispatcher',
            'description' => 'Creates and manages trip dispatches',
            'permissions' => [
                'trips.*',
                'vehicles.view',
                'vehicles.index',
                'drivers.view',
                'drivers.index',
                'dashboard.view',
            ],
        ],
        [
            'name' => 'Driver',
            'slug' => 'driver',
            'description' => 'Views assigned trips and marks them complete',
            'permissions' => [
                'trips.view-own',
                'trips.complete',
            ],
        ],
        [
            'name' => 'Accountant',
            'slug' => 'accountant',
            'description' => 'Manages fuel logs, expenses, and generates reports',
            'permissions' => [
                'fuel-logs.*',
                'expenses.*',
                'reports.*',
                'dashboard.view',
                'vehicles.view',
                'vehicles.index',
                'trips.view',
                'trips.index',
            ],
        ],
    ],
];
