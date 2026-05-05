<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProductionOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorProductionOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_cannot_over_allocate_same_po_item_into_multiple_orders(): void
    {
        $vendor = Vendor::create([
            'code' => 'VND-TST-03',
            'name' => 'Vendor Test 3',
        ]);

        $user = User::create([
            'name' => 'Vendor User 3',
            'email' => 'vendor3@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
        ]);

        $material = Material::create([
            'code' => 'FG-TST-03',
            'name' => 'Finished Test Item 3',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
            'order_method' => 'mrp',
            'process_vendor_id' => $vendor->id,
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-T3',
            'name' => 'Test Warehouse 3',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TST-00003',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 900,
            'created_by' => $user->id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'quantity' => 9,
            'unit_price' => 100,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'total_price' => 900,
            'quantity_received' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('vendor.production-orders.store'), [
                'purchase_order_item_id' => $poItem->id,
                'quantity_planned' => 6,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('vendor.production-orders.create'))
            ->post(route('vendor.production-orders.store'), [
                'purchase_order_item_id' => $poItem->id,
                'quantity_planned' => 4,
            ])
            ->assertSessionHasErrors('quantity_planned');
    }

    public function test_vendor_cannot_access_other_vendor_order(): void
    {
        $vendorA = Vendor::create([
            'code' => 'VND-A-01',
            'name' => 'Vendor A',
        ]);
        $vendorB = Vendor::create([
            'code' => 'VND-B-01',
            'name' => 'Vendor B',
        ]);

        $userA = User::create([
            'name' => 'User A',
            'email' => 'a@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendorA->id,
        ]);

        $material = Material::create([
            'code' => 'FG-TST-02',
            'name' => 'Finished Test Item B',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
            'order_method' => 'mrp',
            'process_vendor_id' => $vendorB->id,
        ]);

        $orderB = VendorProductionOrder::create([
            'order_number' => VendorProductionOrder::generateNumber(),
            'vendor_id' => $vendorB->id,
            'material_id' => $material->id,
            'quantity_planned' => 10,
            'quantity_ok' => 0,
            'quantity_ng' => 0,
            'status' => 'draft',
            'created_by' => $userA->id,
        ]);

        $this->actingAs($userA)
            ->get(route('vendor.production-orders.show', $orderB))
            ->assertForbidden();

        $this->actingAs($userA)
            ->post(route('vendor.production-orders.release', $orderB))
            ->assertForbidden();
    }

    public function test_vendor_can_create_release_report_and_auto_complete_order(): void
    {
        $vendor = Vendor::create([
            'code' => 'VND-TST-01',
            'name' => 'Vendor Test',
        ]);

        $user = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
        ]);

        $material = Material::create([
            'code' => 'FG-TST-01',
            'name' => 'Finished Test Item',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
            'order_method' => 'mrp',
            'process_vendor_id' => $vendor->id,
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-T1',
            'name' => 'Test Warehouse',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TST-00001',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 1000,
            'created_by' => $user->id,
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

        $this->actingAs($user)
            ->post(route('vendor.production-orders.store'), [
                'purchase_order_item_id' => $poItem->id,
                'quantity_planned' => 10,
                'planned_start_date' => now()->toDateString(),
                'planned_end_date' => now()->addDay()->toDateString(),
                'notes' => 'Order test',
            ])
            ->assertRedirect();

        $order = VendorProductionOrder::firstOrFail();
        $this->assertSame('draft', $order->status);

        $this->actingAs($user)
            ->post(route('vendor.production-orders.release', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('released', $order->status);

        // NG requires notes.
        $this->actingAs($user)
            ->post(route('vendor.production-orders.report', $order), [
                'report_date' => now()->toDateString(),
                'quantity_ok' => 5,
                'quantity_ng' => 1,
                'notes' => '',
            ])
            ->assertSessionHasErrors('notes');

        $this->actingAs($user)
            ->post(route('vendor.production-orders.report', $order), [
                'report_date' => now()->toDateString(),
                'quantity_ok' => 6,
                'quantity_ng' => 0,
                'notes' => 'Batch 1',
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('in_progress', $order->status);
        $this->assertEquals(6, (float) $order->quantity_ok);

        // Second report fulfills planned qty, should auto-complete.
        $this->actingAs($user)
            ->post(route('vendor.production-orders.report', $order), [
                'report_date' => now()->toDateString(),
                'quantity_ok' => 4,
                'quantity_ng' => 0,
                'notes' => 'Batch 2',
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertEquals(10, (float) $order->quantity_ok);
        $this->assertNotNull($order->actual_end_date);
        $this->assertNotNull($order->delivery_note_id);

        $deliveryNote = DeliveryNote::with('items')->findOrFail($order->delivery_note_id);
        $this->assertSame($po->id, $deliveryNote->purchase_order_id);
        $this->assertSame($vendor->id, $deliveryNote->vendor_id);
        $this->assertSame('vendor_production_order', $deliveryNote->source_type);
        $this->assertSame($order->id, $deliveryNote->source_id);
        $this->assertCount(1, $deliveryNote->items);
        $this->assertSame($poItem->id, $deliveryNote->items->first()->purchase_order_item_id);
        $this->assertEquals(10, (float) $deliveryNote->items->first()->quantity);

        $this->actingAs($user)
            ->post(route('vendor.production-orders.complete', $order))
            ->assertStatus(422);

        $this->assertSame(1, DeliveryNote::where('source_type', 'vendor_production_order')->where('source_id', $order->id)->count());
    }
}
