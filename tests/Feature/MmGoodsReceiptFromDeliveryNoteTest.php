<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use App\Models\GoodsReceipt;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MmGoodsReceiptFromDeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_mm_can_post_goods_receipt_from_received_delivery_note(): void
    {
        [$admin, $deliveryNote, $poItem, $location] = $this->seedDeliveryNoteReadyForGr();

        $this->actingAs($admin)
            ->post(route('mm.goods-receipts.store'), [
                'purchase_order_id' => $deliveryNote->purchase_order_id,
                'dn_id' => $deliveryNote->id,
                'receipt_date' => now()->toDateString(),
                'storage_location_id' => $location->id,
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'quantity' => 4,
                        'packing_note' => 'CASE-001',
                    ],
                ],
            ])
            ->assertRedirect(route('mm.goods-receipts.index'));

        $this->assertDatabaseHas('goods_receipts', [
            'purchase_order_id' => $deliveryNote->purchase_order_id,
            'delivery_note_id' => $deliveryNote->id,
            'status' => 'posted',
        ]);

        $poItem->refresh();
        $this->assertEquals(4, (float) $poItem->quantity_received);
    }

    public function test_mm_cannot_create_duplicate_goods_receipt_from_same_delivery_note(): void
    {
        [$admin, $deliveryNote, $poItem, $location] = $this->seedDeliveryNoteReadyForGr();

        $this->actingAs($admin)
            ->post(route('mm.goods-receipts.store'), [
                'purchase_order_id' => $deliveryNote->purchase_order_id,
                'dn_id' => $deliveryNote->id,
                'receipt_date' => now()->toDateString(),
                'storage_location_id' => $location->id,
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'quantity' => 4,
                    ],
                ],
            ])
            ->assertRedirect(route('mm.goods-receipts.index'));

        $this->actingAs($admin)
            ->from(route('mm.goods-receipts.create', ['dn_id' => $deliveryNote->id, 'po_id' => $deliveryNote->purchase_order_id]))
            ->post(route('mm.goods-receipts.store'), [
                'purchase_order_id' => $deliveryNote->purchase_order_id,
                'dn_id' => $deliveryNote->id,
                'receipt_date' => now()->toDateString(),
                'storage_location_id' => $location->id,
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('dn_id');

        $this->assertSame(1, (int) \App\Models\GoodsReceipt::where('delivery_note_id', $deliveryNote->id)->count());
    }

    public function test_mm_cannot_post_partial_goods_receipt_from_delivery_note(): void
    {
        [$admin, $deliveryNote, $poItem, $location] = $this->seedDeliveryNoteReadyForGr();

        $this->actingAs($admin)
            ->from(route('mm.goods-receipts.create', ['dn_id' => $deliveryNote->id, 'po_id' => $deliveryNote->purchase_order_id]))
            ->post(route('mm.goods-receipts.store'), [
                'purchase_order_id' => $deliveryNote->purchase_order_id,
                'dn_id' => $deliveryNote->id,
                'receipt_date' => now()->toDateString(),
                'storage_location_id' => $location->id,
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'quantity' => 3,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, (int) \App\Models\GoodsReceipt::where('delivery_note_id', $deliveryNote->id)->count());
    }

    public function test_mm_delivery_note_index_can_filter_by_gr_status(): void
    {
        [$admin, $deliveryNoteWithGr, $poItem, $location] = $this->seedDeliveryNoteReadyForGr();

        $deliveryNotePendingGr = DeliveryNote::create([
            'dn_number' => 'SJ-GR-00002',
            'purchase_order_id' => $deliveryNoteWithGr->purchase_order_id,
            'vendor_id' => $deliveryNoteWithGr->vendor_id,
            'estimated_delivery_date' => now()->toDateString(),
            'status' => 'received',
            'created_by' => $admin->id,
        ]);

        $deliveryNotePendingGr->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 2,
        ]);

        GoodsReceipt::create([
            'gr_number' => GoodsReceipt::generateNumber(),
            'purchase_order_id' => $deliveryNoteWithGr->purchase_order_id,
            'delivery_note_id' => $deliveryNoteWithGr->id,
            'receipt_date' => now()->toDateString(),
            'storage_location_id' => $location->id,
            'status' => 'posted',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('mm.delivery-notes.index', ['gr_status' => 'created']))
            ->assertOk()
            ->assertSee('SJ-GR-00001')
            ->assertDontSee('SJ-GR-00002');

        $this->actingAs($admin)
            ->get(route('mm.delivery-notes.index', ['gr_status' => 'pending']))
            ->assertOk()
            ->assertSee('SJ-GR-00002')
            ->assertDontSee('SJ-GR-00001');
    }

    public function test_mm_delivery_note_index_shows_quick_gr_actions(): void
    {
        [$admin, $deliveryNoteWithNoGr, $poItem, $location] = $this->seedDeliveryNoteReadyForGr();

        $deliveryNoteWithGr = DeliveryNote::create([
            'dn_number' => 'SJ-GR-00003',
            'purchase_order_id' => $deliveryNoteWithNoGr->purchase_order_id,
            'vendor_id' => $deliveryNoteWithNoGr->vendor_id,
            'estimated_delivery_date' => now()->toDateString(),
            'status' => 'received',
            'created_by' => $admin->id,
        ]);

        $deliveryNoteWithGr->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 1,
        ]);

        GoodsReceipt::create([
            'gr_number' => GoodsReceipt::generateNumber(),
            'purchase_order_id' => $deliveryNoteWithGr->purchase_order_id,
            'delivery_note_id' => $deliveryNoteWithGr->id,
            'receipt_date' => now()->toDateString(),
            'storage_location_id' => $location->id,
            'status' => 'posted',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('mm.delivery-notes.index'))
            ->assertOk()
            ->assertSee('Buat GR')
            ->assertSee('Lihat GR');
    }

    private function seedDeliveryNoteReadyForGr(): array
    {
        $admin = User::create([
            'name' => 'MM Admin',
            'email' => 'mm-gr-sj@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $vendor = Vendor::create([
            'code' => 'VND-GR-01',
            'name' => 'Vendor GR SJ',
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-GR',
            'name' => 'Warehouse GR',
        ]);

        $material = Material::create([
            'code' => 'RM-GR-01',
            'name' => 'Material GR',
            'type' => 'RM',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-GR-00001',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 1000,
            'created_by' => $admin->id,
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

        $deliveryNote = DeliveryNote::create([
            'dn_number' => 'SJ-GR-00001',
            'purchase_order_id' => $po->id,
            'vendor_id' => $vendor->id,
            'estimated_delivery_date' => now()->toDateString(),
            'status' => 'received',
            'created_by' => $admin->id,
        ]);

        $deliveryNote->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'quantity' => 4,
        ]);

        return [$admin, $deliveryNote, $poItem, $location];
    }
}
