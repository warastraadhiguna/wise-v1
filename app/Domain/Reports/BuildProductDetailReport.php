<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BuildProductDetailReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{masuk: float, keluar: float, ret_beli: float, ret_jual: float}
     * }
     */
    public function handle(string $dateFrom, string $dateTo, ?int $productId = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $products = Product::query()
            ->when($productId, fn ($query) => $query->whereKey($productId))
            ->orderBy('code')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $movements = StockMovement::query()
            ->whereDate('happened_at', '>=', $from->toDateString())
            ->whereDate('happened_at', '<=', $to->toDateString())
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->get(['product_id', 'qty_change', 'ref_type', 'happened_at']);

        $aggregates = [];

        foreach ($movements as $movement) {
            $dateKey = $movement->happened_at?->toDateString() ?? $from->toDateString();
            $rowKey = $dateKey . '|' . $movement->product_id;

            if (! isset($aggregates[$rowKey])) {
                $aggregates[$rowKey] = [
                    'masuk' => 0.0,
                    'keluar' => 0.0,
                    'ret_beli' => 0.0,
                    'ret_jual' => 0.0,
                ];
            }

            $qty = round(abs((float) $movement->qty_change), 4);

            match ((string) $movement->ref_type) {
                'PURCHASE' => $aggregates[$rowKey]['masuk'] += $qty,
                'SALE' => $aggregates[$rowKey]['keluar'] += $qty,
                'PURCHASE_RETURN' => $aggregates[$rowKey]['ret_beli'] += $qty,
                'SALE_RETURN' => $aggregates[$rowKey]['ret_jual'] += $qty,
                'PURCHASE_RETURN_DETAIL_DELETE' => $aggregates[$rowKey]['masuk'] += $qty,
                'SALE_RETURN_DETAIL_DELETE' => $aggregates[$rowKey]['keluar'] += $qty,
                default => null,
            };
        }

        $rows = [];
        $period = CarbonPeriod::create($from->toDateString(), $to->toDateString());

        foreach ($period as $date) {
            foreach ($products as $product) {
                $rowKey = $date->toDateString() . '|' . $product->id;
                $totals = $aggregates[$rowKey] ?? [
                    'masuk' => 0.0,
                    'keluar' => 0.0,
                    'ret_beli' => 0.0,
                    'ret_jual' => 0.0,
                ];

                $rows[] = [
                    'tanggal' => $date->format('d/m/Y'),
                    'code' => $product->code ?? '-',
                    'name' => $product->name ?? '-',
                    'masuk' => round((float) $totals['masuk'], 4),
                    'keluar' => round((float) $totals['keluar'], 4),
                    'ret_beli' => round((float) $totals['ret_beli'], 4),
                    'ret_jual' => round((float) $totals['ret_jual'], 4),
                ];
            }
        }

        $rows = collect($rows)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['no'] = $index + 1;

                return $row;
            })
            ->all();

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => trim((string) ($company?->name ?? config('app.name', 'Company'))),
                'address' => trim((string) ($company?->address ?? '')),
                'city' => trim((string) ($company?->city ?? '')),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'rows' => $rows,
            'summary' => [
                'masuk' => round((float) collect($rows)->sum('masuk'), 4),
                'keluar' => round((float) collect($rows)->sum('keluar'), 4),
                'ret_beli' => round((float) collect($rows)->sum('ret_beli'), 4),
                'ret_jual' => round((float) collect($rows)->sum('ret_jual'), 4),
            ],
        ];
    }
}
