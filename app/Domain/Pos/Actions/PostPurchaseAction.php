<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Pos\Support\PurchasePaymentValidator;
use App\Domain\Pos\Support\PurchaseTotalsCalculator;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostPurchaseAction
{
    public function handle(int $purchaseId, int $userId): void
    {
        DB::transaction(function () use ($purchaseId, $userId) {

            // 1) Lock header (hindari double post)
            $purchase = Purchase::whereKey($purchaseId)->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'posted') {
                throw ValidationException::withMessages(['status' => 'Purchase sudah diposting.']);
            }

            $purchase->load(['details', 'payments']);

            if ($purchase->details->isEmpty()) {
                throw ValidationException::withMessages(['details' => 'Detail pembelian masih kosong.']);
            }

            // 2) Hitung grand_total final (dibekukan)
            $detailsSubtotal = PurchaseTotalsCalculator::detailsSubtotal($purchase->details);

            $grandTotal = PurchaseTotalsCalculator::grandTotal(
                $detailsSubtotal,
                $purchase->discount_percent,
                $purchase->discount_amount,
                $purchase->ppn,
                $purchase->pph
            );

            PurchasePaymentValidator::assertPaymentMethodSelected($purchase->payment_method_id);

            PurchasePaymentValidator::assertCashPaymentMatchesGrandTotal(
                $purchase->payment_method_id,
                (float) ($purchase->payment_amount ?? 0),
                $grandTotal,
            );

            $purchase->grand_total = $grandTotal;

            // 3) Update stok + ledger + remaining_qty (FIFO batch)
            foreach ($purchase->details as $detail) {
                if ((float) $detail->qty <= 0) {
                    throw ValidationException::withMessages(['qty' => 'Qty harus > 0.']);
                }

                // remaining_qty dibuat saat POST (bukan saat draft)
                $detail->remaining_qty = $detail->qty;
                $detail->save();

                Stock::firstOrCreate(
                    ['product_id' => $detail->product_id],
                    ['qty_on_hand' => 0]
                );

                $stock = Stock::where('product_id', $detail->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock->qty_on_hand = round(((float) $stock->qty_on_hand + (float) $detail->qty), 4);
                $stock->save();

                StockMovement::create([
                    'product_id' => $detail->product_id,
                    'qty_change' => $detail->qty, // + masuk
                    'ref_type' => 'PURCHASE',
                    'ref_id' => $purchase->id,
                    'balance_after' => $stock->qty_on_hand,
                    'happened_at' => $purchase->purchase_date ?? now(),
                    'created_by' => $userId,
                ]);
            }

            // 4) Set posted
            $purchase->status = 'posted';
            $purchase->posted_at = now();
            $purchase->user_id = $userId;
            $purchase->save();

            // 5) Jika ada DP dari field purchase.payment_amount → buat payment pertama
            $dp = (float) ($purchase->payment_amount ?? 0);
            if ($dp > 0) {
                $purchase->payments()->create([
                    'payment_method_id' => $purchase->payment_method_id,
                    'user_id' => $userId,
                    'amount' => round($dp, 2),
                    'paid_at' => ($purchase->purchase_date ?? now())->format('Y-m-d'),
                    'reference_number' => $purchase->reference_number ?? null,
                    'note' => 'DP saat posting',
                ]);
            }

            // 6) Update ringkasan hutang (cache)
            $paidTotal = (float) $purchase->payments()->sum('amount');
            $balance = round($purchase->grand_total - $paidTotal, 2);

            $purchase->paid_total = $paidTotal;
            $purchase->balance_due = $balance;

            $purchase->payment_status =
                $paidTotal <= 0 ? 'unpaid' :
                ($balance <= 0 ? 'paid' : 'partial');

            $purchase->save();
        });
    }
}
