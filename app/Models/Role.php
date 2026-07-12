<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'permissions',
        'description',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get all users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        // Wildcard permission (Super Admin)
        if (in_array('*', $permissions)) {
            return true;
        }

        // Exact match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Wildcard module match (e.g., 'vehicles.*' matches 'vehicles.create')
        $parts = explode('.', $permission);
        if (count($parts) >= 2) {
            $moduleWildcard = $parts[0] . '.*';
            if (in_array($moduleWildcard, $permissions)) {
                return true;
            }
        }

        return false;
    }
}
