<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProcessVendorCoreRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mm_cannot_create_po_if_material_process_vendor_differs_from_po_vendor(): void
    {
        $admin = User::create([
            'name' => 'MM Admin',
            'email' => 'mm-process-vendor@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $vendorA = Vendor::create(['code' => 'VND-PA', 'name' => 'Process Vendor A']);
        $vendorB = Vendor::create(['code' => 'VND-PB', 'name' => 'Process Vendor B']);

        $location = StorageLocation::create([
            'code' => 'WH-PV',
            'name' => 'Warehouse PV',
        ]);

        $material = Material::create([
            'code' => 'FP-PV-01',
            'name' => 'FP Process Vendor',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 100,
            'is_active' => true,
            'order_method' => 'mrp',
            'process_vendor_id' => $vendorA->id,
        ]);

        $this->actingAs($admin)
            ->from(route('mm.purchase-orders.create'))
            ->post(route('mm.purchase-orders.store'), [
                'vendor_id' => $vendorB->id,
                'storage_location_id' => $location->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'material_id' => $material->id,
                        'quantity' => 5,
                        'unit_price' => 100,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_vendor_cannot_create_production_order_if_material_not_assigned_to_vendor_process(): void
    {
        $vendorA = Vendor::create(['code' => 'VND-VA', 'name' => 'Vendor A']);
        $vendorB = Vendor::create(['code' => 'VND-VB', 'name' => 'Vendor B']);

        $userA = User::create([
            'name' => 'Vendor User A',
            'email' => 'vendor-a-process@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendorA->id,
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-VP',
            'name' => 'Warehouse VP',
        ]);

        $material = Material::create([
            'code' => 'FP-VP-01',
            'name' => 'FP Vendor Process',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 100,
            'is_active' => true,
            'order_method' => 'mrp',
            'process_vendor_id' => $vendorB->id,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-VP-00001',
            'vendor_id' => $vendorA->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 1000,
            'created_by' => $userA->id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'quantity' => 10,
            'unit_price' => 100,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'total_price' => 1000,
            'quantity_received' => 0,
        ]);

        $this->actingAs($userA)
            ->from(route('vendor.production-orders.create'))
            ->post(route('vendor.production-orders.store'), [
                'purchase_order_item_id' => $poItem->id,
                'quantity_planned' => 5,
            ])
            ->assertSessionHasErrors('purchase_order_item_id');
    }
}
