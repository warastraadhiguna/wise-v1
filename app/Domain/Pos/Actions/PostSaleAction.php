<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Pos\Support\SalePaymentValidator;
use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSaleAction
{
    public function __construct(
        protected AllocateSaleDetailFifoCost $allocateSaleDetailFifoCost,
    ) {}

    public function handle(int $saleId, int $userId): void
    {
        DB::transaction(function () use ($saleId, $userId) {
            $sale = Sale::whereKey($saleId)->lockForUpdate()->firstOrFail();

            if ($sale->status === 'posted') {
                throw ValidationException::withMessages(['status' => 'Sale sudah diposting.']);
            }

            $sale->load(['details.product:id,name', 'payments']);

            if ($sale->details->isEmpty()) {
                throw ValidationException::withMessages(['details' => 'Detail penjualan masih kosong.']);
            }

            $detailsSubtotal = SaleTotalsCalculator::detailsSubtotal($sale->details);

            $grandTotal = SaleTotalsCalculator::grandTotal(
                $detailsSubtotal,
                $sale->discount_percent,
                $sale->discount_amount,
                $sale->ppn,
                $sale->pph,
            );

            SalePaymentValidator::assertPaymentMethodSelected($sale->payment_method_id);

            SalePaymentValidator::assertCashPaymentMatchesGrandTotal(
                $sale->payment_method_id,
                (float) ($sale->payment_amount ?? 0),
                $grandTotal,
            );

            $sale->grand_total = $grandTotal;

            foreach ($sale->details as $detail) {
                $qty = (float) $detail->qty;

                if ($qty <= 0) {
                    throw ValidationException::withMessages(['qty' => 'Qty harus > 0.']);
                }

                $stock = Stock::where('product_id', $detail->product_id)
                    ->lockForUpdate()
                    ->first();

                $qtyOnHand = (float) ($stock?->qty_on_hand ?? 0);

                if ($qtyOnHand < $qty) {
                    $productName = $detail->product?->name ?? ('ID ' . $detail->product_id);

                    throw ValidationException::withMessages([
                        'details' => "Stok tidak cukup untuk produk {$productName}. Tersedia "
                            . number_format($qtyOnHand, 4, ',', '.')
                            . ', diminta '
                            . number_format($qty, 4, ',', '.')
                            . '.',
                    ]);
                }

                $newBalance = round($qtyOnHand - $qty, 4);

                $fifoResult = $this->allocateSaleDetailCost($detail);

                $detail->remaining_qty = $detail->qty;
                $detail->fifo_cost_amount = $fifoResult['total_cost'];
                $detail->margin_amount = $fifoResult['margin'];
                $detail->save();

                $detail->fifoAllocations()->delete();
                $detail->fifoAllocations()->createMany($fifoResult['allocations']);

                if (! $stock) {
                    throw ValidationException::withMessages([
                        'details' => 'Data stok produk belum tersedia.',
                    ]);
                }

                $stock->qty_on_hand = $newBalance;
                $stock->save();

                StockMovement::create([
                    'product_id' => $detail->product_id,
                    'qty_change' => -$qty,
                    'ref_type' => 'SALE',
                    'ref_id' => $sale->id,
                    'balance_after' => $stock->qty_on_hand,
                    'happened_at' => $sale->sale_date ?? now(),
                    'created_by' => $userId,
                ]);
            }

            $sale->status = 'posted';
            $sale->posted_at = now();
            $sale->user_id = $userId;
            $sale->save();

            $dp = max(0, (float) ($sale->payment_amount ?? 0));
            $kembalian = max(0, $dp - (float) $sale->grand_total);

            if ($dp > 0) {
                $sale->payments()->create([
                    'payment_method_id' => $sale->payment_method_id,
                    'user_id' => $userId,
                    'amount' => round($dp, 2),
                    'paid_at' => ($sale->sale_date ?? now())->format('Y-m-d'),
                    'reference_number' => $sale->reference_number ?? null,
                    'note' => $kembalian > 0
                        ? 'Pembayaran awal saat posting (Kembalian: ' . number_format($kembalian, 0, ',', '.') . ')'
                        : 'Pembayaran awal saat posting',
                ]);
            }

            $paidTotal = (float) $sale->payments()->sum('amount');
            $balance = max(0, round($sale->grand_total - $paidTotal, 2));

            $sale->paid_total = $paidTotal;
            $sale->balance_due = $balance;
            $sale->payment_status =
                $paidTotal <= 0 ? 'unpaid' :
                ($balance <= 0 ? 'paid' : 'partial');
            $sale->save();
        });
    }

    /**
     * @return array{total_cost: float, margin: float, allocations: array<int, array<string, float|int>>}
     */
    protected function allocateSaleDetailCost(SaleDetail $detail): array
    {
        return $this->allocateSaleDetailFifoCost->handle($detail);
    }
}
