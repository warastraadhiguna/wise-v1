@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 0, ',', '.');
    $statusLabel = static fn (string $status): string => match ($status) {
        'paid' => 'Selesai',
        'partial' => 'Sebagian',
        default => 'Belum',
    };
@endphp

<div class="report-sheet">
    <div class="report-header">
        <div class="report-title">LAPORAN {{ $typeLabel }}</div>
        <div class="report-company">{{ $company['name'] }}</div>
        <div class="report-period">{{ $periodLabel }}</div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Efektif</div>
            <div class="summary-value">Rp {{ $formatMoney($summary['total']) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Retur</div>
            <div class="summary-value">Rp {{ $formatMoney($summary['returned']) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Sudah Dibayar</div>
            <div class="summary-value">Rp {{ $formatMoney($summary['paid']) }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Sisa</div>
            <div class="summary-value">Rp {{ $formatMoney($summary['balance']) }}</div>
        </div>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 42px;">No</th>
                <th style="width: 140px;">No Nota</th>
                <th style="width: 170px;">Tanggal Transaksi</th>
                <th style="width: 110px;">Jatuh Tempo</th>
                <th style="width: 100px;">Cara Bayar</th>
                <th style="width: 150px;">{{ $partnerLabel }}</th>
                <th style="width: 110px;">Kasir</th>
                <th class="text-right" style="width: 110px;">Total Nota</th>
                <th class="text-right" style="width: 110px;">Retur</th>
                <th class="text-right" style="width: 110px;">Total Efektif</th>
                <th class="text-right" style="width: 110px;">Dibayar</th>
                <th class="text-right" style="width: 110px;">Sisa</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td>{{ $row['number'] }}</td>
                    <td>{{ $row['transaction_at'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ $row['partner'] }}</td>
                    <td>{{ $row['cashier'] }}</td>
                    <td class="text-right">{{ $formatMoney($row['grand_total']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['return_total']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['effective_total']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['paid_total']) }}</td>
                    <td class="text-right">{{ $formatMoney($row['balance_due']) }}</td>
                    <td>
                        <span class="status-badge status-{{ $row['status'] }}">
                            {{ $statusLabel($row['status']) }}
                        </span>
                    </td>
                </tr>
                @if ($showDetail ?? false)
                    <tr>
                        <td></td>
                        <td colspan="12" style="padding: 0;">
                            <table class="report-table" style="margin: 0; border: none;">
                                <tbody>
                                    <tr>
                                        <td class="text-center" style="width: 140px; background: #f8fafc; font-weight: 700;">Tanggal Bayar</td>
                                        <td style="width: 140px; background: #f8fafc; font-weight: 700;">Cara Bayar</td>
                                        <td style="width: 140px; background: #f8fafc; font-weight: 700;">Kasir</td>
                                        <td style="width: 170px; background: #f8fafc; font-weight: 700;">No Ref</td>
                                        <td style="background: #f8fafc; font-weight: 700;">Catatan</td>
                                        <td class="text-right" style="width: 140px; background: #f8fafc; font-weight: 700;">Jumlah Bayar</td>
                                    </tr>
                                    @forelse ($row['payments'] as $payment)
                                        <tr>
                                            <td class="text-center">{{ $payment['paid_at'] }}</td>
                                            <td>{{ $payment['payment_method'] }}</td>
                                            <td>{{ $payment['cashier'] }}</td>
                                            <td>{{ $payment['reference_number'] }}</td>
                                            <td>{{ $payment['note'] }}</td>
                                            <td class="text-right">{{ $formatMoney($payment['amount']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="muted text-center">Belum ada riwayat pembayaran.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="13" class="muted text-center">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
