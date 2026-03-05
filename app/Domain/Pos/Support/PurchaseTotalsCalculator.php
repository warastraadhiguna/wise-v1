<?php

namespace App\Domain\Pos\Support;

class PurchaseTotalsCalculator
{
    public static function lineTotal($qty, $price, $discPercent, $discAmount): float
    {
        $qty = (float) $qty;
        $price = (float) $price;
        $discPercent = (float) ($discPercent ?? 0);
        $discAmount = (float) ($discAmount ?? 0);

        $gross = max(0, $qty) * max(0, $price);
        $discPctAmt = $gross * max(0, $discPercent) / 100;

        $net = $gross - $discPctAmt - max(0, $discAmount);
        return max(0, round($net, 2));
    }

    /** @param iterable $details (PurchaseDetail collection/array) */
    public static function detailsSubtotal(iterable $details): float
    {
        $sum = 0.0;
        foreach ($details as $d) {
            $sum += self::lineTotal(
                $d->qty ?? 0,
                $d->price ?? 0,
                $d->discount_percent ?? 0,
                $d->discount_amount ?? 0,
            );
        }
        return max(0, round($sum, 2));
    }

    public static function grandTotal(
        float $detailsSubtotal,
        $headerDiscPercent,
        $headerDiscAmount,
        $ppnPercent,
        $pphPercent
    ): float {
        $headerDiscPercent = (float) ($headerDiscPercent ?? 0);
        $headerDiscAmount  = (float) ($headerDiscAmount ?? 0);
        $ppnPercent        = (float) ($ppnPercent ?? 0);
        $pphPercent        = (float) ($pphPercent ?? 0);

        $headerDiscPctAmt = $detailsSubtotal * max(0, $headerDiscPercent) / 100;
        $afterDisc = $detailsSubtotal - $headerDiscPctAmt - max(0, $headerDiscAmount);
        $afterDisc = max(0, $afterDisc);

        $ppnAmt = $afterDisc * max(0, $ppnPercent) / 100;
        $pphAmt = $afterDisc * max(0, $pphPercent) / 100;

        // mengikuti kode kamu: total = afterDisc + ppn + pph
        return max(0, round($afterDisc + $ppnAmt + $pphAmt, 2));
    }
}