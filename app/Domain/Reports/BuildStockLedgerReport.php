<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BuildStockLedgerReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{qty_in: float, qty_out: float}
     * }
     */
    public function handle(string $dateFrom, string $dateTo, ?int $productId = null, ?string $refType = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $movements = StockMovement::query()
            ->with([
                'product:id,code,name,unit_id',
                'product.unit:id,name',
                'user:id,name',
            ])
            ->whereDate('happened_at', '>=', $from->toDateString())
            ->whereDate('happened_at', '<=', $to->toDateString())
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when(filled($refType) && $refType !== 'all', fn ($query) => $query->where('ref_type', $refType))
            ->orderBy('happened_at')
            ->orderBy('id')
            ->get();

        $referenceMaps = $this->buildReferenceMaps($movements);

        $rows = $movements->values()->map(function (StockMovement $movement, int $index) use ($referenceMaps): array {
            $qtyChange = round((float) $movement->qty_change, 4);

            return [
                'no' => $index + 1,
                'happened_at' => $movement->happened_at?->format('d M Y / H:i:s') ?? '-',
                'product_code' => $movement->product?->code ?? '-',
                'product_name' => $movement->product?->name ?? '-',
                'unit' => $movement->product?->unit?->name ?? '-',
                'ref_type' => (string) $movement->ref_type,
                'ref_type_label' => $this->resolveRefTypeLabel((string) $movement->ref_type),
                'reference_number' => $referenceMaps[(string) $movement->ref_type][(int) $movement->ref_id] ?? ('#' . $movement->ref_id),
                'user' => $movement->user?->name ?? '-',
                'qty_in' => $qtyChange > 0 ? $qtyChange : 0.0,
                'qty_out' => $qtyChange < 0 ? abs($qtyChange) : 0.0,
                'balance_after' => round((float) ($movement->balance_after ?? 0), 4),
            ];
        })->all();

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => (string) ($company?->name ?? config('app.name', 'Company')),
                'address' => (string) ($company?->address ?? ''),
                'city' => (string) ($company?->city ?? ''),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'rows' => $rows,
            'summary' => [
                'qty_in' => round((float) collect($rows)->sum('qty_in'), 4),
                'qty_out' => round((float) collect($rows)->sum('qty_out'), 4),
            ],
        ];
    }

    /**
     * @param Collection<int, StockMovement> $movements
     * @return array<string, array<int, string>>
     */
    protected function buildReferenceMaps(Collection $movements): array
    {
        $idsByType = $movements
            ->groupBy('ref_type')
            ->map(fn (Collection $group) => $group->pluck('ref_id')->unique()->map(fn ($id) => (int) $id)->all());

        return [
            'PURCHASE' => Purchase::withTrashed()
                ->whereIn('id', $idsByType->get('PURCHASE', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
            'SALE' => Sale::withTrashed()
                ->whereIn('id', $idsByType->get('SALE', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
            'PURCHASE_RETURN' => PurchaseReturn::withTrashed()
                ->whereIn('id', $idsByType->get('PURCHASE_RETURN', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
            'SALE_RETURN' => SaleReturn::withTrashed()
                ->whereIn('id', $idsByType->get('SALE_RETURN', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
            'PURCHASE_RETURN_DETAIL_DELETE' => PurchaseReturn::withTrashed()
                ->whereIn('id', $idsByType->get('PURCHASE_RETURN_DETAIL_DELETE', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
            'SALE_RETURN_DETAIL_DELETE' => SaleReturn::withTrashed()
                ->whereIn('id', $idsByType->get('SALE_RETURN_DETAIL_DELETE', []))
                ->pluck('number', 'id')
                ->map(fn ($value) => (string) $value)
                ->all(),
        ];
    }

    protected function resolveRefTypeLabel(string $refType): string
    {
        return match ($refType) {
            'PURCHASE' => 'Pembelian',
            'SALE' => 'Penjualan',
            'PURCHASE_RETURN' => 'Retur Pembelian',
            'SALE_RETURN' => 'Retur Penjualan',
            'PURCHASE_RETURN_DETAIL_DELETE' => 'Batal Detail Retur Pembelian',
            'SALE_RETURN_DETAIL_DELETE' => 'Batal Detail Retur Penjualan',
            default => $refType,
        };
    }
}
