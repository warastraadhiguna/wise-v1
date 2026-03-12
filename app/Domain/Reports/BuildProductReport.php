<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BuildProductReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     transaction_label: string,
     *     rank_label: string,
     *     limit: int,
     *     rows: array<int, array<string, mixed>>
     * }
     */
    public function handle(string $dateFrom, string $dateTo, string $transactionType, string $rankType, int $limit): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $transactionType = $transactionType === 'purchase' ? 'purchase' : 'sale';
        $rankType = $rankType === 'worst' ? 'worst' : 'best';
        $limit = max(1, min($limit, 100));

        $rows = $transactionType === 'sale'
            ? $this->buildSaleRows($from, $to, $rankType, $limit)
            : $this->buildPurchaseRows($from, $to, $rankType, $limit);

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => trim((string) ($company?->name ?? config('app.name', 'Company'))),
                'address' => trim((string) ($company?->address ?? '')),
                'city' => trim((string) ($company?->city ?? '')),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'transaction_label' => $transactionType === 'sale' ? 'PENJUALAN' : 'PEMBELIAN',
            'rank_label' => $rankType === 'best' ? 'TERBAIK' : 'TERBURUK',
            'limit' => $limit,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSaleRows(Carbon $from, Carbon $to, string $rankType, int $limit): array
    {
        $sold = DB::table('sale_details')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->whereNull('sale_details.deleted_at')
            ->whereNull('sales.deleted_at')
            ->where('sales.status', 'posted')
            ->whereDate('sales.sale_date', '>=', $from->toDateString())
            ->whereDate('sales.sale_date', '<=', $to->toDateString())
            ->groupBy('sale_details.product_id')
            ->select('sale_details.product_id', DB::raw('SUM(sale_details.qty) as qty_total'))
            ->pluck('qty_total', 'sale_details.product_id');

        $returned = DB::table('sale_return_details')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_details.sale_return_id')
            ->whereNull('sale_return_details.deleted_at')
            ->whereNull('sale_returns.deleted_at')
            ->whereDate('sale_returns.return_date', '>=', $from->toDateString())
            ->whereDate('sale_returns.return_date', '<=', $to->toDateString())
            ->groupBy('sale_return_details.product_id')
            ->select('sale_return_details.product_id', DB::raw('SUM(sale_return_details.qty) as qty_total'))
            ->pluck('qty_total', 'sale_return_details.product_id');

        return $this->buildRowsFromTotals($sold, $returned, $rankType, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPurchaseRows(Carbon $from, Carbon $to, string $rankType, int $limit): array
    {
        $purchased = DB::table('purchase_details')
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->whereNull('purchase_details.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.status', 'posted')
            ->whereDate('purchases.purchase_date', '>=', $from->toDateString())
            ->whereDate('purchases.purchase_date', '<=', $to->toDateString())
            ->groupBy('purchase_details.product_id')
            ->select('purchase_details.product_id', DB::raw('SUM(purchase_details.qty) as qty_total'))
            ->pluck('qty_total', 'purchase_details.product_id');

        $returned = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->whereNull('purchase_return_details.deleted_at')
            ->whereNull('purchase_returns.deleted_at')
            ->whereDate('purchase_returns.return_date', '>=', $from->toDateString())
            ->whereDate('purchase_returns.return_date', '<=', $to->toDateString())
            ->groupBy('purchase_return_details.product_id')
            ->select('purchase_return_details.product_id', DB::raw('SUM(purchase_return_details.qty) as qty_total'))
            ->pluck('qty_total', 'purchase_return_details.product_id');

        return $this->buildRowsFromTotals($purchased, $returned, $rankType, $limit);
    }

    /**
     * @param \Illuminate\Support\Collection<int|string, mixed> $primaryTotals
     * @param \Illuminate\Support\Collection<int|string, mixed> $returnTotals
     * @return array<int, array<string, mixed>>
     */
    protected function buildRowsFromTotals($primaryTotals, $returnTotals, string $rankType, int $limit): array
    {
        $productIds = collect($primaryTotals->keys())
            ->merge($returnTotals->keys())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::withTrashed()
            ->with('unit:id,name')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $rows = $productIds
            ->map(function (int $productId) use ($primaryTotals, $returnTotals, $products): ?array {
                $qty = round((float) ($primaryTotals[$productId] ?? 0) - (float) ($returnTotals[$productId] ?? 0), 4);

                if ($qty == 0.0) {
                    return null;
                }

                $product = $products->get($productId);

                return [
                    'product_id' => $productId,
                    'code' => $product?->code ?? '-',
                    'name' => $product?->name ?? '-',
                    'unit' => $product?->unit?->name ?? '-',
                    'qty' => $qty,
                ];
            })
            ->filter()
            ->sortBy([
                ['qty', $rankType === 'best' ? 'desc' : 'asc'],
                ['name', 'asc'],
            ])
            ->take($limit)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['no'] = $index + 1;

                return $row;
            })
            ->all();

        return $rows;
    }
}
