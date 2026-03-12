<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Pos\Support\PurchaseTotalsCalculator;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseReturn;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseReturnAction
{
    public function handle(int $purchaseId, array $data, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseId, $data, $userId): PurchaseReturn {
            $purchase = Purchase::whereKey($purchaseId)
                ->lockForUpdate()
                ->with(['details.product:id,name'])
                ->firstOrFail();

            if ($purchase->status !== 'posted') {
                throw ValidationException::withMessages([
                    'purchase' => 'Retur hanya bisa dibuat dari purchase yang sudah diposting.',
                ]);
            }

            $rows = $this->normalizeRows($data['details'] ?? []);

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'details' => 'Pilih minimal satu item retur.',
                ]);
            }

            $returnDate = (string) ($data['return_date'] ?? now()->toDateString());

            $purchaseReturn = PurchaseReturn::create([
                'user_id' => $userId,
                'purchase_id' => $purchase->id,
                'number' => $this->generateNumber($returnDate),
                'return_date' => $returnDate,
                'reason' => (string) ($data['reason'] ?? ''),
                'total_amount' => 0,
                'posted_at' => now(),
            ]);

            $totalAmount = 0.0;

            foreach ($rows as $row) {
                /** @var PurchaseDetail $detail */
                $detail = PurchaseDetail::query()
                    ->whereKey($row['purchase_detail_id'])
                    ->lockForUpdate()
                    ->with('product:id,name')
                    ->firstOrFail();

                if ((int) $detail->purchase_id !== (int) $purchase->id) {
                    throw ValidationException::withMessages([
                        'details' => 'Item retur tidak cocok dengan purchase asal.',
                    ]);
                }

                $availableQty = round(max(0, (float) $detail->remaining_qty), 4);
                $returnQty = round((float) $row['qty'], 4);

                if ($returnQty <= 0 || $returnQty > $availableQty) {
                    $productName = $detail->product?->name ?? ('ID ' . $detail->product_id);

                    throw ValidationException::withMessages([
                        'details' => "Qty retur purchase untuk produk {$productName} melebihi sisa batch yang tersedia.",
                    ]);
                }

                $stock = Stock::query()
                    ->where('product_id', $detail->product_id)
                    ->lockForUpdate()
                    ->first();

                $stockOnHand = (float) ($stock?->qty_on_hand ?? 0);

                if ($stockOnHand < $returnQty) {
                    throw ValidationException::withMessages([
                        'details' => 'Stok tidak cukup untuk diproses sebagai retur pembelian.',
                    ]);
                }

                $unitNetPrice = $this->resolveUnitNetPrice($detail);
                $subtotal = round($returnQty * $unitNetPrice, 4);

                $detail->remaining_qty = round($availableQty - $returnQty, 4);
                $detail->save();

                $stock->qty_on_hand = round($stockOnHand - $returnQty, 4);
                $stock->save();

                $purchaseReturn->details()->create([
                    'purchase_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'qty' => $returnQty,
                    'price' => $detail->price,
                    'discount_percent' => $detail->discount_percent,
                    'discount_amount' => $detail->discount_amount,
                    'subtotal' => $subtotal,
                ]);

                StockMovement::create([
                    'product_id' => $detail->product_id,
                    'qty_change' => -$returnQty,
                    'ref_type' => 'PURCHASE_RETURN',
                    'ref_id' => $purchaseReturn->id,
                    'balance_after' => $stock->qty_on_hand,
                    'happened_at' => $returnDate,
                    'created_by' => $userId,
                ]);

                $totalAmount = round($totalAmount + $subtotal, 2);
            }

            $purchaseReturn->total_amount = $totalAmount;
            $purchaseReturn->save();

            return $purchaseReturn;
        });
    }

    protected function normalizeRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && filled($row['purchase_detail_id'] ?? null) && (float) ($row['qty'] ?? 0) > 0)
            ->groupBy(fn ($row) => (int) $row['purchase_detail_id'])
            ->map(fn ($group, $purchaseDetailId) => [
                'purchase_detail_id' => (int) $purchaseDetailId,
                'qty' => round((float) collect($group)->sum(fn ($row) => (float) ($row['qty'] ?? 0)), 4),
            ])
            ->values()
            ->all();
    }

    protected function resolveUnitNetPrice(PurchaseDetail $detail): float
    {
        $qty = max(0.0001, (float) $detail->qty);
        $lineTotal = PurchaseTotalsCalculator::lineTotal(
            $detail->qty,
            $detail->price,
            $detail->discount_percent,
            $detail->discount_amount,
        );

        return round($lineTotal / $qty, 4);
    }

    protected function generateNumber(string $returnDate): string
    {
        $datePart = Carbon::parse($returnDate)->format('Y/m/d');
        $prefix = 'RB/' . $datePart . '/';

        $lastNumber = PurchaseReturn::withTrashed()
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
