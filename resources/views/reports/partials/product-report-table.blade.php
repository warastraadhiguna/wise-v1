@php
    $formatQty = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="report-sheet" style="width: 100%; min-width: 840px; background: #fff; border: 1px solid #cbd5e1; padding: 2rem 2.25rem; box-sizing: border-box;">
    <div class="report-header" style="text-align: center; margin-bottom: 1.5rem; color: #111827;">
        <div class="report-title" style="font-size: 1.75rem; font-weight: 800; letter-spacing: 0.02em;">
            LAPORAN {{ $transactionLabel }} TOP {{ $topX }} BARANG {{ $rankLabel }}
        </div>
        <div class="report-company" style="font-size: 1.375rem; font-weight: 700; margin-top: 0.15rem;">{{ $company['name'] }}</div>
        <div class="report-period" style="font-size: 0.95rem; margin-top: 0.3rem;">{{ $periodLabel }}</div>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; color: #111827;">
        <thead>
            <tr>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 42px;">No</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 160px;">No Barang</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc;">Nama Barang</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 100px;">Satuan</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 120px; text-align: right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: center;">{{ $row['no'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['code'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['name'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['unit'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right; font-weight: 700;">{{ $formatQty($row['qty']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="border: 1px solid #111827; padding: 0.75rem; text-align: center; color: #64748b;">
                        Tidak ada data produk pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
