@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 0, ',', '.');
@endphp

<div class="profit-sheet">
    <div class="profit-header">
        <div class="profit-title">LAPORAN LABA RUGI DAN KEUANGAN</div>
        <div class="profit-company">{{ $company['name'] }}</div>
        <div class="profit-period">{{ $periodLabel }}</div>
    </div>

    <div class="profit-content">
        <div class="profit-section">Laba Rugi</div>

        <table class="profit-table">
            <tbody>
                <tr>
                    <td>Total Penjualan</td>
                    <td>:</td>
                    <td>Rp {{ $formatMoney($totalSales) }}</td>
                </tr>
                <tr>
                    <td>Modal terjual</td>
                    <td>:</td>
                    <td>Rp {{ $formatMoney($totalCogs) }}</td>
                </tr>
                <tr class="profit-divider">
                    <td>Laba di tangan</td>
                    <td>:</td>
                    <td>Rp {{ $formatMoney($grossProfit) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
