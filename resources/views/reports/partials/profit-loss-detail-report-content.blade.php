@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 0, ',', '.');
@endphp

<div class="profit-sheet" style="width: 100%; min-width: 860px; background: #fff; border: 1px solid #cbd5e1; padding: 2.5rem 3rem; box-sizing: border-box;">
    <div class="profit-header" style="text-align: center; color: #111827; margin-bottom: 2rem;">
        <div class="profit-title" style="font-size: 1.7rem; font-weight: 800; letter-spacing: 0.02em;">LAPORAN LABA RUGI DAN KEUANGAN</div>
        <div class="profit-company" style="font-size: 1.35rem; font-weight: 700; margin-top: 0.15rem;">{{ $company['name'] }}</div>
        <div class="profit-period" style="font-size: 0.95rem; margin-top: 0.35rem;">{{ $periodLabel }}</div>
    </div>

    <div style="max-width: 560px; color: #111827;">
        <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.75rem;">PENJUALAN</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 1rem;">
            <tbody>
                <tr>
                    <td style="width: 58%; padding: 0.25rem 0;">Total Penjualan</td>
                    <td style="width: 8%; text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="width: 10%; padding: 0.25rem 0;">Rp.</td>
                    <td style="width: 24%; text-align: right; padding: 0.25rem 0;">{{ $formatMoney($sales['total_sales']) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.75rem;">PEMBELIAN</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 1rem;">
            <tbody>
                <tr>
                    <td style="width: 58%; padding: 0.25rem 0;">Total Pembelian Tercatat</td>
                    <td style="width: 8%; text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="width: 10%; padding: 0.25rem 0;">Rp.</td>
                    <td style="width: 24%; text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['total_recorded']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 1.25rem;">Total Pembelian Tunai</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['total_cash']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 1.25rem;">Total Pembelian Hutang</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['total_credit']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 2.5rem;">Total Hutang Pembelian Lunas</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['credit_paid_total']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 2.5rem;">Total Hutang Pembelian Belum Lunas</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['credit_unpaid_total']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 3.75rem;">Hutang Sudah Terbayar</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['debt_paid']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0 0.25rem 3.75rem;">Hutang Belum Terbayar</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($purchases['debt_unpaid']) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.75rem;">Laba Rugi</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 1rem;">
            <tbody>
                <tr>
                    <td style="width: 58%; padding: 0.25rem 0;">Total Penjualan</td>
                    <td style="width: 8%; text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="width: 10%; padding: 0.25rem 0;">Rp.</td>
                    <td style="width: 24%; text-align: right; padding: 0.25rem 0;">{{ $formatMoney($profitLoss['total_sales']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0;">Modal terjual</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($profitLoss['cogs_sold']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.35rem 0; border-top: 1px solid #111827;">Laba di tangan</td>
                    <td style="text-align: center; padding: 0.35rem 0; border-top: 1px solid #111827;">:</td>
                    <td style="padding: 0.35rem 0; border-top: 1px solid #111827;">Rp.</td>
                    <td style="text-align: right; padding: 0.35rem 0; border-top: 1px solid #111827;">{{ $formatMoney($profitLoss['profit_in_hand']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0;">Modal belum terjual</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($profitLoss['inventory_value']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.25rem 0;">Hutang belum terbayar</td>
                    <td style="text-align: center; padding: 0.25rem 0;">:</td>
                    <td style="padding: 0.25rem 0;">Rp.</td>
                    <td style="text-align: right; padding: 0.25rem 0;">{{ $formatMoney($profitLoss['debt_unpaid']) }}</td>
                </tr>
                <tr>
                    <td style="padding: 0.35rem 0; border-top: 1px solid #111827; font-weight: 700;">Total Laba</td>
                    <td style="text-align: center; padding: 0.35rem 0; border-top: 1px solid #111827;">:</td>
                    <td style="padding: 0.35rem 0; border-top: 1px solid #111827;">Rp.</td>
                    <td style="text-align: right; padding: 0.35rem 0; border-top: 1px solid #111827; font-weight: 700;">{{ $formatMoney($profitLoss['total_profit']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
