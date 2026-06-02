<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically enforces permission checks on mm.* and pp.* routes
 * by deriving the required permission from the route name.
 *
 * Pattern:
 *   Route name   : mm.purchase-orders.create
 *   Permission   : mm.purchase_orders.create
 *
 * admin / super_admin bypass all checks (see User::hasPermission).
 */
class RoutePermissionGuard
{
    /**
     * Explicit route-name → required permission.
     * Takes priority over the pattern-based derivation below.
     */
    private const EXPLICIT = [
        // MM – Stocks (special actions)
        'mm.stocks.export'                    => 'mm.stocks.export',
        'mm.stocks.export-pdf'                => 'mm.stocks.export',
        'mm.stocks.movements'                 => 'mm.stocks.movements',

        // MM – SKM (non-resource actions)
        'mm.skm.excel'                        => 'mm.skm.view',
        'mm.skm.pdf'                          => 'mm.skm.view',
        'mm.skm.status'                       => 'mm.skm.edit',
        'mm.skm.generate-po'                  => 'mm.skm.generate_po',
        'mm.skm.demands.template'             => 'mm.skm.view',
        'mm.skm.demands.import'               => 'mm.skm.edit',
        'mm.skm.demands.clear'                => 'mm.skm.edit',

        // MM – Purchase Orders (special actions)
        'mm.purchase-orders.approve'          => 'mm.purchase_orders.approve',
        'mm.purchase-orders.cancel'           => 'mm.purchase_orders.cancel',
        'mm.purchase-orders.pdf'              => 'mm.purchase_orders.view',
        'mm.purchase-orders.import-excel'     => 'mm.purchase_orders.create',
        'mm.purchase-orders.import-create'    => 'mm.purchase_orders.create',
        'mm.purchase-orders.import-template'  => 'mm.purchase_orders.view',
        'mm.purchase-orders.export'           => 'mm.purchase_orders.view',
        'mm.purchase-orders.export-pdf'       => 'mm.purchase_orders.view',

        // MM – Goods Receipts
        'mm.goods-receipts.export'            => 'mm.goods_receipts.view',
        'mm.goods-receipts.export-pdf'        => 'mm.goods_receipts.view',

        // MM – Goods Issues (special actions)
        'mm.goods-issues.pdf'                 => 'mm.goods_issues.view',
        'mm.goods-issues.excel'               => 'mm.goods_issues.view',
        'mm.goods-issues.export'              => 'mm.goods_issues.view',
        'mm.goods-issues.export-pdf'          => 'mm.goods_issues.view',

        // MM – Delivery Notes (special actions)
        'mm.delivery-notes.confirm'           => 'mm.delivery_notes.confirm',
        'mm.delivery-notes.receive'           => 'mm.delivery_notes.receive',
        'mm.delivery-notes.update-qty'        => 'mm.delivery_notes.edit',
        'mm.delivery-notes.export'            => 'mm.delivery_notes.export',

        // MM – Vendor Deliveries
        'mm.vendor-deliveries.view'           => 'mm.vendor_deliveries.view',

        // MM – Business Event Logs
        'mm.business-event-logs.export'       => 'mm.business_event_logs.view',

        // MM – Materials
        'mm.materials.export'                 => 'mm.materials.view',
        'mm.materials.export-pdf'             => 'mm.materials.view',
        'mm.materials.template'               => 'mm.materials.view',
        'mm.materials.import'                 => 'mm.materials.create',

        // MM – Storage Locations
        'mm.storage-locations.export'         => 'mm.storage_locations.view',
        'mm.storage-locations.export-pdf'     => 'mm.storage_locations.view',
        'mm.storage-locations.template'       => 'mm.storage_locations.view',
        'mm.storage-locations.import'         => 'mm.storage_locations.create',

        // MM – Vendors
        'mm.vendors.export'                   => 'mm.vendors.view',
        'mm.vendors.export-pdf'               => 'mm.vendors.view',
        'mm.vendors.template'                 => 'mm.vendors.view',
        'mm.vendors.import'                   => 'mm.vendors.create',

        // MM – Customers
        'mm.customers.export'                 => 'mm.customers.view',
        'mm.customers.export-pdf'             => 'mm.customers.view',
        'mm.customers.template'               => 'mm.customers.view',
        'mm.customers.import'                 => 'mm.customers.create',

        // PP – Work Centers
        'pp.work-centers.export'              => 'pp.work_centers.view',
        'pp.work-centers.export-pdf'          => 'pp.work_centers.view',
        'pp.work-centers.import-template'     => 'pp.work_centers.view',
        'pp.work-centers.import'              => 'pp.work_centers.create',

        // PP – BOMs
        'pp.boms.export'                      => 'pp.boms.view',
        'pp.boms.export-pdf'                  => 'pp.boms.view',
        'pp.boms.import-template'             => 'pp.boms.view',
        'pp.boms.import'                      => 'pp.boms.create',

        // PP – Routings
        'pp.routings.export'                  => 'pp.routings.view',
        'pp.routings.export-pdf'              => 'pp.routings.view',
        'pp.routings.import-template'         => 'pp.routings.view',
        'pp.routings.import'                  => 'pp.routings.create',

        // PP – Production Orders (special actions)
        'pp.production-orders.export-pdf'     => 'pp.production_orders.view',
        'pp.production-orders.print'          => 'pp.production_orders.view',
        'pp.production-orders.release'        => 'pp.production_orders.release',
        'pp.production-orders.bulk-release'   => 'pp.production_orders.release',
        'pp.production-orders.goods-issue'    => 'pp.production_orders.confirm',
        'pp.production-orders.confirm'        => 'pp.production_orders.confirm',

        // PP – MRP (special actions)
        'pp.mrp.run'                          => 'pp.mrp.run',
        'pp.mrp.export-pdf'                   => 'pp.mrp.view',
        'pp.mrp.excel'                        => 'pp.mrp.view',
        'pp.mrp.pdf'                          => 'pp.mrp.view',
        'pp.mrp.demands.template'             => 'pp.mrp.view',
        'pp.mrp.demands.import'               => 'pp.mrp.demands',
        'pp.mrp.demands.clear'                => 'pp.mrp.demands',
        'pp.mrp.demands.destroy'              => 'pp.mrp.demands',
    ];

    /**
     * Route name prefix → permission name prefix.
     * Route names use hyphens; permissions use underscores.
     */
    private const PREFIX_MAP = [
        'mm.storage-locations' => 'mm.storage_locations',
        'mm.materials'         => 'mm.materials',
        'mm.vendors'           => 'mm.vendors',
        'mm.customers'         => 'mm.customers',
        'mm.purchase-orders'   => 'mm.purchase_orders',
        'mm.goods-receipts'    => 'mm.goods_receipts',
        'mm.goods-issues'      => 'mm.goods_issues',
        'mm.stocks'            => 'mm.stocks',
        'mm.skm'               => 'mm.skm',
        'mm.delivery-notes'    => 'mm.delivery_notes',
        'mm.vendor-deliveries' => 'mm.vendor_deliveries',
        'mm.business-event-logs' => 'mm.business_event_logs',
        'pp.work-centers'      => 'pp.work_centers',
        'pp.boms'              => 'pp.boms',
        'pp.routings'          => 'pp.routings',
        'pp.production-orders' => 'pp.production_orders',
        'pp.mrp'               => 'pp.mrp',
    ];

    /**
     * Standard Laravel resource action suffix → permission action suffix.
     */
    private const ACTION_MAP = [
        'index'   => 'view',
        'show'    => 'view',
        'create'  => 'create',
        'store'   => 'create',
        'edit'    => 'edit',
        'update'  => 'edit',
        'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->loadRoleWithPermissions();

        $routeName = $request->route()?->getName() ?? '';

        // 1. Explicit map takes priority
        if (array_key_exists($routeName, self::EXPLICIT)) {
            $required = self::EXPLICIT[$routeName];
            if (! $user->hasPermission($required)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        }

        // 2. Pattern-based derivation for standard resource routes
        foreach (self::PREFIX_MAP as $routePrefix => $permPrefix) {
            if (str_starts_with($routeName, $routePrefix . '.')) {
                $suffix = substr($routeName, strlen($routePrefix) + 1);
                $action = self::ACTION_MAP[$suffix] ?? null;

                if ($action !== null) {
                    $required = $permPrefix . '.' . $action;
                    if (! $user->hasPermission($required)) {
                        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
                    }
                }
                // Suffix not in ACTION_MAP but prefix matched → already-authed, let through
                return $next($request);
            }
        }

        // 3. No mapping found — route is already auth-protected, let through
        return $next($request);
    }
}
