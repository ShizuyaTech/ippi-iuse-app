<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class ApprovePurchaseOrders extends Command
{
    protected $signature   = 'app:approve-purchase-orders';
    protected $description = 'Auto-approve draft POs where expected delivery date is within 2 days (H-2)';

    public function handle(): int
    {
        $threshold = now()->addDays(2)->toDateString();

        $pos = PurchaseOrder::where('status', 'draft')
            ->whereNotNull('expected_delivery_date')
            ->whereDate('expected_delivery_date', '<=', $threshold)
            ->get();

        if ($pos->isEmpty()) {
            $this->info('No POs to auto-approve.');
            return self::SUCCESS;
        }

        foreach ($pos as $po) {
            $po->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'approved_by' => 'system',
            ]);
            $this->info("Auto-approved: {$po->po_number} (delivery: {$po->expected_delivery_date->format('d M Y')})");
        }

        $this->info("Total auto-approved: {$pos->count()} PO(s).");
        return self::SUCCESS;
    }
}
