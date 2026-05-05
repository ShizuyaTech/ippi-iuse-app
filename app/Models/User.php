<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'role_id', 'vendor_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relations ────────────────────────────────────────────────

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function userPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    // ── Role helpers ─────────────────────────────────────────────

    /** Check by role name (uses new role_id FK when available, falls back to old string) */
    public function hasRole(string $role): bool
    {
        if ($this->role_id && $this->relationLoaded('roleModel') && $this->roleModel) {
            return $this->roleModel->name === $role;
        }
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function isVendor(): bool
    {
        return $this->hasRole('vendor_admin') || $this->hasRole('vendor_user');
    }

    // ── Permission helpers ────────────────────────────────────────

    /**
     * Check if user has a given permission.
     * Priority: super_admin → role permissions → individual user permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check role-level permissions
        if ($this->role_id && $this->roleModel) {
            if ($this->roleModel->permissions->contains('name', $permission)) {
                return true;
            }
        }

        // Fallback: admin has all permissions
        if ($this->hasRole('admin')) {
            return true;
        }

        // Check individual user-level permissions (overrides / additions)
        if ($this->relationLoaded('userPermissions')) {
            return $this->userPermissions->contains('name', $permission);
        }

        return false;
    }

    /** Eager-load role + permissions + user-specific permissions (call once per request) */
    public function loadRoleWithPermissions(): static
    {
        return $this->loadMissing('roleModel.permissions', 'userPermissions');
    }
}
