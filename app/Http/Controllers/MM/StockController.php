<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\VendorStock;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with('material', 'storageLocation');
        $this->applyStockFilters($query, $request);
        $stocks    = $query->paginate(25)->withQueryString();
        $locations = StorageLocation::all();

        // Total vendor stock per material_id (sum across all vendors)
        $vendorStockMap = VendorStock::selectRaw('material_id, SUM(quantity) as total_qty')
            ->groupBy('material_id')
            ->pluck('total_qty', 'material_id')
            ->toArray();

        // Material yang ada di vendor tapi belum punya Stock record di IPPI (WIP/FP belum diterima)
        $ippiMaterialIds = Stock::pluck('material_id')->unique()->toArray();
        $vendorOnlyStocks = VendorStock::with(['material', 'vendor'])
            ->where('quantity', '>', 0)
            ->whereNotIn('material_id', $ippiMaterialIds)
            ->get()
            ->groupBy('material_id');

        return view('mm.stocks.index', compact('stocks', 'locations', 'vendorStockMap', 'vendorOnlyStocks'));
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with('material', 'storageLocation', 'createdBy');
        if ($request->search) {
            $query->whereHas('material', fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        }
        if ($request->type)       $query->where('movement_type', $request->type);
        if ($request->location)   $query->where('storage_location_id', $request->location);
        if ($request->date_from)  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)    $query->whereDate('created_at', '<=', $request->date_to);
        $movements = $query->latest()->paginate(30)->withQueryString();
        $locations = StorageLocation::orderBy('name')->get();
        return view('mm.stocks.movements', compact('movements', 'locations'));
    }

    /**
     * Resolve status label based on material min_stock.
     */
    private function resolveStockStatus(Stock $s): string
    {
        $qty      = (float) $s->quantity;
        $minStock = (float) ($s->material->min_stock ?? 0);
        if ($qty <= 0) return 'Habis';
        if ($minStock > 0 && $qty <= $minStock) return 'Rendah';
        return 'Normal';
    }

    private function applyStockFilters($query, Request $request): void
    {
        // Selalu sembunyikan lokasi scrap dari stock overview
        $query->whereHas('storageLocation', fn($q) => $q->where('is_scrap', false));

        if ($request->search) {
            $query->whereHas('material', function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }
        if ($request->location) {
            $query->where('storage_location_id', $request->location);
        }
        if ($request->boolean('low_stock')) {
            $query->where('quantity', '>', 0)
                  ->whereRaw('(SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id) > 0')
                  ->whereRaw('quantity <= (SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id)');
        }
        if ($request->status === 'habis') {
            $query->where('quantity', '<=', 0);
        } elseif ($request->status === 'rendah') {
            $query->where('quantity', '>', 0)
                  ->whereRaw('(SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id) > 0')
                  ->whereRaw('quantity <= (SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id)');
        } elseif ($request->status === 'normal') {
            $query->where('quantity', '>', 0)
                  ->where(function ($q) {
                      $q->whereRaw('(SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id) = 0')
                        ->orWhereRaw('quantity > (SELECT COALESCE(min_stock,0) FROM materials WHERE id = stocks.material_id)');
                  });
        }
    }

    public function exportExcel(Request $request)
    {
        $query = Stock::with('material', 'storageLocation');
        $this->applyStockFilters($query, $request);
        $stocks = $query->orderBy('storage_location_id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Overview');

        $headers = ['Kode Material', 'Nama Material', 'Tipe', 'Lokasi', 'Qty Stok', 'UoM', 'Min Stok', 'Status'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:H1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($stocks as $s) {
            $status = $this->resolveStockStatus($s);
            $sheet->setCellValue("A{$r}", $s->material->code);
            $sheet->setCellValue("B{$r}", $s->material->name);
            $sheet->setCellValue("C{$r}", $s->material->type);
            $sheet->setCellValue("D{$r}", $s->storageLocation->name);
            $sheet->setCellValue("E{$r}", (float)$s->quantity);
            $sheet->setCellValue("F{$r}", $s->material->unit_of_measure ?? '-');
            $sheet->setCellValue("G{$r}", (float)($s->material->min_stock ?? 0));
            $sheet->setCellValue("H{$r}", $status);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:H{$r}", $r % 2 === 0);
            $r++;
        }
        foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'stock_overview_'.date('Ymd').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '256M');
        $query = Stock::with('material', 'storageLocation');
        $this->applyStockFilters($query, $request);
        $stocks      = $query->orderBy('storage_location_id')->get();
        $filters     = $request->only(['search', 'location']);
        $locationName = $request->location ? StorageLocation::find($request->location)?->name : null;
        $pdf = Pdf::loadView('mm.stocks.pdf-list', compact('stocks', 'filters', 'locationName'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('stock_overview_'.date('Ymd').'.pdf');
    }
}
