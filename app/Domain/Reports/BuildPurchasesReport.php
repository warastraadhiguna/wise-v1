<?php

namespace App\Domain\Reports;

use App\Domain\Pos\Support\PurchaseTotalsCalculator;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Carbon\Carbon;

class BuildPurchasesReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     grand_total: float
     * }
     */
    public function handle(string $dateFrom, string $dateTo, ?int $supplierId = null): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $purchases = Purchase::query()
            ->with([
                'supplier:id,name,company_name',
                'paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->where('status', 'posted')
            ->whereDate('purchase_date', '>=', $from->toDateString())
            ->whereDate('purchase_date', '<=', $to->toDateString())
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->orderBy('purchase_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        $purchaseReturns = PurchaseReturn::query()
            ->with([
                'purchase.supplier:id,name,company_name',
                'purchase.paymentMethod:id,name',
                'user:id,name',
                'details.product:id,code,name,unit_id',
                'details.product.unit:id,name',
            ])
            ->whereDate('return_date', '>=', $from->toDateString())
            ->whereDate('return_date', '<=', $to->toDateString())
            ->when($supplierId, fn ($query) => $query->whereHas('purchase', fn ($purchaseQuery) => $purchaseQuery->where('supplier_id', $supplierId)))
            ->orderBy('return_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get();

        $purchaseRows = $purchases->values()->map(function (Purchase $purchase): array {
            $detailsSubtotal = PurchaseTotalsCalculator::detailsSubtotal($purchase->details);

            return [
                'sort_at' => ($purchase->posted_at ?? $purchase->purchase_date)?->timestamp ?? 0,
                'id' => (int) $purchase->id,
                'number' => (string) $purchase->number,
                'transaction_at' => ($purchase->posted_at ?? $purchase->purchase_date)?->format('d M Y / H:i:s')
                    ?? ($purchase->purchase_date?->format('d M Y') ?? '-'),
                'payment_method' => $purchase->paymentMethod?->name ?? '-',
                'supplier' => $purchase->supplier?->company_name ?: $purchase->supplier?->name ?: '-',
                'cashier' => $purchase->user?->name ?? '-',
                'reference_number' => $purchase->reference_number ?: '-',
                'total_beli' => round($detailsSubtotal, 2),
                'diskon' => round((float) ($purchase->discount_percent ?? 0), 2),
                'potongan' => round((float) ($purchase->discount_amount ?? 0), 2),
                'total_nota' => round((float) ($purchase->grand_total ?? 0), 2),
                'details' => collect($purchase->details)->map(function ($detail): array {
                    return [
                        'code' => $detail->product?->code ?? '-',
                        'name' => $detail->product?->name ?? '-',
                        'price' => round((float) ($detail->price ?? 0), 2),
                        'qty' => round((float) ($detail->qty ?? 0), 4),
                        'unit' => $detail->product?->unit?->name ?? '-',
                        'subtotal' => PurchaseTotalsCalculator::lineTotal(
                            $detail->qty,
                            $detail->price,
                            $detail->discount_percent,
                            $detail->discount_amount,
                        ),
                    ];
                })->all(),
            ];
        });

        $returnRows = $purchaseReturns->values()->map(function (PurchaseReturn $purchaseReturn): array {
            return [
                'sort_at' => ($purchaseReturn->posted_at ?? $purchaseReturn->return_date)?->timestamp ?? 0,
                'id' => (int) $purchaseReturn->id,
                'number' => (string) ($purchaseReturn->number ?? ('RETUR-' . $purchaseReturn->id)),
                'transaction_at' => ($purchaseReturn->posted_at ?? $purchaseReturn->return_date)?->format('d M Y / H:i:s')
                    ?? ($purchaseReturn->return_date?->format('d M Y') ?? '-'),
                'payment_method' => $purchaseReturn->purchase?->paymentMethod?->name ?? '-',
                'supplier' => $purchaseReturn->purchase?->supplier?->company_name ?: $purchaseReturn->purchase?->supplier?->name ?: '-',
                'cashier' => $purchaseReturn->user?->name ?? '-',
                'reference_number' => $purchaseReturn->purchase?->number ?: '-',
                'total_beli' => round((float) $purchaseReturn->total_amount * -1, 2),
                'diskon' => 0,
                'potongan' => 0,
                'total_nota' => round((float) $purchaseReturn->total_amount * -1, 2),
                'details' => collect($purchaseReturn->details)->map(function ($detail): array {
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

        $rows = $purchaseRows
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
