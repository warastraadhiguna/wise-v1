<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Struk {{ $sale->number }}</title>
    <style>
        body {
            margin: 0;
            background: #ececec;
            font-family: "Courier New", monospace;
            color: #111;
        }

        .page {
            width: 320px;
            margin: 16px auto;
            background: #fff;
            border: 1px solid #d4d4d4;
            padding: 14px 16px 18px;
            box-sizing: border-box;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 8px 10px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
        }

        .btn.primary {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }

        .btn.active {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }

        .switches {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .header {
            margin-bottom: 10px;
        }

        .company {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
        }

        .muted {
            font-size: 11px;
            line-height: 1.4;
        }

        .divider {
            border-top: 1px solid #111;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        td, th {
            padding: 2px 0;
            vertical-align: top;
        }

        .summary td:first-child {
            width: 58%;
            font-weight: 700;
        }

        .summary td:nth-child(2) {
            width: 8%;
            text-align: center;
        }

        .summary td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .items th {
            border-bottom: 1px solid #111;
            padding-bottom: 4px;
        }

        .items td {
            padding-top: 4px;
        }

        .text-right {
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                width: auto;
                margin: 0;
                border: none;
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
    $paidTotal = (float) ($sale->paid_total ?? 0);
    $changeAmount = (bool) ($sale->paymentMethod?->is_cash ?? false)
        ? max(0, $paidTotal - $grandTotal)
        : 0;
    $format = static fn ($value): string => number_format((float) $value, 0, ',', '.');
    $companyName = trim((string) ($company?->name ?? config('app.name', 'Company')));
    $companyAddress = trim((string) ($company?->address ?? ''));
    $companyCity = trim((string) ($company?->city ?? ''));
    $customerLabel = $sale->customer?->company_name ?: $sale->customer?->name ?: '-';
@endphp
    <div class="page">
        <div class="toolbar">
            <div class="switches">
                <a class="btn" href="{{ route('sales.print', ['sale' => $sale->id, 'type' => 'nota']) }}">Nota</a>
                <a class="btn active" href="{{ route('sales.print', ['sale' => $sale->id, 'type' => 'struk']) }}">Struk</a>
            </div>
            <button class="btn" type="button" onclick="window.close()">Tutup</button>
            <button class="btn primary" type="button" onclick="window.print()">Cetak</button>
        </div>

        <div class="header center">
            <div class="company">{{ $companyName }}</div>
            @if (filled($companyAddress))
                <div class="muted">{{ $companyAddress }}</div>
            @endif
            @if (filled($companyCity))
                <div class="muted">{{ $companyCity }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <table>
            <tbody>
                <tr>
                    <td>No Nota</td>
                    <td>:</td>
                    <td>{{ $sale->number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>:</td>
                    <td>{{ $sale->posted_at?->format('d-m-Y H.i.s') ?? $sale->sale_date?->format('d-m-Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Pelanggan</td>
                    <td>:</td>
                    <td>{{ $customerLabel }}</td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>:</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="items">
            <thead>
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th class="text-right" style="width: 28%;">Harga</th>
                    <th class="text-right" style="width: 16%;">QTY</th>
                    <th class="text-right" style="width: 28%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->details as $detail)
                    <tr>
                        <td>{{ $detail->product?->code ?? '-' }} {{ $detail->product?->name ?? '-' }}</td>
                        <td class="text-right">{{ $format($detail->price) }}</td>
                        <td class="text-right">{{ $format($detail->qty) }}</td>
                        <td class="text-right">{{ $format($lineTotal($detail)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="summary">
            <tbody>
                <tr>
                    <td>SubTotal</td>
                    <td>:</td>
                    <td>Rp {{ $format($detailsSubtotal) }}</td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td>:</td>
                    <td>{{ $format($headerDiscountPercent) }} %</td>
                </tr>
                <tr>
                    <td>Potongan</td>
                    <td>:</td>
                    <td>Rp {{ $format($headerDiscountAmount) }}</td>
                </tr>
                <tr>
                    <td>Grand Total</td>
                    <td>:</td>
                    <td>Rp {{ $format($grandTotal) }}</td>
                </tr>
                <tr>
                    <td>Bayar</td>
                    <td>:</td>
                    <td>Rp {{ $format($paidTotal) }}</td>
                </tr>
                @if ((bool) ($sale->paymentMethod?->is_cash ?? false))
                    <tr>
                        <td>Kembali</td>
                        <td>:</td>
                        <td>Rp {{ $format($changeAmount) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
