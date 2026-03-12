<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;

class BuildDebtReceivableReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     type: string,
     *     type_label: string,
     *     partner_label: string,
     *     period_label: string,
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{total: float, returned: float, paid: float, balance: float}
     * }
     */
    public function handle(string $dateFrom, string $dateTo, string $type, string $status): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $type = $type === 'sale' ? 'sale' : 'purchase';
        $status = in_array($status, ['all', 'open', 'closed'], true) ? $status : 'all';

        $rows = $type === 'sale'
            ? $this->buildSaleRows($from, $to, $status)
            : $this->buildPurchaseRows($from, $to, $status);

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => (string) ($company?->name ?? config('app.name', 'Company')),
                'address' => (string) ($company?->address ?? ''),
                'city' => (string) ($company?->city ?? ''),
            ],
            'type' => $type,
            'type_label' => $type === 'sale' ? 'PIUTANG PENJUALAN' : 'HUTANG PEMBELIAN',
            'partner_label' => $type === 'sale' ? 'Pelanggan' : 'Supplier',
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'rows' => $rows,
            'summary' => [
                'total' => round((float) collect($rows)->sum('effective_total'), 2),
                'returned' => round((float) collect($rows)->sum('return_total'), 2),
                'paid' => round((float) collect($rows)->sum('paid_total'), 2),
                'balance' => round((float) collect($rows)->sum('balance_due'), 2),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPurchaseRows(Carbon $from, Carbon $to, string $status): array
    {
        $rows = Purchase::query()
            ->with([
                'supplier:id,name,company_name',
                'paymentMethod:id,name,is_cash',
                'user:id,name',
                'returns:id,purchase_id,total_amount',
                'payments.paymentMethod:id,name',
                'payments.user:id,name',
            ])
            ->where('status', 'posted')
            ->whereDate('purchase_date', '>=', $from->toDateString())
            ->whereDate('purchase_date', '<=', $to->toDateString())
            ->whereHas('paymentMethod', fn ($query) => $query->where('is_cash', false))
            ->orderBy('purchase_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Purchase $purchase) => $this->mapPurchaseRow($purchase))
            ->values();

        return $this->filterRowsByStatus($rows, $status)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['no'] = $index + 1;

                return $row;
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSaleRows(Carbon $from, Carbon $to, string $status): array
    {
        $rows = Sale::query()
            ->with([
                'customer:id,name,company_name',
                'paymentMethod:id,name,is_cash',
                'user:id,name',
                'returns:id,sale_id,total_amount',
                'payments.paymentMethod:id,name',
                'payments.user:id,name',
            ])
            ->where('status', 'posted')
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->whereHas('paymentMethod', fn ($query) => $query->where('is_cash', false))
            ->orderBy('sale_date')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Sale $sale) => $this->mapSaleRow($sale))
            ->values();

        return $this->filterRowsByStatus($rows, $status)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['no'] = $index + 1;

                return $row;
            })
            ->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function filterRowsByStatus($rows, string $status)
    {
        return $rows->filter(function (array $row) use ($status): bool {
            if ($status === 'closed') {
                return $row['status'] === 'paid';
            }

            if ($status === 'open') {
                return $row['status'] !== 'paid';
            }

            return true;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapPurchaseRow(Purchase $purchase): array
    {
        $returnTotal = round((float) $purchase->returns->sum('total_amount'), 2);
        $effectiveTotal = max(0, round((float) $purchase->grand_total - $returnTotal, 2));
        $paidTotal = round((float) $purchase->paid_total, 2);
        $balanceDue = max(0, round($effectiveTotal - $paidTotal, 2));

        return [
            'no' => 0,
            'number' => (string) $purchase->number,
            'transaction_at' => ($purchase->posted_at ?? $purchase->purchase_date)?->format('d M Y / H:i:s')
                ?? ($purchase->purchase_date?->format('d M Y') ?? '-'),
            'due_date' => $purchase->due_date?->format('d/m/Y') ?? '-',
            'payment_method' => $purchase->paymentMethod?->name ?? '-',
            'partner' => $purchase->supplier?->company_name ?: $purchase->supplier?->name ?: '-',
            'cashier' => $purchase->user?->name ?? '-',
            'grand_total' => round((float) $purchase->grand_total, 2),
            'return_total' => $returnTotal,
            'effective_total' => $effectiveTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'status' => $this->resolveStatus($effectiveTotal, $paidTotal, $balanceDue),
            'payments' => $purchase->payments
                ->sortBy(fn ($payment) => ($payment->paid_at?->timestamp ?? 0) . '-' . $payment->id)
                ->values()
                ->map(fn ($payment): array => [
                    'paid_at' => $payment->paid_at?->format('d/m/Y') ?? '-',
                    'payment_method' => $payment->paymentMethod?->name ?? '-',
                    'cashier' => $payment->user?->name ?? '-',
                    'reference_number' => $payment->reference_number ?: '-',
                    'note' => $payment->note ?: '-',
                    'amount' => round((float) $payment->amount, 2),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapSaleRow(Sale $sale): array
    {
        $returnTotal = round((float) $sale->returns->sum('total_amount'), 2);
        $effectiveTotal = max(0, round((float) $sale->grand_total - $returnTotal, 2));
        $paidTotal = round((float) $sale->paid_total, 2);
        $balanceDue = max(0, round($effectiveTotal - $paidTotal, 2));

        return [
            'no' => 0,
            'number' => (string) $sale->number,
            'transaction_at' => ($sale->posted_at ?? $sale->sale_date)?->format('d M Y / H:i:s')
                ?? ($sale->sale_date?->format('d M Y') ?? '-'),
            'due_date' => $sale->due_date?->format('d/m/Y') ?? '-',
            'payment_method' => $sale->paymentMethod?->name ?? '-',
            'partner' => $sale->customer?->company_name ?: $sale->customer?->name ?: '-',
            'cashier' => $sale->user?->name ?? '-',
            'grand_total' => round((float) $sale->grand_total, 2),
            'return_total' => $returnTotal,
            'effective_total' => $effectiveTotal,
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'status' => $this->resolveStatus($effectiveTotal, $paidTotal, $balanceDue),
            'payments' => $sale->payments
                ->sortBy(fn ($payment) => ($payment->paid_at?->timestamp ?? 0) . '-' . $payment->id)
                ->values()
                ->map(fn ($payment): array => [
                    'paid_at' => $payment->paid_at?->format('d/m/Y') ?? '-',
                    'payment_method' => $payment->paymentMethod?->name ?? '-',
                    'cashier' => $payment->user?->name ?? '-',
                    'reference_number' => $payment->reference_number ?: '-',
                    'note' => $payment->note ?: '-',
                    'amount' => round((float) $payment->amount, 2),
                ])
                ->all(),
        ];
    }

    protected function resolveStatus(float $effectiveTotal, float $effectivePaid, float $balanceDue): string
    {
        if ($effectiveTotal <= 0 || $balanceDue <= 0) {
            return 'paid';
        }

        if ($effectivePaid <= 0) {
            return 'unpaid';
        }

        return 'partial';
    }
}
