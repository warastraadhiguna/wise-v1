<?php

namespace App\Domain\Pos\Actions;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PostPurchaseAction
{
    public function handle(Purchase $purchase, ?int $userId = null): void
    {
        DB::transaction(function () use ($purchase, $userId) {

            // lock purchase header biar tidak kepost 2x
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === 'posted') {
                throw ValidationException::withMessages([
                    'status' => 'Purchase sudah pernah diposting.',
                ]);
            }

            $purchase->load('details'); // pastikan relasi details ada

            foreach ($purchase->details as $detail) {
                // set remaining_qty (batch FIFO)
                $detail->remaining_qty = $detail->qty;
                $detail->save();

                // pastikan row stock ada
                Stock::firstOrCreate(
                    ['product_id' => $detail->product_id],
                    ['qty_on_hand' => 0]
                );

                // lock stock row lalu update saldo
                $stock = Stock::where('product_id', $detail->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock->qty_on_hand = bcadd((string) $stock->qty_on_hand, (string) $detail->qty, 4);
                $stock->save();

                // ledger
                StockMovement::create([
                    'product_id'     => $detail->product_id,
                    'qty_change'     => $detail->qty, // + masuk
                    'ref_type'       => 'PURCHASE',
                    'ref_id'         => $purchase->id,
                    'balance_after'  => $stock->qty_on_hand, // optional
                    'happened_at'    => $purchase->date ?? now(),
                    'created_by'     => $userId,
                ]);
            }

            $purchase->status = 'posted';
            $purchase->posted_at = now();
            $purchase->user_id = $userId;
            $purchase->save();
        });
    }
}