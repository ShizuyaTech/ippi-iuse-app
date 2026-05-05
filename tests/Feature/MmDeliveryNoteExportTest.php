<?php

namespace Tests\Feature;

use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MmDeliveryNoteExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mm_delivery_notes_export_returns_xlsx_and_applies_source_filter(): void
    {
        $user = User::create([
            'name' => 'MM Admin',
            'email' => 'mm-admin@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $vendor = Vendor::create([
            'code' => 'VND-EXP-01',
            'name' => 'Vendor Export',
        ]);

        $location = StorageLocation::create([
            'code' => 'WH-EXP',
            'name' => 'Warehouse Export',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-EXP-00001',
            'vendor_id' => $vendor->id,
            'storage_location_id' => $location->id,
            'order_date' => now()->toDateString(),
            'status' => 'approved',
            'total_amount' => 1000,
            'created_by' => $user->id,
        ]);

        $manual = DeliveryNote::create([
            'dn_number' => 'SJ-EXP-MANUAL',
            'purchase_order_id' => $po->id,
            'vendor_id' => $vendor->id,
            'estimated_delivery_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
            'source_type' => null,
            'source_id' => null,
            'created_by' => $user->id,
        ]);

        DeliveryNote::create([
            'dn_number' => 'SJ-EXP-AUTO',
            'purchase_order_id' => $po->id,
            'vendor_id' => $vendor->id,
            'estimated_delivery_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
            'source_type' => 'vendor_production_order',
            'source_id' => 9999,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('mm.delivery-notes.export', ['source_type' => 'manual']));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('delivery-notes-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $tmpFile = tempnam(sys_get_temp_dir(), 'dn_export_') . '.xlsx';
        file_put_contents($tmpFile, $content);

        $spreadsheet = IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 2 is first data row; with manual filter only manual SJ should be exported.
        $this->assertSame($manual->dn_number, $sheet->getCell('A2')->getValue());
        $this->assertSame('MANUAL', $sheet->getCell('G2')->getValue());
        $this->assertSame(null, $sheet->getCell('A3')->getValue());

        @unlink($tmpFile);
    }
}
