<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Purchase {{ $purchase->number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            background: #f3f4f6;
        }

        .container {
            max-width: 1024px;
            margin: 18px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 14px;
        }

        .btn {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            padding: 8px 14px;
            font-size: 14px;
            cursor: pointer;
        }

        .btn.primary {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }

        .title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .title h1 {
            margin: 0;
            font-size: 22px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge.posted { background: #dcfce7; color: #166534; }
        .badge.draft { background: #fef3c7; color: #92400e; }
        .badge.void { background: #fee2e2; color: #991b1b; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        th {
            text-align: left;
            background: #f9fafb;
            white-space: nowrap;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; font-size: 12px; }
        .no-border td, .no-border th { border-bottom: none; }

        .summary {
            display: flex;
            justify-content: flex-end;
        }

        .summary table {
            max-width: 460px;
        }

        .grand {
            font-weight: 800;
            font-size: 24px;
            color: #1e3a8a;
            background: #eff6ff;
        }

        .grand th {
            color: #1e3a8a;
            background: #eff6ff;
            font-size: 16px;
        }

        @media print {
            body {
                margin: 0;
                background: #fff;
            }

            .container {
                margin: 0;
                border: none;
                border-radius: 0;
                max-width: none;
                padding: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
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
    <div class="container">
        <div class="toolbar">
            <button class="btn" type="button" onclick="window.close()">Tutup</button>
            <button class="btn primary" type="button" onclick="window.print()">Cetak</button>
        </div>

        <div class="title">
            <h1>Purchase {{ $purchase->number }}</h1>
            <span class="badge {{ $purchase->status }}">{{ $purchase->status }}</span>
        </div>

        <div class="box">
            <table>
                <tbody>
                    <tr>
                        <th style="width: 18%;">No Nota</th>
                        <td style="width: 32%;">{{ $purchase->number }}</td>
                        <th style="width: 18%;">Supplier</th>
                        <td style="width: 32%;">{{ $purchase->supplier?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Pembelian</th>
                        <td>{{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                        <th>Jatuh Tempo</th>
                        <td>{{ $purchase->due_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Cara Bayar</th>
                        <td>{{ $purchase->paymentMethod?->name ?? '-' }}</td>
                        <th>Jumlah Bayar</th>
                        <td class="text-right"><strong>{{ $format($purchase->payment_amount) }}</strong></td>
                    </tr>
                    <tr class="no-border">
                        <th>Catatan</th>
                        <td colspan="3">{{ filled($purchase->note) ? $purchase->note : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="box">
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 48px;">No</th>
                        <th>Produk</th>
                        <th class="text-right" style="width: 90px;">Qty</th>
                        <th class="text-right" style="width: 130px;">Harga</th>
                        <th class="text-right" style="width: 90px;">Disc %</th>
                        <th class="text-right" style="width: 130px;">Disc Rp</th>
                        <th class="text-right" style="width: 130px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchase->details as $index => $detail)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <div><strong>{{ $detail->product?->name ?? '-' }}</strong></div>
                                <div class="muted">{{ $detail->product?->code ?? '-' }}</div>
                            </td>
                            <td class="text-right">{{ $format($detail->qty) }}</td>
                            <td class="text-right">{{ $format($detail->price) }}</td>
                            <td class="text-right">{{ $format($detail->discount_percent) }}</td>
                            <td class="text-right">{{ $format($detail->discount_amount) }}</td>
                            <td class="text-right"><strong>{{ $format($lineTotal($detail)) }}</strong></td>
                        </tr>
                    @empty
                        <tr class="no-border">
                            <td colspan="7" class="text-center">Detail purchase kosong.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchase->returns->isNotEmpty())
            <div style="margin-bottom: 14px;">
                @include('filament.purchases.partials.return-history', ['returns' => $purchase->returns->sortByDesc('return_date')->values()])
            </div>
        @endif

        <div class="summary">
            <div class="box" style="margin-bottom: 0; width: 100%; max-width: 460px;">
                <table>
                    <tbody>
                        <tr>
                            <th>Subtotal</th>
                            <td class="text-right">{{ $format($detailsSubtotal) }}</td>
                        </tr>
                        <tr>
                            <th>Disc Header (%)</th>
                            <td class="text-right">{{ $format($headerDiscountPercent) }}</td>
                        </tr>
                        <tr>
                            <th>Disc Header (Rp)</th>
                            <td class="text-right">{{ $format($headerDiscountAmount) }}</td>
                        </tr>
                        <tr>
                            <th>PPN (%)</th>
                            <td class="text-right">{{ $format($ppnPercent) }}</td>
                        </tr>
                        <tr>
                            <th>PPH (%)</th>
                            <td class="text-right">{{ $format($pphPercent) }}</td>
                        </tr>
                        <tr>
                            <th>Total Setelah Disc Header</th>
                            <td class="text-right">{{ $format($afterHeaderDiscount) }}</td>
                        </tr>
                        <tr>
                            <th>PPN (Rp)</th>
                            <td class="text-right">{{ $format($ppnAmount) }}</td>
                        </tr>
                        <tr>
                            <th>PPH (Rp)</th>
                            <td class="text-right">{{ $format($pphAmount) }}</td>
                        </tr>
                        <tr class="grand no-border">
                            <th>Grand Total</th>
                            <td class="text-right">{{ $format($grandTotal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
