<?php

namespace App\Domain\Reports;

use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\Company;
use App\Models\Sale;
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

        $rows = $sales->values()->map(function (Sale $sale, int $index): array {
            $detailsSubtotal = SaleTotalsCalculator::detailsSubtotal($sale->details);

            return [
                'no' => $index + 1,
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
        })->all();

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
