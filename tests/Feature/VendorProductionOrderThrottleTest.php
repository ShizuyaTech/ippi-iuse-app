<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProductionOrder;
use App\Models\VendorProductionOrderReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorProductionOrderThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_report_endpoint_is_throttled_after_limit(): void
    {
        [$user, $order] = $this->seedReleasedOrder(1000);

        $lastResponse = null;
        for ($i = 1; $i <= 21; $i++) {
            $lastResponse = $this->actingAs($user)
                ->post(route('vendor.production-orders.report', $order), [
                    'report_date' => now()->toDateString(),
                    'quantity_ok' => 1,
                    'quantity_ng' => 0,
                    'notes' => 'Throttle test ' . $i,
                ]);
        }

        $this->assertNotNull($lastResponse);
        $lastResponse->assertStatus(429);
    }

    public function test_vendor_complete_endpoint_is_throttled_after_limit(): void
    {
        [$user, $order] = $this->seedCompletableOrder(1000);

        $lastResponse = null;
        for ($i = 1; $i <= 11; $i++) {
            $lastResponse = $this->actingAs($user)
                ->post(route('vendor.production-orders.complete', $order));
        }

        $this->assertNotNull($lastResponse);
        $lastResponse->assertStatus(429);
    }

    private function seedReleasedOrder(float $plannedQty): array
    {
        $vendor = Vendor::create([
            'code' => 'VND-THR-01',
            'name' => 'Vendor Throttle',
        ]);

        $user = User::create([
            'name' => 'Vendor Throttle User',
            'email' => 'vendor-throttle@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
        ]);

        $material = Material::create([
            'code' => 'FG-THR-01',
            'name' => 'Throttle FG',
            'type' => 'FP',
            'unit_of_measure' => 'PCS',
            'standard_price' => 0,
            'is_active' => true,
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-THR',
            'name' => 'Warehouse Throttle',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-THR-00001',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => $plannedQty * 100,
            'created_by' => $user->id,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'material_id' => $material->id,
            'quantity' => $plannedQty,
            'unit_price' => 100,
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'total_price' => $plannedQty * 100,
            'quantity_received' => 0,
        ]);

        $order = VendorProductionOrder::create([
            'order_number' => VendorProductionOrder::generateNumber(),
            'vendor_id' => $vendor->id,
            'material_id' => $material->id,
            'purchase_order_item_id' => $poItem->id,
            'quantity_planned' => $plannedQty,
            'quantity_ok' => 0,
            'quantity_ng' => 0,
            'status' => 'released',
            'actual_start_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        return [$user, $order];
    }

    private function seedCompletableOrder(float $plannedQty): array
    {
        [$user, $order] = $this->seedReleasedOrder($plannedQty);

        $order->update([
            'status' => 'in_progress',
            'quantity_ok' => $plannedQty,
            'quantity_ng' => 0,
        ]);

        VendorProductionOrderReport::create([
            'vendor_production_order_id' => $order->id,
            'report_date' => now()->toDateString(),
            'quantity_ok' => $plannedQty,
            'quantity_ng' => 0,
            'notes' => 'Ready to complete',
            'created_by' => $user->id,
        ]);

        return [$user, $order->fresh()];
    }
}
