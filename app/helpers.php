<?php

use Carbon\Carbon;

if (! function_exists('user_now')) {
    /**
     * Return current Carbon timestamp in the authenticated user's timezone.
     * Falls back to app timezone (Asia/Jakarta / WIB) when no user is logged in.
     */
    function user_now(): Carbon
    {
        $tz = auth()->check() ? (auth()->user()->timezone ?? 'Asia/Jakarta') : config('app.timezone', 'Asia/Jakarta');
        return Carbon::now($tz);
    }
}

if (! function_exists('user_tz_label')) {
    /**
     * Return the short timezone label (WIB / WITA / WIT) for the current user.
     */
    function user_tz_label(): string
    {
        if (! auth()->check()) {
            return 'WIB';
        }
        return auth()->user()->tzLabel();
    }
}
