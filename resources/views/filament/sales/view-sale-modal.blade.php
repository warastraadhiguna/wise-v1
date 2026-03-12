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

    $detailsSubtotal = (float) $sale->details->sum(fn ($detail) => $lineTotal($detail));
    $headerDiscountPercent = max(0, (float) $sale->discount_percent);
    $headerDiscountAmount = max(0, (float) $sale->discount_amount);
    $ppnPercent = max(0, (float) $sale->ppn);
    $pphPercent = max(0, (float) $sale->pph);

    $headerDiscountPercentAmount = $detailsSubtotal * $headerDiscountPercent / 100;
    $afterHeaderDiscount = max(0, $detailsSubtotal - $headerDiscountPercentAmount - $headerDiscountAmount);
    $ppnAmount = $afterHeaderDiscount * $ppnPercent / 100;
    $pphAmount = $afterHeaderDiscount * $pphPercent / 100;
    $grandTotal = max(0, $afterHeaderDiscount + $ppnAmount + $pphAmount);
    $totalFifoCost = (float) $sale->details->sum(fn ($detail) => (float) ($detail->fifo_cost_amount ?? 0));
    $totalMargin = (float) $sale->details->sum(fn ($detail) => (float) ($detail->margin_amount ?? 0));

    $format = static fn ($value): string => number_format((float) $value, 0, ',', '.');
@endphp

<div style="font-size: 14px; color: #111827;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 12px;">
        <div style="font-size: 18px; font-weight: 700;">Detail Sale {{ $sale->number }}</div>
        <div style="padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: {{ $sale->status === 'posted' ? '#dcfce7' : ($sale->status === 'draft' ? '#fef3c7' : '#fee2e2') }}; color: {{ $sale->status === 'posted' ? '#166534' : ($sale->status === 'draft' ? '#92400e' : '#991b1b') }};">
            {{ $sale->status }}
        </div>
    </div>

    <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr>
                    <th style="width: 18%; background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">No Nota</th>
                    <td style="width: 32%; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $sale->number }}</td>
                    <th style="width: 18%; background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Customer</th>
                    <td style="width: 32%; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $sale->customer?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Tanggal Penjualan</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $sale->sale_date?->format('d/m/Y') ?? '-' }}</td>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Jatuh Tempo</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $sale->due_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Cara Bayar</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">{{ $sale->paymentMethod?->name ?? '-' }}</td>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">Jumlah Bayar</th>
                    <td style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 700;">{{ $format($sale->payment_amount) }}</td>
                </tr>
                <tr>
                    <th style="background: #f9fafb; text-align: left; padding: 8px 10px;">Catatan</th>
                    <td colspan="3" style="padding: 8px 10px;">{{ filled($sale->note) ? $sale->note : '-' }}</td>
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
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">HPP FIFO</th>
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">Margin</th>
                    <th style="padding: 8px 10px; text-align: right; width: 130px; border-bottom: 1px solid #e5e7eb;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sale->details as $index => $detail)
                    <tr>
                        <td style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #f3f4f6;">{{ $index + 1 }}</td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-weight: 700;">{{ $detail->product?->name ?? '-' }}</div>
                            <div style="font-size: 12px; color: #6b7280;">{{ $detail->product?->code ?? '-' }}</div>
                            @if ($detail->fifoAllocations->isNotEmpty())
                                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    FIFO:
                                    {{ $detail->fifoAllocations
                                        ->map(fn ($allocation) => $format($allocation->qty) . ' x ' . $format($allocation->unit_cost))
                                        ->implode(' + ') }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->qty) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->price) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->discount_percent) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->discount_amount) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6;">{{ $format($detail->fifo_cost_amount) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 700; color: {{ (float) $detail->margin_amount < 0 ? '#b91c1c' : '#166534' }};">{{ $format($detail->margin_amount) }}</td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 700;">{{ $format($lineTotal($detail)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 16px 10px; text-align: center; color: #6b7280;">Detail sale kosong.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($sale->returns->isNotEmpty())
        <div style="margin-bottom: 14px;">
            @include('filament.sales.partials.return-history', ['sale' => $sale, 'returns' => $sale->returns->sortByDesc('return_date')->values(), 'showDelete' => true])
        </div>
    @endif

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
                    <th style="padding: 7px 10px; text-align: left; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">Total HPP FIFO</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $format($totalFifoCost) }}</td>
                </tr>
                <tr>
                    <th style="padding: 7px 10px; text-align: left; background: #f0fdf4; border-bottom: 1px solid #e5e7eb; color: #166534;">Total Margin</th>
                    <td style="padding: 7px 10px; text-align: right; border-bottom: 1px solid #e5e7eb; color: {{ $totalMargin < 0 ? '#b91c1c' : '#166534' }}; font-weight: 700;">{{ $format($totalMargin) }}</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left; background: #eff6ff; color: #1e3a8a; font-size: 16px;">Grand Total</th>
                    <td style="padding: 10px; text-align: right; background: #eff6ff; color: #1e3a8a; font-size: 24px; font-weight: 800;">{{ $format($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

