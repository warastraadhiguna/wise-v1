<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleDetailFifoAllocation;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleReturnAction
{
    public function handle(int $saleId, array $data, int $userId): SaleReturn
    {
        return DB::transaction(function () use ($saleId, $data, $userId): SaleReturn {
            $sale = Sale::whereKey($saleId)
                ->lockForUpdate()
                ->with(['details.product:id,name', 'details.fifoAllocations'])
                ->firstOrFail();

            if ($sale->status !== 'posted') {
                throw ValidationException::withMessages([
                    'sale' => 'Retur hanya bisa dibuat dari sale yang sudah diposting.',
                ]);
            }

            $rows = $this->normalizeRows($data['details'] ?? []);

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'details' => 'Pilih minimal satu item retur.',
                ]);
            }

            $returnDate = (string) ($data['return_date'] ?? now()->toDateString());

            $saleReturn = SaleReturn::create([
                'user_id' => $userId,
                'sale_id' => $sale->id,
                'number' => $this->generateNumber($returnDate),
                'return_date' => $returnDate,
                'reason' => (string) ($data['reason'] ?? ''),
                'total_amount' => 0,
                'posted_at' => now(),
            ]);

            $totalAmount = 0.0;

            foreach ($rows as $row) {
                /** @var SaleDetail $detail */
                $detail = SaleDetail::query()
                    ->whereKey($row['sale_detail_id'])
                    ->with(['product:id,name', 'fifoAllocations'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $detail->sale_id !== (int) $sale->id) {
                    throw ValidationException::withMessages([
                        'details' => 'Item retur tidak cocok dengan sale asal.',
                    ]);
                }

                $availableQty = round((float) $detail->qty - $this->getReturnedQty($detail->id), 4);
                $returnQty = round((float) $row['qty'], 4);

                if ($returnQty <= 0 || $returnQty > $availableQty) {
                    $productName = $detail->product?->name ?? ('ID ' . $detail->product_id);

                    throw ValidationException::withMessages([
                        'details' => "Qty retur sale untuk produk {$productName} melebihi qty yang masih bisa diretur.",
                    ]);
                }

                $subtotal = round($returnQty * $this->resolveUnitNetPrice($detail), 4);

                $returnDetail = $saleReturn->details()->create([
                    'sale_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'qty' => $returnQty,
                    'price' => $detail->price,
                    'discount_percent' => $detail->discount_percent,
                    'discount_amount' => $detail->discount_amount,
                    'subtotal' => $subtotal,
                    'fifo_cost_amount' => 0,
                ]);

                $remainingRestoreQty = $returnQty;
                $restoredCost = 0.0;

                $allocations = SaleDetailFifoAllocation::query()
                    ->where('sale_detail_id', $detail->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($allocations as $allocation) {
                    if ($remainingRestoreQty <= 0) {
                        break;
                    }

                    $alreadyReturnedQty = round((float) $allocation->returnAllocations()->sum('qty'), 4);
                    $availableAllocationQty = round((float) $allocation->qty - $alreadyReturnedQty, 4);

                    if ($availableAllocationQty <= 0) {
                        continue;
                    }

                    $restoreQty = round(min($remainingRestoreQty, $availableAllocationQty), 4);

                    $purchaseDetail = PurchaseDetail::query()
                        ->whereKey($allocation->purchase_detail_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $purchaseDetail->remaining_qty = round((float) $purchaseDetail->remaining_qty + $restoreQty, 4);
                    $purchaseDetail->save();

                    $restoreCost = round($restoreQty * (float) $allocation->unit_cost, 4);

                    $returnDetail->allocations()->create([
                        'sale_detail_fifo_allocation_id' => $allocation->id,
                        'purchase_detail_id' => $allocation->purchase_detail_id,
                        'qty' => $restoreQty,
                        'unit_cost' => $allocation->unit_cost,
                        'total_cost' => $restoreCost,
                    ]);

                    $restoredCost = round($restoredCost + $restoreCost, 4);
                    $remainingRestoreQty = round($remainingRestoreQty - $restoreQty, 4);
                }

                if ($remainingRestoreQty > 0) {
                    throw ValidationException::withMessages([
                        'details' => 'Alokasi FIFO retur sale tidak lengkap.',
                    ]);
                }

                $returnDetail->fifo_cost_amount = $restoredCost;
                $returnDetail->save();

                $stock = Stock::firstOrCreate(
                    ['product_id' => $detail->product_id],
                    ['qty_on_hand' => 0],
                );

                $stock = Stock::query()
                    ->whereKey($stock->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock->qty_on_hand = round((float) $stock->qty_on_hand + $returnQty, 4);
                $stock->save();

                StockMovement::create([
                    'product_id' => $detail->product_id,
                    'qty_change' => $returnQty,
                    'ref_type' => 'SALE_RETURN',
                    'ref_id' => $saleReturn->id,
                    'balance_after' => $stock->qty_on_hand,
                    'happened_at' => $returnDate,
                    'created_by' => $userId,
                ]);

                $totalAmount = round($totalAmount + $subtotal, 2);
            }

            $saleReturn->total_amount = $totalAmount;
            $saleReturn->save();

            return $saleReturn;
        });
    }

    protected function normalizeRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && filled($row['sale_detail_id'] ?? null) && (float) ($row['qty'] ?? 0) > 0)
            ->groupBy(fn ($row) => (int) $row['sale_detail_id'])
            ->map(fn ($group, $saleDetailId) => [
                'sale_detail_id' => (int) $saleDetailId,
                'qty' => round((float) collect($group)->sum(fn ($row) => (float) ($row['qty'] ?? 0)), 4),
            ])
            ->values()
            ->all();
    }

    protected function resolveUnitNetPrice(SaleDetail $detail): float
    {
        $qty = max(0.0001, (float) $detail->qty);
        $lineTotal = SaleTotalsCalculator::lineTotal(
            $detail->qty,
            $detail->price,
            $detail->discount_percent,
            $detail->discount_amount,
        );

        return round($lineTotal / $qty, 4);
    }

    protected function getReturnedQty(int $saleDetailId): float
    {
        return round((float) DB::table('sale_return_details')
            ->where('sale_detail_id', $saleDetailId)
            ->whereNull('deleted_at')
            ->sum('qty'), 4);
    }

    protected function generateNumber(string $returnDate): string
    {
        $datePart = Carbon::parse($returnDate)->format('Y/m/d');
        $prefix = 'RJ/' . $datePart . '/';

        $lastNumber = SaleReturn::withTrashed()
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');

        $next = 1;

        if (is_string($lastNumber) && preg_match('/(\d{4})$/', $lastNumber, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
