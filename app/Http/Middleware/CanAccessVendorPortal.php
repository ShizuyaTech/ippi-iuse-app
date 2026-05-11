<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows access to vendor portal routes for:
 *  - Actual vendor users (role: vendor_admin / vendor_user)
 *  - Internal IPPI staff who have the 'vendor.portal.access' permission
 */
class CanAccessVendorPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->loadRoleWithPermissions();

        if ($user->isVendor() || $user->hasPermission('vendor.portal.access')) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke portal vendor.');
    }
}
