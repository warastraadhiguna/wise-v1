<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Pos\Support\PurchaseTotalsCalculator;
use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\PurchaseDetail;
use App\Models\SaleDetail;
use Illuminate\Validation\ValidationException;

class AllocateSaleDetailFifoCost
{
    /**
     * @return array{total_cost: float, margin: float, allocations: array<int, array{purchase_detail_id:int, qty:float, unit_cost:float, total_cost:float}>}
     */
    public function handle(SaleDetail $saleDetail): array
    {
        $requiredQty = round(max(0, (float) $saleDetail->qty), 4);

        if ($requiredQty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Qty sale harus lebih dari 0 untuk alokasi FIFO.',
            ]);
        }

        $remainingQty = $requiredQty;
        $totalCost = 0.0;
        $allocations = [];

        $layers = PurchaseDetail::query()
            ->select('purchase_details.*')
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->where('purchase_details.product_id', $saleDetail->product_id)
            ->where('purchase_details.remaining_qty', '>', 0)
            ->where('purchases.status', 'posted')
            ->whereNull('purchase_details.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->orderBy('purchases.purchase_date')
            ->orderBy('purchases.id')
            ->orderBy('purchase_details.id')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {
            if ($remainingQty <= 0) {
                break;
            }

            $availableQty = round(max(0, (float) $layer->remaining_qty), 4);

            if ($availableQty <= 0) {
                continue;
            }

            $takenQty = round(min($remainingQty, $availableQty), 4);
            $unitCost = $this->resolveUnitCost($layer);
            $layerCost = round($takenQty * $unitCost, 4);

            $layer->remaining_qty = round($availableQty - $takenQty, 4);
            $layer->save();

            $allocations[] = [
                'purchase_detail_id' => (int) $layer->getKey(),
                'qty' => $takenQty,
                'unit_cost' => $unitCost,
                'total_cost' => $layerCost,
            ];

            $remainingQty = round($remainingQty - $takenQty, 4);
            $totalCost = round($totalCost + $layerCost, 4);
        }

        if ($remainingQty > 0) {
            $productName = $saleDetail->product?->name ?? ('ID ' . $saleDetail->product_id);

            throw ValidationException::withMessages([
                'details' => "Layer FIFO tidak cukup untuk produk {$productName}. Sisa kebutuhan "
                    . number_format($remainingQty, 4, ',', '.')
                    . '.',
            ]);
        }

        $saleLineRevenue = SaleTotalsCalculator::lineTotal(
            $saleDetail->qty,
            $saleDetail->price,
            $saleDetail->discount_percent,
            $saleDetail->discount_amount,
        );

        return [
            'total_cost' => round($totalCost, 4),
            'margin' => round($saleLineRevenue - $totalCost, 4),
            'allocations' => $allocations,
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
