<?php

namespace App\Domain\Pos\Support;

use App\Models\PaymentMethod;
use Illuminate\Validation\ValidationException;

class SalePaymentValidator
{
    protected static array $paymentMethodCashCache = [];

    public static function normalizeAndValidateDraft(array $data): array
    {
        self::assertPaymentMethodSelected($data['payment_method_id'] ?? null);

        $grandTotal = self::calculateGrandTotalFromFormData($data);
        $paymentAmount = round((float) ($data['payment_amount'] ?? 0), 2);

        self::assertCashPaymentMatchesGrandTotal(
            $data['payment_method_id'] ?? null,
            $paymentAmount,
            $grandTotal,
        );

        $data['grand_total'] = $grandTotal;

        return $data;
    }

    public static function assertPaymentMethodSelected(mixed $paymentMethodId): void
    {
        if (filled($paymentMethodId)) {
            return;
        }

        throw ValidationException::withMessages([
            'payment_method_id' => 'Cara bayar wajib dipilih.',
        ]);
    }

    public static function assertCashPaymentMatchesGrandTotal(
        mixed $paymentMethodId,
        float $paymentAmount,
        float $grandTotal,
    ): void {
        if (! self::isCashPaymentMethod($paymentMethodId)) {
            return;
        }

        $paymentAmount = round($paymentAmount, 2);
        $grandTotal = round($grandTotal, 2);

        if ($paymentAmount + 0.0001 >= $grandTotal) {
            return;
        }

        throw ValidationException::withMessages([
            'payment_amount' => 'Untuk pembayaran tunai, jumlah bayar minimal sama dengan grand total ('
                . number_format($grandTotal, 0, ',', '.')
                . ').',
        ]);
    }

    public static function calculateGrandTotalFromFormData(array $data): float
    {
        $detailsSubtotal = 0.0;

        foreach (($data['details'] ?? []) as $detail) {
            $detailsSubtotal += SaleTotalsCalculator::lineTotal(
                $detail['qty'] ?? 0,
                $detail['price'] ?? 0,
                $detail['discount_percent'] ?? 0,
                $detail['discount_amount'] ?? 0,
            );
        }

        return SaleTotalsCalculator::grandTotal(
            $detailsSubtotal,
            $data['discount_percent'] ?? 0,
            $data['discount_amount'] ?? 0,
            $data['ppn'] ?? 0,
            $data['pph'] ?? 0,
        );
    }

    protected static function isCashPaymentMethod(mixed $paymentMethodId): bool
    {
        if (blank($paymentMethodId)) {
            return true;
        }

        $paymentMethodId = (int) $paymentMethodId;

        if (array_key_exists($paymentMethodId, self::$paymentMethodCashCache)) {
            return self::$paymentMethodCashCache[$paymentMethodId];
        }

        $value = PaymentMethod::query()
            ->whereKey($paymentMethodId)
            ->value('is_cash');

        return self::$paymentMethodCashCache[$paymentMethodId] = $value === null ? true : (bool) $value;
    }
}
