@php
    $lineTotal = static function ($detail): float {
        $qty = max(0, (float) $detail->qty);
        $price = max(0, (float) $detail->price);
        $discountPercent = max(0, (float) $detail->discount_percent);
        $discountAmount = max(0, (float) $detail->discount_amount);

        $gross = $qty * $price;
        $discountPercentAmount = $gross * $discountPercent / 100;

        return max(0, $gross - $discountPercentAmount - $discountAmount);
    };

    $detailsSubtotal = (float) $purchase->details->sum(fn ($detail) => $lineTotal($detail));
    $headerDiscountPercent = max(0, (float) $purchase->discount_percent);
    $headerDiscountAmount = max(0, (float) $purchase->discount_amount);
    $ppnPercent = max(0, (float) $purchase->ppn);
    $pphPercent = max(0, (float) $purchase->pph);

    $headerDiscountPercentAmount = $detailsSubtotal * $headerDiscountPercent / 100;
    $afterHeaderDiscount = max(0, $detailsSubtotal - $headerDiscountPercentAmount - $headerDiscountAmount);
    $ppnAmount = $afterHeaderDiscount * $ppnPercent / 100;
    $pphAmount = $afterHeaderDiscount * $pphPercent / 100;
    $grandTotal = max(0, $afterHeaderDiscount + $ppnAmount + $pphAmount);

    $format = static fn ($value): string => number_format((float) $value, 0, ',', '.');
@endphp

<div style="font-size: 14px; color: #111827;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 12px;">
        <div style="font-size: 18px; font-weight: 700;">Detail Purchase {{ $purchase->number }}</div>
        <div style="padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: {{ $purchase->status === 'posted' ? '#dcfce7' : ($purchase->status === 'draft' ? '#fef3c7' : '#fee2e2') }}; color: {{ $purchase->status === 'posted' ? '#166534' : ($purchase->status === 'draft' ? '#92400e' : '#991b1b') }};">
            {{ $purchase->status }}
        </div>
    </div>

    <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr>
                    <th style="width: 18%; background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">No Nota</th>
                    <td style="width: 32%; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $purchase->number }}</td>
                    <th style="width: 18%; background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Supplier</th>
                    <td style="width: 32%; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $purchase->supplier?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Tanggal Pembelian</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Jatuh Tempo</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $purchase->due_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Cara Bayar</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $purchase->paymentMethod?->name ?? '-' }}</td>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Jumlah Bayar</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 700;">{{ $format($purchase->payment_amount) }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px;">Catatan</th>
                    <td colspan="3" style="padding: 8px 10px;">{{ filled($purchase->note) ? $purchase->note : '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 8px 10px; text-align: center; width: 48px; border-bottom: 1px solid #e5e7eb;">No</th>
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Produk</th>
                    <th style="padding: 8px 10px; text-align: right; width: 90px; border-bottom: 1px solid #e5e7eb;">Qty</th>
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">Harga</th>
                    <th style="padding: 8px 10px; text-align: right; width: 90px; border-bottom: 1px solid #e5e7eb;">Disc %</th>
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">Disc Rp</th>
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchase->details as $index => $detail)
                    <tr>
                        <td style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #f3f4f6;">{{ $index + 1 }}</td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-weight: 700;">{{ $detail->product?->name ?? '-' }}</div>
                            <div style="font-size: 12px; color: #6b7280;">{{ $detail->product?->code ?? '-' }}</div>
                        </td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->qty) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->price) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->discount_percent) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->discount_amount) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 700;">{{ $format($lineTotal($detail)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 16px 10px; text-align: center; color: #6b7280;">Detail purchase kosong.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end;">
        <table style="width: 100%; max-width: 440px; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
            <tbody>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">Subtotal</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($detailsSubtotal) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">Disc Header (%)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($headerDiscountPercent) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">Disc Header (Rp)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($headerDiscountAmount) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">PPN (%)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($ppnPercent) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">PPH (%)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($pphPercent) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">Total Setelah Disc Header</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($afterHeaderDiscount) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">PPN (Rp)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($ppnAmount) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">PPH (Rp)</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($pphAmount) }}</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left; background: #eff6ff; color: #1e3a8a; font-size: 16px;">Grand Total</th>
                    <td style="padding: 10px; text-align: right; background: #eff6ff; color: #1e3a8a; font-size: 24px; font-weight: 800;">{{ $format($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
