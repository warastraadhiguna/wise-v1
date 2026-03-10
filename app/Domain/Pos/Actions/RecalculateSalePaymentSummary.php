<?php

namespace App\Domain\Pos\Actions;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class RecalculateSalePaymentSummary
{
    public function handle(int $saleId): void
    {
        DB::transaction(function () use ($saleId) {
            $sale = Sale::whereKey($saleId)->lockForUpdate()->firstOrFail();

            $paidTotal = (float) $sale->payments()->sum('amount');
            $grandTotal = (float) $sale->grand_total;

            $balanceDue = max(0, round($grandTotal - $paidTotal, 2));

            $status = 'unpaid';
            if ($paidTotal <= 0) {
                $status = 'unpaid';
            } elseif ($balanceDue <= 0) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }

            $sale->paid_total = $paidTotal;
            $sale->balance_due = $balanceDue;
            $sale->payment_status = $status;
            $sale->save();
        });
    }
}
