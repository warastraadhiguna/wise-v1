<?php

namespace App\Domain\Pos\Actions;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
class RecalculatePurchasePaymentSummary
{
    public function handle(int $purchaseId): void
    {
        DB::transaction(function () use ($purchaseId) {
            $purchase = Purchase::whereKey($purchaseId)->lockForUpdate()->firstOrFail();

            $paidTotal = (float) $purchase->payments()->sum('amount');
            $grandTotal = (float) $purchase->grand_total;

            $balanceDue = $grandTotal - $paidTotal;

            $status = 'unpaid';
            if ($paidTotal <= 0) $status = 'unpaid';
            elseif ($balanceDue <= 0) $status = 'paid';
            else $status = 'partial';

            $purchase->paid_total = $paidTotal;
            $purchase->balance_due = $balanceDue;
            $purchase->payment_status = $status;
            $purchase->save();
        });
    }
}