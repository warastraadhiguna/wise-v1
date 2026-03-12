<?php

namespace App\Domain\Reports;

use App\Domain\Pos\Support\PurchaseTotalsCalculator;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use Carbon\Carbon;

class BuildProfitLossDetailReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     sales: array{total_sales: float},
     *     purchases: array{
     *         total_recorded: float,
     *         total_cash: float,
     *         total_credit: float,
     *         credit_paid_total: float,
     *         credit_unpaid_total: float,
     *         debt_paid: float,
     *         debt_unpaid: float
     *     },
     *     profit_loss: array{
     *         total_sales: float,
     *         cogs_sold: float,
     *         profit_in_hand: float,
     *         inventory_value: float,
     *         debt_unpaid: float,
     *         total_profit: float
     *     }
     * }
     */
    public function handle(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $sales = Sale::query()
            ->with('details:id,sale_id,fifo_cost_amount')
            ->where('status', 'posted')
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->get();

        $purchases = Purchase::query()
            ->with('paymentMethod:id,is_cash')
            ->where('status', 'posted')
            ->whereDate('purchase_date', '>=', $from->toDateString())
            ->whereDate('purchase_date', '<=', $to->toDateString())
            ->get();

        $inventoryLayers = PurchaseDetail::query()
            ->select('purchase_details.*')
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->where('purchases.status', 'posted')
            ->whereDate('purchases.purchase_date', '<=', $to->toDateString())
            ->where('purchase_details.remaining_qty', '>', 0)
            ->whereNull('purchases.deleted_at')
            ->whereNull('purchase_details.deleted_at')
            ->get();

        $totalSales = round((float) $sales->sum('grand_total'), 2);
        $cogsSold = round((float) $sales->flatMap->details->sum('fifo_cost_amount'), 2);
        $profitInHand = round($totalSales - $cogsSold, 2);

        $totalRecordedPurchases = round((float) $purchases->sum('grand_total'), 2);
        $cashPurchases = round((float) $purchases
            ->filter(fn (Purchase $purchase) => (bool) ($purchase->paymentMethod?->is_cash ?? false))
            ->sum('grand_total'), 2);
        $creditPurchases = round((float) $purchases
            ->filter(fn (Purchase $purchase) => ! (bool) ($purchase->paymentMethod?->is_cash ?? false))
            ->sum('grand_total'), 2);
        $creditPaidPurchases = round((float) $purchases
            ->filter(fn (Purchase $purchase) => ! (bool) ($purchase->paymentMethod?->is_cash ?? false) && $purchase->payment_status === 'paid')
            ->sum('grand_total'), 2);
        $creditUnpaidPurchases = round((float) $purchases
            ->filter(fn (Purchase $purchase) => ! (bool) ($purchase->paymentMethod?->is_cash ?? false) && $purchase->payment_status !== 'paid')
            ->sum('grand_total'), 2);
        $debtPaid = round((float) $purchases
            ->filter(fn (Purchase $purchase) => ! (bool) ($purchase->paymentMethod?->is_cash ?? false))
            ->sum('paid_total'), 2);
        $debtUnpaid = round((float) $purchases
            ->filter(fn (Purchase $purchase) => ! (bool) ($purchase->paymentMethod?->is_cash ?? false))
            ->sum('balance_due'), 2);

        $inventoryValue = round((float) $inventoryLayers->sum(function (PurchaseDetail $detail): float {
            $unitCost = $this->resolveUnitCost($detail);

            return round((float) $detail->remaining_qty * $unitCost, 4);
        }), 2);

        $totalProfit = round($profitInHand - $inventoryValue - $debtUnpaid, 2);

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => trim((string) ($company?->name ?? config('app.name', 'Company'))),
                'address' => trim((string) ($company?->address ?? '')),
                'city' => trim((string) ($company?->city ?? '')),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'sales' => [
                'total_sales' => $totalSales,
            ],
            'purchases' => [
                'total_recorded' => $totalRecordedPurchases,
                'total_cash' => $cashPurchases,
                'total_credit' => $creditPurchases,
                'credit_paid_total' => $creditPaidPurchases,
                'credit_unpaid_total' => $creditUnpaidPurchases,
                'debt_paid' => $debtPaid,
                'debt_unpaid' => $debtUnpaid,
            ],
            'profit_loss' => [
                'total_sales' => $totalSales,
                'cogs_sold' => $cogsSold,
                'profit_in_hand' => $profitInHand,
                'inventory_value' => $inventoryValue,
                'debt_unpaid' => $debtUnpaid,
                'total_profit' => $totalProfit,
            ],
        ];
    }

    protected function resolveUnitCost(PurchaseDetail $purchaseDetail): float
    {
        $qty = round(max(0, (float) $purchaseDetail->qty), 4);

        if ($qty <= 0) {
            return 0.0;
        }

        $lineTotal = PurchaseTotalsCalculator::lineTotal(
            $purchaseDetail->qty,
            $purchaseDetail->price,
            $purchaseDetail->discount_percent,
            $purchaseDetail->discount_amount,
        );

        return round($lineTotal / $qty, 4);
    }
}
