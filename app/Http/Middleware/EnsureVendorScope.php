<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorScope
{
    /**
     * Ensures a vendor user has a vendor_id assigned.
     * Shares the vendor_id to all views via View::share so controllers
     * can apply ->when(auth()->user()->isVendor(), fn($q) => $q->where('vendor_id', vendorId())) etc.
     *
     * Usage: apply to vendor-accessible routes only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isVendor()) {
            if (! $user->vendor_id) {
                abort(403, 'Akun vendor Anda belum dikaitkan dengan vendor manapun. Hubungi administrator.');
            }

            // Share vendor_id to all views so Blade can use $currentVendorId
            view()->share('currentVendorId', $user->vendor_id);
            view()->share('currentVendor',   $user->vendor);
        } elseif ($user) {
            // Internal IPPI staff accessing vendor portal as monitor — no vendor scoping
            view()->share('currentVendorId', null);
            view()->share('currentVendor',   null);
        }

        return $next($request);
    }
}

