<?php

namespace App\Domain\Pos\Actions;

use App\Models\PurchaseDetail;
use App\Models\PurchaseReturnDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePurchaseReturnDetailAction
{
    public function handle(int $purchaseId, int $purchaseReturnDetailId): void
    {
        DB::transaction(function () use ($purchaseId, $purchaseReturnDetailId): void {
            $returnDetail = PurchaseReturnDetail::query()
                ->whereKey($purchaseReturnDetailId)
                ->with(['purchaseReturn', 'product:id,name'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) ($returnDetail->purchaseReturn?->purchase_id) !== $purchaseId) {
                abort(404);
            }

            /** @var PurchaseDetail $purchaseDetail */
            $purchaseDetail = PurchaseDetail::query()
                ->whereKey($returnDetail->purchase_detail_id)
                ->lockForUpdate()
                ->firstOrFail();

            $restoreQty = round((float) $returnDetail->qty, 4);
            $nextRemainingQty = round((float) $purchaseDetail->remaining_qty + $restoreQty, 4);

            if ($nextRemainingQty > round((float) $purchaseDetail->qty, 4)) {
                $productName = $returnDetail->product?->name ?? ('ID ' . $returnDetail->product_id);

                throw ValidationException::withMessages([
                    'purchase_return_detail_id' => "Detail retur purchase {$productName} tidak bisa dihapus karena sisa batch sudah berubah.",
                ]);
            }

            $purchaseDetail->remaining_qty = $nextRemainingQty;
            $purchaseDetail->save();

            $stock = Stock::query()
                ->where('product_id', $returnDetail->product_id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['product_id' => $returnDetail->product_id],
                    ['qty_on_hand' => 0]
                );

            $stock->qty_on_hand = round((float) $stock->qty_on_hand + $restoreQty, 4);
            $stock->save();

            StockMovement::create([
                'product_id' => $returnDetail->product_id,
                'ref_type' => 'PURCHASE_RETURN_DETAIL_DELETE',
                'ref_id' => $returnDetail->purchase_return_id,
                'direction' => 'in',
                'qty' => $restoreQty,
                'moved_at' => now(),
                'note' => 'Rollback hapus detail retur purchase ' . ($returnDetail->purchaseReturn?->number ?? $returnDetail->purchase_return_id),
            ]);

            $purchaseReturn = $returnDetail->purchaseReturn;
            $returnDetail->delete();

            if (! $purchaseReturn) {
                return;
            }

            $remainingTotal = round((float) $purchaseReturn->details()->sum('subtotal'), 2);

            if ((float) $remainingTotal <= 0) {
                $purchaseReturn->delete();

                return;
            }

            $purchaseReturn->total_amount = $remainingTotal;
            $purchaseReturn->save();
        });
    }
}
