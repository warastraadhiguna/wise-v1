<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Nota {{ $sale->number }}</title>
    <style>
        body {
            margin: 0;
            background: #eef0f3;
            font-family: Arial, sans-serif;
            color: #111;
        }

        .page {
            width: 1024px;
            min-height: 720px;
            margin: 16px auto;
            background: #fff;
            border: 1px solid #c8ccd2;
            padding: 30px 34px 22px;
            box-sizing: border-box;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 18px;
        }

        .switches {
            display: flex;
            gap: 8px;
            margin-right: auto;
        }

        .btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn.primary {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .btn.active {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 28px;
            margin-bottom: 20px;
        }

        .company {
            width: 45%;
        }

        .company-name {
            font-size: 26px;
            font-weight: 700;
            text-align: center;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .company-address {
            font-size: 13px;
            text-align: center;
            line-height: 1.35;
            white-space: pre-line;
        }

        .invoice {
            width: 42%;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta td:first-child {
            width: 42%;
        }

        .meta td:nth-child(2) {
            width: 4%;
        }

        .recipient {
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.45;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 14px;
        }

        .table th,
        .table td {
            padding: 4px 6px;
            border-bottom: 1px solid #111;
            vertical-align: top;
        }

        .table thead th {
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .totals table {
            width: 320px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .totals td {
            padding: 2px 0;
        }

        .totals td:first-child {
            width: 48%;
            font-weight: 700;
            text-align: right;
            padding-right: 10px;
        }

        .totals td:nth-child(2) {
            width: 6%;
            text-align: center;
        }

        .totals td:last-child {
            width: 46%;
            text-align: right;
            font-weight: 700;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 36px;
            margin-top: 34px;
            font-size: 14px;
            text-align: center;
        }

        .signature-line {
            height: 52px;
        }

        .footer-page {
            margin-top: 34px;
            text-align: right;
            font-size: 12px;
        }

        @media print {
            body {
                margin: 0;
                background: #fff;
            }

            .page {
                margin: 0;
                border: none;
                padding: 0;
                width: auto;
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
    $customerName = $sale->customer?->company_name ?: $sale->customer?->name ?: '-';
    $customerAddress = trim((string) ($sale->customer?->address ?? ''));
@endphp
    <div class="page">
        <div class="toolbar">
            <div class="switches">
                <a class="btn active" href="{{ route('sales.print', ['sale' => $sale->id, 'type' => 'nota']) }}">Nota</a>
                <a class="btn" href="{{ route('sales.print', ['sale' => $sale->id, 'type' => 'struk']) }}">Struk</a>
            </div>
            <button class="btn" type="button" onclick="window.close()">Tutup</button>
            <button class="btn primary" type="button" onclick="window.print()">Cetak</button>
        </div>

        <div class="header">
            <div class="company">
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-address">{{ $companyAddress }}@if (filled($companyAddress) && filled($companyCity))<br>@endif{{ $companyCity }}</div>
            </div>
            <div class="invoice">
                <div class="invoice-title">FAKTUR PENJUALAN</div>
                <table class="meta">
                    <tbody>
                        <tr>
                            <td>Tanggal Faktur</td>
                            <td>:</td>
                            <td>{{ $sale->sale_date?->translatedFormat('d-M-Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>No. Faktur</td>
                            <td>:</td>
                            <td>{{ $sale->number }}</td>
                        </tr>
                        <tr>
                            <td>Cara Bayar</td>
                            <td>:</td>
                            <td>{{ $sale->paymentMethod?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Total Bayar</td>
                            <td>:</td>
                            <td>Rp {{ $format($paidTotal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recipient">
            <div>Kepada Yth.</div>
            <div>{{ $customerName }}</div>
            @if (filled($customerAddress))
                <div>{{ $customerAddress }}</div>
            @endif
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 16%;">Kode</th>
                    <th>Nama Barang</th>
                    <th class="text-right" style="width: 10%;">QTY</th>
                    <th class="text-right" style="width: 15%;">Harga</th>
                    <th class="text-right" style="width: 16%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sale->details as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->product?->code ?? '-' }}</td>
                        <td>{{ $detail->product?->name ?? '-' }}</td>
                        <td class="text-right">{{ $format($detail->qty) }}</td>
                        <td class="text-right">{{ $format($detail->price) }}</td>
                        <td class="text-right">{{ $format($lineTotal($detail)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center;">Detail sale kosong.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tbody>
                    <tr>
                        <td>Total</td>
                        <td>:</td>
                        <td>{{ $format($detailsSubtotal) }}</td>
                    </tr>
                    <tr>
                        <td>Potongan</td>
                        <td>:</td>
                        <td>{{ $format($headerDiscountAmount) }}</td>
                    </tr>
                    <tr>
                        <td>Disc</td>
                        <td>:</td>
                        <td>{{ $format($headerDiscountPercent) }} %</td>
                    </tr>
                    @if ($ppnPercent > 0)
                        <tr>
                            <td>PPN</td>
                            <td>:</td>
                            <td>{{ $format($ppnAmount) }}</td>
                        </tr>
                    @endif
                    @if ($pphPercent > 0)
                        <tr>
                            <td>PPH</td>
                            <td>:</td>
                            <td>{{ $format($pphAmount) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Total Bayar</td>
                        <td>:</td>
                        <td>{{ $format($grandTotal) }}</td>
                    </tr>
                    <tr>
                        <td>Bayar</td>
                        <td>:</td>
                        <td>{{ $format($paidTotal) }}</td>
                    </tr>
                    @if ((bool) ($sale->paymentMethod?->is_cash ?? false))
                        <tr>
                            <td>Kembali</td>
                            <td>:</td>
                            <td>{{ $format($changeAmount) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="signatures">
            <div>
                <div>Tanda Terima</div>
                <div class="signature-line"></div>
                <div>(....................)</div>
            </div>
            <div>
                <div>Diperiksa</div>
                <div class="signature-line"></div>
                <div>(....................)</div>
            </div>
            <div>
                <div>Dikemas</div>
                <div class="signature-line"></div>
                <div>(....................)</div>
            </div>
        </div>

        <div class="footer-page">1 / 1</div>
    </div>
</body>
</html>
