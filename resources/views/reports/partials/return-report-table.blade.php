@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 0, ',', '.');
    $formatQty = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
    $partnerLabel = $type === 'sale' ? 'Pembeli' : 'Supplier';
    $totalLabel = $type === 'sale' ? 'Total Jual' : 'Total Beli';
@endphp

<div class="report-sheet">
    <div class="report-header">
        <div class="report-title">LAPORAN RETUR {{ $typeLabel }}</div>
        <div class="report-company">{{ $company['name'] }}</div>
        <div class="report-period">{{ $periodLabel }}</div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 42px;">No</th>
                <th style="width: 140px;">No Nota</th>
                <th style="width: 170px;">Tanggal Transaksi</th>
                <th style="width: 100px;">Cara Bayar</th>
                <th style="width: 150px;">{{ $partnerLabel }}</th>
                <th style="width: 110px;">Kasir</th>
                <th class="text-right" style="width: 110px;">{{ $totalLabel }}</th>
                <th class="text-right" style="width: 90px;">Diskon</th>
                <th class="text-right" style="width: 90px;">Potongan</th>
                <th class="text-right" style="width: 120px;">Total Nota</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td>{{ $row['number'] }}</td>
                    <td>{{ $row['transaction_at'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ $row['partner'] }}</td>
                    <td>{{ $row['cashier'] }}</td>
                    <td class="text-right">{{ $formatMoney($row['total_value']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['diskon']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['potongan']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['total_nota']) }}</td>
                </tr>
                <tr class="detail-head">
                    <td></td>
                    <td>Tanggal Retur</td>
                    <td>Kode Barang</td>
                    <td colspan="2">Nama Barang</td>
                    <td>Alasan</td>
                    <td class="text-right">Harga</td>
                    <td class="text-right">QTY</td>
                    <td>Satuan</td>
                    <td class="text-right">Sub Total</td>
                </tr>
                @forelse ($row['details'] as $detail)
                    <tr>
                        <td></td>
                        <td>{{ $detail['return_date'] }}</td>
                        <td>{{ $detail['code'] }}</td>
                        <td colspan="2">{{ $detail['name'] }}</td>
                        <td>{{ $detail['reason'] }}</td>
                        <td class="text-right">{{ $formatMoney($detail['price']) }}</td>
                        <td class="text-right">{{ $formatQty($detail['qty']) }}</td>
                        <td>{{ $detail['unit'] }}</td>
                        <td class="text-right">{{ $formatMoney($detail['subtotal']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td></td>
                        <td colspan="9" class="muted">Tidak ada detail item retur.</td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="10" class="muted text-center">Tidak ada data retur pada periode ini.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="8" style="border: none;"></td>
                <td class="grand-label">Grand Total</td>
                <td class="grand-value">Rp {{ $formatMoney($grandTotal) }}</td>
            </tr>
        </tbody>
    </table>
</div>
