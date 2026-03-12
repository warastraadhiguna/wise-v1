@php
    $formatQty = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="report-sheet" style="width: 100%; min-width: 980px; background: #fff; border: 1px solid #cbd5e1; padding: 2rem 2.25rem; box-sizing: border-box;">
    <div class="report-header" style="text-align: center; margin-bottom: 1.5rem; color: #111827;">
        <div class="report-title" style="font-size: 1.75rem; font-weight: 800; letter-spacing: 0.02em;">LAPORAN DETAIL PRODUK</div>
        <div class="report-company" style="font-size: 1.375rem; font-weight: 700; margin-top: 0.15rem;">{{ $company['name'] }}</div>
        <div class="report-period" style="font-size: 0.95rem; margin-top: 0.3rem;">{{ $periodLabel }}</div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 180px)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Masuk</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['masuk']) }}</div>
        </div>
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Keluar</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['keluar']) }}</div>
        </div>
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Ret Beli</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['ret_beli']) }}</div>
        </div>
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Ret Jual</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['ret_jual']) }}</div>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; color: #111827;">
        <thead>
            <tr>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 120px;">Tanggal</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 140px;">Kode Barang</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc;">Nama Barang</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 80px; text-align: right;">Masuk</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 80px; text-align: right;">Keluar</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 80px; text-align: right;">Ret Beli</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 80px; text-align: right;">Ret Jual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['tanggal'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['code'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['name'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['masuk']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['keluar']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['ret_beli']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['ret_jual']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="border: 1px solid #111827; padding: 0.75rem; text-align: center; color: #64748b;">
                        Tidak ada data detail produk pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
