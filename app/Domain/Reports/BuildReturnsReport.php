<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use Carbon\Carbon;

class BuildReturnsReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     type: string,
     *     type_label: string,
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     grand_total: float
     * }
     */
    public function handle(string $dateFrom, string $dateTo, string $type): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $type = $type === 'sale' ? 'sale' : 'purchase';
        $rows = $type === 'sale'
            ? $this->buildSaleRows($from, $to)
            : $this->buildPurchaseRows($from, $to);

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => (string) ($company?->name ?? config('app.name', 'Company')),
                'address' => (string) ($company?->address ?? ''),
                'city' => (string) ($company?->city ?? ''),
            ],
            'type' => $type,
            'type_label' => $type === 'sale' ? 'PENJUALAN' : 'PEMBELIAN',
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'rows' => $rows,
            'grand_total' => round((float) collect($rows)->sum('total_nota'), 2),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPurchaseRows(Carbon $from, Carbon $to): array
    {
        return PurchaseReturn::query()
            ->with([
                'purchase.supplier:id,name,company_name',
                'purchase.paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->whereDate('return_date', '>=', $from->toDateString())
            ->whereDate('return_date', '<=', $to->toDateString())
            ->orderBy('return_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (PurchaseReturn $return, int $index): array {
                return [
                    'no' => $index + 1,
                    'number' => (string) ($return->number ?: ('RETUR-' . $return->id)),
                    'transaction_at' => ($return->posted_at ?? $return->return_date)?->format('d M Y / H:i:s')
                        ?? ($return->return_date?->format('d M Y') ?? '-'),
                    'payment_method' => $return->purchase?->paymentMethod?->name ?? '-',
                    'partner' => $return->purchase?->supplier?->company_name ?: $return->purchase?->supplier?->name ?: '-',
                    'cashier' => $return->user?->name ?? '-',
                    'total_value' => round((float) $return->total_amount, 2),
                    'diskon' => 0.0,
                    'potongan' => 0.0,
                    'total_nota' => round((float) $return->total_amount, 2),
                    'details' => collect($return->details)->map(function ($detail) use ($return): array {
                        return [
                            'return_date' => $return->return_date?->format('d/m/Y') ?? '-',
                            'code' => $detail->product?->code ?? '-',
                            'name' => $detail->product?->name ?? '-',
                            'reason' => $return->reason ?: '-',
                            'price' => round((float) ($detail->price ?? 0), 2),
                            'qty' => round((float) ($detail->qty ?? 0), 4),
                            'unit' => $detail->product?->unit?->name ?? '-',
                            'subtotal' => round((float) ($detail->subtotal ?? 0), 2),
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSaleRows(Carbon $from, Carbon $to): array
    {
        return SaleReturn::query()
            ->with([
                'sale.customer:id,name,company_name',
                'sale.paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->whereDate('return_date', '>=', $from->toDateString())
            ->whereDate('return_date', '<=', $to->toDateString())
            ->orderBy('return_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (SaleReturn $return, int $index): array {
                return [
                    'no' => $index + 1,
                    'number' => (string) ($return->number ?: ('RETUR-' . $return->id)),
                    'transaction_at' => ($return->posted_at ?? $return->return_date)?->format('d M Y / H:i:s')
                        ?? ($return->return_date?->format('d M Y') ?? '-'),
                    'payment_method' => $return->sale?->paymentMethod?->name ?? '-',
                    'partner' => $return->sale?->customer?->company_name ?: $return->sale?->customer?->name ?: '-',
                    'cashier' => $return->user?->name ?? '-',
                    'total_value' => round((float) $return->total_amount, 2),
                    'diskon' => 0.0,
                    'potongan' => 0.0,
                    'total_nota' => round((float) $return->total_amount, 2),
                    'details' => collect($return->details)->map(function ($detail) use ($return): array {
                        return [
                            'return_date' => $return->return_date?->format('d/m/Y') ?? '-',
                            'code' => $detail->product?->code ?? '-',
                            'name' => $detail->product?->name ?? '-',
                            'reason' => $return->reason ?: '-',
                            'price' => round((float) ($detail->price ?? 0), 2),
                            'qty' => round((float) ($detail->qty ?? 0), 4),
                            'unit' => $detail->product?->unit?->name ?? '-',
                            'subtotal' => round((float) ($detail->subtotal ?? 0), 2),
                        ];
                    })->all(),
                ];
            })
            ->all();
    }
}
