<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class BuildProductSalesChartReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     product: array{id: int|null, code: string, name: string},
     *     period_label: string,
     *     granularity: string,
     *     granularity_label: string,
     *     chart_title: string,
     *     rows: array<int, array{label: string, qty: float}>,
     *     total_qty: float,
     *     max_qty: float
     * }
     */
    public function handle(string $dateFrom, string $dateTo, ?int $productId = null, string $granularity = 'month'): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();
        $granularity = in_array($granularity, ['day', 'month', 'year'], true) ? $granularity : 'month';

        $product = $productId ? Product::query()->find($productId) : null;

        [$periodStart, $periodEnd, $intervalSpec] = match ($granularity) {
            'day' => [$from->copy()->startOfDay(), $to->copy()->startOfDay(), '1 day'],
            'year' => [$from->copy()->startOfYear(), $to->copy()->startOfYear(), '1 year'],
            default => [$from->copy()->startOfMonth(), $to->copy()->startOfMonth(), '1 month'],
        };

        $period = CarbonPeriod::create($periodStart, $intervalSpec, $periodEnd);
        $buckets = [];

        foreach ($period as $month) {
            $key = $this->formatBucketKey($month, $granularity);
            $buckets[$key] = [
                'label' => $this->formatBucketLabel($month, $granularity),
                'qty' => 0.0,
            ];
        }

        if ($product) {
            $salesDateExpression = $this->sqlBucketExpression('sales.sale_date', $granularity);
            $sales = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->whereNull('sale_details.deleted_at')
                ->whereNull('sales.deleted_at')
                ->where('sales.status', 'posted')
                ->where('sale_details.product_id', $product->id)
                ->whereDate('sales.sale_date', '>=', $from->toDateString())
                ->whereDate('sales.sale_date', '<=', $to->toDateString())
                ->groupByRaw($salesDateExpression)
                ->selectRaw("{$salesDateExpression} as bucket_key, SUM(sale_details.qty) as qty_total")
                ->pluck('qty_total', 'bucket_key');

            $returnsDateExpression = $this->sqlBucketExpression('sale_returns.return_date', $granularity);
            $returns = DB::table('sale_return_details')
                ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_details.sale_return_id')
                ->whereNull('sale_return_details.deleted_at')
                ->whereNull('sale_returns.deleted_at')
                ->where('sale_return_details.product_id', $product->id)
                ->whereDate('sale_returns.return_date', '>=', $from->toDateString())
                ->whereDate('sale_returns.return_date', '<=', $to->toDateString())
                ->groupByRaw($returnsDateExpression)
                ->selectRaw("{$returnsDateExpression} as bucket_key, SUM(sale_return_details.qty) as qty_total")
                ->pluck('qty_total', 'bucket_key');

            foreach ($buckets as $key => $bucket) {
                $netQty = round((float) ($sales[$key] ?? 0) - (float) ($returns[$key] ?? 0), 4);
                $buckets[$key]['qty'] = $netQty;
            }
        }

        $rows = array_values($buckets);
        $totalQty = round((float) collect($rows)->sum('qty'), 4);
        $maxQty = round((float) max(1, collect($rows)->max('qty') ?: 0), 4);

        $company = Company::query()->first();
        $productLabel = $product
            ? trim(($product->code ? $product->code . ' ' : '') . $product->name)
            : 'Pilih Produk';
        $granularityLabel = match ($granularity) {
            'day' => 'Hari',
            'year' => 'Tahun',
            default => 'Bulan',
        };

        return [
            'company' => [
                'name' => trim((string) ($company?->name ?? config('app.name', 'Company'))),
                'address' => trim((string) ($company?->address ?? '')),
                'city' => trim((string) ($company?->city ?? '')),
            ],
            'product' => [
                'id' => $product?->id,
                'code' => (string) ($product?->code ?? ''),
                'name' => (string) ($product?->name ?? ''),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'granularity' => $granularity,
            'granularity_label' => $granularityLabel,
            'chart_title' => 'Grafik Penjualan ' . $productLabel . ' Tiap ' . $granularityLabel,
            'rows' => $rows,
            'total_qty' => $totalQty,
            'max_qty' => $maxQty,
        ];
    }

    protected function sqlBucketExpression(string $column, string $granularity): string
    {
        return match ($granularity) {
            'day' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
            'year' => "DATE_FORMAT({$column}, '%Y')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    protected function formatBucketKey(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'day' => $date->format('Y-m-d'),
            'year' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    protected function formatBucketLabel(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'day' => $date->format('d/m'),
            'year' => $date->format('Y'),
            default => $date->translatedFormat('M Y'),
        };
    }
}
