<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Permissions ─────────────────────────────────────────
        $defs = [
            // SAP MM – Material Master
            ['name' => 'mm.materials.view',              'display_name' => 'Lihat Master Material',         'module' => 'MM'],
            ['name' => 'mm.materials.create',            'display_name' => 'Buat Master Material',          'module' => 'MM'],
            ['name' => 'mm.materials.edit',              'display_name' => 'Edit Master Material',          'module' => 'MM'],
            ['name' => 'mm.materials.delete',            'display_name' => 'Hapus Master Material',         'module' => 'MM'],
            // MM – Vendor
            ['name' => 'mm.vendors.view',                'display_name' => 'Lihat Vendor',                  'module' => 'MM'],
            ['name' => 'mm.vendors.create',              'display_name' => 'Buat Vendor',                   'module' => 'MM'],
            ['name' => 'mm.vendors.edit',                'display_name' => 'Edit Vendor',                   'module' => 'MM'],
            ['name' => 'mm.vendors.delete',              'display_name' => 'Hapus Vendor',                  'module' => 'MM'],
            // MM – Customer
            ['name' => 'mm.customers.view',              'display_name' => 'Lihat Customer',                'module' => 'MM'],
            ['name' => 'mm.customers.create',            'display_name' => 'Buat Customer',                 'module' => 'MM'],
            ['name' => 'mm.customers.edit',              'display_name' => 'Edit Customer',                 'module' => 'MM'],
            ['name' => 'mm.customers.delete',            'display_name' => 'Hapus Customer',                'module' => 'MM'],
            // MM – Storage Location
            ['name' => 'mm.storage_locations.view',      'display_name' => 'Lihat Storage Location',        'module' => 'MM'],
            ['name' => 'mm.storage_locations.create',    'display_name' => 'Buat Storage Location',         'module' => 'MM'],
            ['name' => 'mm.storage_locations.edit',      'display_name' => 'Edit Storage Location',         'module' => 'MM'],
            ['name' => 'mm.storage_locations.delete',    'display_name' => 'Hapus Storage Location',        'module' => 'MM'],
            // MM – Purchase Order
            ['name' => 'mm.purchase_orders.view',        'display_name' => 'Lihat Purchase Order',          'module' => 'MM'],
            ['name' => 'mm.purchase_orders.create',      'display_name' => 'Buat Purchase Order',           'module' => 'MM'],
            ['name' => 'mm.purchase_orders.edit',        'display_name' => 'Edit Purchase Order',           'module' => 'MM'],
            ['name' => 'mm.purchase_orders.approve',     'display_name' => 'Approve Purchase Order',        'module' => 'MM'],
            ['name' => 'mm.purchase_orders.cancel',      'display_name' => 'Cancel Purchase Order',         'module' => 'MM'],
            ['name' => 'mm.purchase_orders.delete',      'display_name' => 'Hapus Purchase Order',          'module' => 'MM'],
            // MM – Goods Receipt
            ['name' => 'mm.goods_receipts.view',         'display_name' => 'Lihat Goods Receipt',           'module' => 'MM'],
            ['name' => 'mm.goods_receipts.create',       'display_name' => 'Buat Goods Receipt',            'module' => 'MM'],
            ['name' => 'mm.goods_receipts.edit',         'display_name' => 'Edit Goods Receipt',            'module' => 'MM'],
            ['name' => 'mm.goods_receipts.delete',       'display_name' => 'Hapus Goods Receipt',           'module' => 'MM'],
            // MM – Goods Issue
            ['name' => 'mm.goods_issues.view',           'display_name' => 'Lihat Goods Issue',             'module' => 'MM'],
            ['name' => 'mm.goods_issues.create',         'display_name' => 'Buat Goods Issue',              'module' => 'MM'],
            ['name' => 'mm.goods_issues.edit',           'display_name' => 'Edit Goods Issue',              'module' => 'MM'],
            ['name' => 'mm.goods_issues.delete',         'display_name' => 'Hapus Goods Issue',             'module' => 'MM'],
            // MM – Stock
            ['name' => 'mm.stocks.view',                 'display_name' => 'Lihat Stock Overview',          'module' => 'MM'],
            ['name' => 'mm.stocks.movements',            'display_name' => 'Lihat Stock Movements',         'module' => 'MM'],
            ['name' => 'mm.stocks.export',               'display_name' => 'Export Stock',                  'module' => 'MM'],
            // MM – SKM (Summary Kanban Material)
            ['name' => 'mm.skm.view',                    'display_name' => 'Lihat Summary Kanban',          'module' => 'MM'],
            ['name' => 'mm.skm.create',                  'display_name' => 'Buat Summary Kanban',           'module' => 'MM'],
            ['name' => 'mm.skm.edit',                    'display_name' => 'Edit Summary Kanban',           'module' => 'MM'],
            ['name' => 'mm.skm.delete',                  'display_name' => 'Hapus Summary Kanban',          'module' => 'MM'],
            ['name' => 'mm.skm.generate_po',             'display_name' => 'Generate PO dari SKM',          'module' => 'MM'],
            // MM – Delivery Note (internal)
            ['name' => 'mm.delivery_notes.view',         'display_name' => 'Lihat Delivery Note',           'module' => 'MM'],
            ['name' => 'mm.delivery_notes.edit',         'display_name' => 'Edit Delivery Note',            'module' => 'MM'],
            ['name' => 'mm.delivery_notes.confirm',      'display_name' => 'Konfirmasi Delivery Note',      'module' => 'MM'],
            ['name' => 'mm.delivery_notes.receive',      'display_name' => 'Terima Delivery Note',          'module' => 'MM'],
            ['name' => 'mm.delivery_notes.export',       'display_name' => 'Export Delivery Note',          'module' => 'MM'],
            // MM – Vendor Delivery
            ['name' => 'mm.vendor_deliveries.view',      'display_name' => 'Lihat Vendor Delivery',         'module' => 'MM'],
            ['name' => 'mm.vendor_deliveries.create',    'display_name' => 'Buat Vendor Delivery',          'module' => 'MM'],
            // MM – Business Event Log
            ['name' => 'mm.business_event_logs.view',    'display_name' => 'Lihat Business Event Log',      'module' => 'MM'],
            // SAP PP – Work Center
            ['name' => 'pp.work_centers.view',           'display_name' => 'Lihat Work Center',             'module' => 'PP'],
            ['name' => 'pp.work_centers.create',         'display_name' => 'Buat Work Center',              'module' => 'PP'],
            ['name' => 'pp.work_centers.edit',           'display_name' => 'Edit Work Center',              'module' => 'PP'],
            ['name' => 'pp.work_centers.delete',         'display_name' => 'Hapus Work Center',             'module' => 'PP'],
            // PP – BOM
            ['name' => 'pp.boms.view',                   'display_name' => 'Lihat BOM',                     'module' => 'PP'],
            ['name' => 'pp.boms.create',                 'display_name' => 'Buat BOM',                      'module' => 'PP'],
            ['name' => 'pp.boms.edit',                   'display_name' => 'Edit BOM',                      'module' => 'PP'],
            ['name' => 'pp.boms.delete',                 'display_name' => 'Hapus BOM',                     'module' => 'PP'],
            // PP – Routing
            ['name' => 'pp.routings.view',               'display_name' => 'Lihat Routing',                 'module' => 'PP'],
            ['name' => 'pp.routings.create',             'display_name' => 'Buat Routing',                  'module' => 'PP'],
            ['name' => 'pp.routings.edit',               'display_name' => 'Edit Routing',                  'module' => 'PP'],
            ['name' => 'pp.routings.delete',             'display_name' => 'Hapus Routing',                 'module' => 'PP'],
            // PP – Production Order
            ['name' => 'pp.production_orders.view',      'display_name' => 'Lihat Production Order',        'module' => 'PP'],
            ['name' => 'pp.production_orders.create',    'display_name' => 'Buat Production Order',         'module' => 'PP'],
            ['name' => 'pp.production_orders.edit',      'display_name' => 'Edit Production Order',         'module' => 'PP'],
            ['name' => 'pp.production_orders.release',   'display_name' => 'Release Production Order',      'module' => 'PP'],
            ['name' => 'pp.production_orders.confirm',   'display_name' => 'Konfirmasi Production Order',   'module' => 'PP'],
            ['name' => 'pp.production_orders.delete',    'display_name' => 'Hapus Production Order',        'module' => 'PP'],
            // PP – MRP
            ['name' => 'pp.mrp.view',                    'display_name' => 'Lihat MRP',                     'module' => 'PP'],
            ['name' => 'pp.mrp.run',                     'display_name' => 'Jalankan MRP',                  'module' => 'PP'],
            ['name' => 'pp.mrp.demands',                 'display_name' => 'Kelola MRP Demand',             'module' => 'PP'],
            ['name' => 'pp.mrp.delete',                  'display_name' => 'Hapus MRP Run',                 'module' => 'PP'],
        ];

        foreach ($defs as $def) {
            Permission::firstOrCreate(['name' => $def['name']], $def);
        }

        // ── 2. System Roles ────────────────────────────────────────
        $roles = [
            [
                'name' => 'super_admin', 'display_name' => 'Super Admin',
                'description' => 'Akses penuh ke semua modul dan konfigurasi sistem.', 'is_system' => true,
                'permissions' => [], // super_admin bypasses all checks in User::hasPermission()
            ],
            [
                'name' => 'admin', 'display_name' => 'Admin',
                'description' => 'Akses penuh ke semua modul SAP MM dan PP.', 'is_system' => true,
                'permissions' => Permission::all()->pluck('id')->toArray(),
            ],
            [
                'name' => 'planner', 'display_name' => 'Planner',
                'description' => 'Akses modul PP: BOM, Routing, Production Order, MRP.', 'is_system' => true,
                'permissions' => Permission::where('module', 'PP')
                    ->orWhereIn('name', [
                        'mm.stocks.view',
                        'mm.delivery_notes.view',
                        'mm.business_event_logs.view',
                    ])
                    ->pluck('id')->toArray(),
            ],
            [
                'name' => 'purchasing', 'display_name' => 'Purchasing',
                'description' => 'Akses Purchase Order, Goods Receipt, Vendor, SKM, Delivery Note.', 'is_system' => true,
                'permissions' => Permission::whereIn('name', [
                    'mm.vendors.view', 'mm.vendors.create', 'mm.vendors.edit',
                    'mm.materials.view',
                    'mm.purchase_orders.view', 'mm.purchase_orders.create', 'mm.purchase_orders.edit',
                    'mm.purchase_orders.approve', 'mm.purchase_orders.cancel',
                    'mm.goods_receipts.view', 'mm.goods_receipts.create', 'mm.goods_receipts.edit',
                    'mm.stocks.view',
                    'mm.skm.view', 'mm.skm.create', 'mm.skm.edit', 'mm.skm.delete', 'mm.skm.generate_po',
                    'mm.delivery_notes.view', 'mm.delivery_notes.confirm', 'mm.delivery_notes.export',
                    'mm.vendor_deliveries.view', 'mm.vendor_deliveries.create',
                    'mm.business_event_logs.view',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'warehouse', 'display_name' => 'Gudang (Warehouse)',
                'description' => 'Akses Goods Receipt, Goods Issue, Stock, Delivery Note.', 'is_system' => true,
                'permissions' => Permission::whereIn('name', [
                    'mm.materials.view',
                    'mm.goods_receipts.view', 'mm.goods_receipts.create', 'mm.goods_receipts.edit',
                    'mm.goods_issues.view', 'mm.goods_issues.create', 'mm.goods_issues.edit',
                    'mm.stocks.view', 'mm.stocks.movements', 'mm.stocks.export',
                    'mm.storage_locations.view',
                    'mm.delivery_notes.view', 'mm.delivery_notes.receive', 'mm.delivery_notes.export',
                    'mm.vendor_deliveries.view',
                    'mm.business_event_logs.view',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'vendor_admin', 'display_name' => 'Vendor Admin',
                'description' => 'Admin dari sisi vendor. Bisa lihat PO, buat & lihat GR untuk vendor sendiri.', 'is_system' => true,
                'permissions' => Permission::whereIn('name', [
                    'mm.purchase_orders.view',
                    'mm.goods_receipts.view', 'mm.goods_receipts.create',
                    'mm.stocks.view',
                ])->pluck('id')->toArray(),
            ],
            [
                'name' => 'vendor_user', 'display_name' => 'Vendor User',
                'description' => 'User biasa dari sisi vendor. Hanya bisa lihat PO & GR vendor sendiri.', 'is_system' => true,
                'permissions' => Permission::whereIn('name', [
                    'mm.purchase_orders.view',
                    'mm.goods_receipts.view',
                    'mm.stocks.view',
                ])->pluck('id')->toArray(),
            ],
        ];

        foreach ($roles as $roleDef) {
            $permissions = $roleDef['permissions'];
            unset($roleDef['permissions']);

            $role = Role::firstOrCreate(['name' => $roleDef['name']], $roleDef);
            if (count($permissions) > 0) {
                $role->permissions()->syncWithoutDetaching($permissions);
            }
        }
    }
}
