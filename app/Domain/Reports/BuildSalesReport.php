<?php

namespace App\Domain\Reports;

use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\Company;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;

class BuildSalesReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     grand_total: float
     * }
     */
    public function handle(string $dateFrom, string $dateTo, ?int $cashierId = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $sales = Sale::query()
            ->with([
                'customer:id,name,company_name',
                'paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->where('status', 'posted')
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->when($cashierId, fn ($query) => $query->where('user_id', $cashierId))
            ->orderBy('sale_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        $saleReturns = SaleReturn::query()
            ->with([
                'sale.customer:id,name,company_name',
                'sale.paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->whereDate('return_date', '>=', $from->toDateString())
            ->whereDate('return_date', '<=', $to->toDateString())
            ->when($cashierId, fn ($query) => $query->where('user_id', $cashierId))
            ->orderBy('return_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        $saleRows = $sales->values()->map(function (Sale $sale): array {
            $detailsSubtotal = SaleTotalsCalculator::detailsSubtotal($sale->details);

            return [
                'sort_at' => ($sale->posted_at ?? $sale->sale_date)?->timestamp ?? 0,
                'id' => (int) $sale->id,
                'number' => (string) $sale->number,
                'transaction_at' => ($sale->posted_at ?? $sale->sale_date)?->format('d M Y / H:i:s')
                    ?? ($sale->sale_date?->format('d M Y') ?? '-'),
                'payment_method' => $sale->paymentMethod?->name ?? '-',
                'customer' => $sale->customer?->company_name ?: $sale->customer?->name ?: '-',
                'cashier' => $sale->user?->name ?? '-',
                'reference_number' => $sale->reference_number ?: '-',
                'total_jual' => round($detailsSubtotal, 2),
                'diskon' => round((float) ($sale->discount_percent ?? 0), 2),
                'potongan' => round((float) ($sale->discount_amount ?? 0), 2),
                'total_nota' => round((float) ($sale->grand_total ?? 0), 2),
                'details' => collect($sale->details)->map(function ($detail): array {
                    return [
                        'code' => $detail->product?->code ?? '-',
                        'name' => $detail->product?->name ?? '-',
                        'price' => round((float) ($detail->price ?? 0), 2),
                        'qty' => round((float) ($detail->qty ?? 0), 4),
                        'unit' => $detail->product?->unit?->name ?? '-',
                        'subtotal' => SaleTotalsCalculator::lineTotal(
                            $detail->qty,
                            $detail->price,
                            $detail->discount_percent,
                            $detail->discount_amount,
                        ),
                    ];
                })->all(),
            ];
        });

        $returnRows = $saleReturns->values()->map(function (SaleReturn $saleReturn): array {
            return [
                'sort_at' => ($saleReturn->posted_at ?? $saleReturn->return_date)?->timestamp ?? 0,
                'id' => (int) $saleReturn->id,
                'number' => (string) ($saleReturn->number ?? ('RETUR-' . $saleReturn->id)),
                'transaction_at' => ($saleReturn->posted_at ?? $saleReturn->return_date)?->format('d M Y / H:i:s')
                    ?? ($saleReturn->return_date?->format('d M Y') ?? '-'),
                'payment_method' => $saleReturn->sale?->paymentMethod?->name ?? '-',
                'customer' => $saleReturn->sale?->customer?->company_name ?: $saleReturn->sale?->customer?->name ?: '-',
                'cashier' => $saleReturn->user?->name ?? '-',
                'reference_number' => $saleReturn->sale?->number ?: '-',
                'total_jual' => round((float) $saleReturn->total_amount * -1, 2),
                'diskon' => 0,
                'potongan' => 0,
                'total_nota' => round((float) $saleReturn->total_amount * -1, 2),
                'details' => collect($saleReturn->details)->map(function ($detail): array {
                    return [
                        'code' => $detail->product?->code ?? '-',
                        'name' => $detail->product?->name ?? '-',
                        'price' => round((float) ($detail->price ?? 0), 2),
                        'qty' => round((float) ($detail->qty ?? 0) * -1, 4),
                        'unit' => $detail->product?->unit?->name ?? '-',
                        'subtotal' => round((float) ($detail->subtotal ?? 0) * -1, 2),
                    ];
                })->all(),
            ];
        });

        $rows = $saleRows
            ->concat($returnRows)
            ->sortBy([
                ['sort_at', 'asc'],
                ['number', 'asc'],
            ])
            ->values()
            ->map(function (array $row, int $index): array {
                $row['no'] = $index + 1;
                unset($row['sort_at']);

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
            'grand_total' => round(collect($rows)->sum('total_nota'), 2),
        ];
    }
}
