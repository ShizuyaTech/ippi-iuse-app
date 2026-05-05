<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfVendor
{
    /**
     * Redirect vendor users away from internal routes to their portal.
     * Apply this to all internal (non-vendor) authenticated route groups.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $user->loadRoleWithPermissions();

            if ($user->isVendor()) {
                return redirect()->route('vendor.dashboard');
            }
        }

        return $next($request);
    }
}
