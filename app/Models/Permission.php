<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'module', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Return permissions grouped as: module → feature → sorted Collection
     * Used by role/user permission assignment forms.
     */
    public static function groupedForForm(): \Illuminate\Support\Collection
    {
        $actionOrder = ['view', 'movements', 'create', 'edit', 'delete',
                        'approve', 'cancel', 'release', 'confirm',
                        'export', 'generate_po', 'run', 'demands'];

        return static::orderBy('module')->orderBy('name')->get()
            ->groupBy('module')
            ->map(fn($modulePerms) =>
                $modulePerms
                    ->groupBy(fn($p) => explode('.', $p->name)[1] ?? 'other')
                    ->map(fn($featurePerms) =>
                        $featurePerms->sortBy(function ($p) use ($actionOrder) {
                            $action = explode('.', $p->name)[2] ?? '';
                            $idx = array_search($action, $actionOrder);
                            return $idx !== false ? $idx : 99;
                        })->values()
                    )
            );
    }
}
