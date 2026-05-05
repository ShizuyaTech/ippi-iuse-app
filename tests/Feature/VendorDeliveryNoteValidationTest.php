<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorDeliveryNoteValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_cannot_create_delivery_note_over_available_quantity(): void
    {
        [$vendor, $user, $poItem] = $this->seedVendorPoWithSingleItem(10);

        $existingDn = DeliveryNote::create([
            'dn_number' => DeliveryNote::generateNumber(),
            'purchase_order_id' => $poItem->purchase_order_id,
            'vendor_id' => $vendor->id,
            'estimated_delivery_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $existingDn->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 8,
        ]);

        $this->actingAs($user)
            ->from(route('vendor.delivery-notes.create', ['po_id' => $poItem->purchase_order_id]))
            ->post(route('vendor.delivery-notes.store'), [
                'purchase_order_id' => $poItem->purchase_order_id,
                'estimated_delivery_date' => now()->addDay()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'quantity' => 3,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(1, DeliveryNote::count());
    }

    public function test_vendor_cannot_use_po_item_from_other_po(): void
    {
        [$vendor, $user, $poItemA] = $this->seedVendorPoWithSingleItem(10);

        $materialB = Material::create([
            'code' => 'RM-TST-B',
            'name' => 'Material B',
            'type' => 'RM',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
        ]);

        $poB = PurchaseOrder::create([
            'po_number' => 'PO-TST-DN-B',
            'vendor_id' => $vendor->id,
            'storage_location_id' => PurchaseOrder::findOrFail($poItemA->purchase_order_id)->storage_location_id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 500,
            'created_by' => $user->id,
        ]);

        $poItemB = PurchaseOrderItem::create([
            'purchase_order_id' => $poB->id,
            'material_id' => $materialB->id,
            'quantity' => 5,
            'unit_price' => 100,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'total_price' => 500,
            'quantity_received' => 0,
        ]);

        $this->actingAs($user)
            ->from(route('vendor.delivery-notes.create', ['po_id' => $poItemA->purchase_order_id]))
            ->post(route('vendor.delivery-notes.store'), [
                'purchase_order_id' => $poItemA->purchase_order_id,
                'estimated_delivery_date' => now()->addDay()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => $poItemB->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, DeliveryNote::count());
    }

    private function seedVendorPoWithSingleItem(float $quantity): array
    {
        $vendor = Vendor::create([
            'code' => 'VND-DN-01',
            'name' => 'Vendor DN Test',
        ]);

        $user = User::create([
            'name' => 'Vendor DN User',
            'email' => 'vendor-dn@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
        ]);

        $material = Material::create([
            'code' => 'RM-TST-A',
            'name' => 'Material A',
            'type' => 'RM',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-DN',
            'name' => 'Warehouse DN',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TST-DN-A',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => $quantity * 100,
            'created_by' => $user->id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'total_price' => $quantity * 100,
            'quantity_received' => 0,
        ]);

        return [$vendor, $user, $poItem];
    }
}
