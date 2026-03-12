<?php

namespace App\Domain\Pos\Actions;

use App\Models\PurchaseDetail;
use App\Models\SaleReturnDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSaleReturnDetailAction
{
    public function handle(int $saleId, int $saleReturnDetailId): void
    {
        DB::transaction(function () use ($saleId, $saleReturnDetailId): void {
            $returnDetail = SaleReturnDetail::query()
                ->whereKey($saleReturnDetailId)
                ->with(['saleReturn', 'product:id,name', 'allocations'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) ($returnDetail->saleReturn?->sale_id) !== $saleId) {
                abort(404);
            }

            $rollbackQty = round((float) $returnDetail->qty, 4);

            $stock = Stock::query()
                ->where('product_id', $returnDetail->product_id)
                ->lockForUpdate()
                ->first();

            if (! $stock || round((float) $stock->qty_on_hand, 4) < $rollbackQty) {
                $productName = $returnDetail->product?->name ?? ('ID ' . $returnDetail->product_id);

                throw ValidationException::withMessages([
                    'sale_return_detail_id' => "Detail retur sale {$productName} tidak bisa dihapus karena stok saat ini sudah tidak mencukupi.",
                ]);
            }

            foreach ($returnDetail->allocations as $allocation) {
                /** @var PurchaseDetail $purchaseDetail */
                $purchaseDetail = PurchaseDetail::query()
                    ->whereKey($allocation->purchase_detail_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $allocationQty = round((float) $allocation->qty, 4);

                if (round((float) $purchaseDetail->remaining_qty, 4) < $allocationQty) {
                    $productName = $returnDetail->product?->name ?? ('ID ' . $returnDetail->product_id);

                    throw ValidationException::withMessages([
                        'sale_return_detail_id' => "Detail retur sale {$productName} tidak bisa dihapus karena stok batch sudah terpakai lagi.",
                    ]);
                }

                $purchaseDetail->remaining_qty = round((float) $purchaseDetail->remaining_qty - $allocationQty, 4);
                $purchaseDetail->save();
            }

            $stock->qty_on_hand = round((float) $stock->qty_on_hand - $rollbackQty, 4);
            $stock->save();

            StockMovement::create([
                'product_id' => $returnDetail->product_id,
                'ref_type' => 'SALE_RETURN_DETAIL_DELETE',
                'ref_id' => $returnDetail->sale_return_id,
                'direction' => 'out',
                'qty' => $rollbackQty,
                'moved_at' => now(),
                'note' => 'Rollback hapus detail retur sale ' . ($returnDetail->saleReturn?->number ?? $returnDetail->sale_return_id),
            ]);

            $saleReturn = $returnDetail->saleReturn;
            $returnDetail->allocations()->delete();
            $returnDetail->delete();

            if (! $saleReturn) {
                return;
            }

            $remainingTotal = round((float) $saleReturn->details()->sum('subtotal'), 2);

            if ((float) $remainingTotal <= 0) {
                $saleReturn->delete();

                return;
            }

            $saleReturn->total_amount = $remainingTotal;
            $saleReturn->save();
        });
    }
}
