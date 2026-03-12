@php
    $formatQty = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="report-sheet">
    <div class="report-header" style="text-align: center; margin-bottom: 1.5rem; color: #111827;">
        <div class="report-title" style="font-size: 1.75rem; font-weight: 800; letter-spacing: 0.02em;">LAPORAN HISTORY STOK</div>
        <div class="report-company" style="font-size: 1.375rem; font-weight: 700; margin-top: 0.15rem;">{{ $company['name'] }}</div>
        <div class="report-period" style="font-size: 0.95rem; margin-top: 0.3rem;">{{ $periodLabel }}</div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 220px)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Total Masuk</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['qty_in']) }}</div>
        </div>
        <div style="border: 1px solid #dbe3ee; border-radius: 0.9rem; padding: 0.9rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.35rem;">Total Keluar</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">{{ $formatQty($summary['qty_out']) }}</div>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; color: #111827;">
        <thead>
            <tr>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 42px;">No</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 170px;">Waktu</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 120px;">Kode</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc;">Nama Barang</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 180px;">Jenis Mutasi</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 140px;">No Ref</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 100px;">User</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 90px; text-align: right;">Masuk</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 90px; text-align: right;">Keluar</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 110px; text-align: right;">Saldo</th>
                <th style="border: 1px solid #111827; padding: 0.42rem 0.5rem; background: #f8fafc; width: 80px;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: center;">{{ $row['no'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['happened_at'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['product_code'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['product_name'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['ref_type_label'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['reference_number'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['user'] }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['qty_in']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right;">{{ $formatQty($row['qty_out']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem; text-align: right; font-weight: 700;">{{ $formatQty($row['balance_after']) }}</td>
                    <td style="border: 1px solid #111827; padding: 0.42rem 0.5rem;">{{ $row['unit'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="border: 1px solid #111827; padding: 0.75rem; text-align: center; color: #64748b;">Tidak ada mutasi stok pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
