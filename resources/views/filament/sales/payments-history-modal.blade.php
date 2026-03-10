@php
    $payments = $sale->payments
        ->sortByDesc('paid_at')
        ->values();

    $format = static fn ($value): string => number_format((float) $value, 0, ',', '.');
@endphp

<div style="margin-bottom: 14px; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
    <div style="padding: 10px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-weight: 700;">
        Riwayat Pembayaran
    </div>

    @if ($payments->isEmpty())
        <div style="padding: 12px; color: #6b7280;">
            Belum ada pembayaran.
        </div>
    @else
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Tanggal</th>
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Cara Bayar</th>
                    <th style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">Jumlah</th>
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">User</th>
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Ref</th>
                    <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Catatan</th>
                    <th style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #e5e7eb; width: 88px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            {{ $payment->paid_at?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            {{ $payment->paymentMethod?->name ?? '-' }}
                        </td>
                        <td style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 700;">
                            {{ $format($payment->amount) }}
                        </td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            {{ $payment->user?->name ?? '-' }}
                        </td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            {{ $payment->reference_number ?: '-' }}
                        </td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6;">
                            {{ $payment->note ?: '-' }}
                        </td>
                        <td style="padding: 8px 10px; border-bottom: 1px solid #f3f4f6; text-align: center;">
                            <button
                                type="button"
                                style="border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 6px; padding: 4px 8px; font-size: 12px; font-weight: 600; cursor: pointer;"
                                onclick="
                                    if (! confirm('Hapus pembayaran ini?')) return;
                                    const url = '{{ route('sales.payments.destroy', ['sale' => $sale->id, 'payment' => $payment->id]) }}';
                                    fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        body: '_method=DELETE',
                                    }).then((response) => {
                                        if (! response.ok) throw new Error('Gagal hapus pembayaran.');
                                        window.location.reload();
                                    }).catch(() => {
                                        alert('Gagal menghapus pembayaran.');
                                    });
                                "
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="display: flex; gap: 16px; justify-content: flex-end; padding: 10px 12px; border-top: 1px solid #e5e7eb; background: #fcfcfd;">
        <div style="font-size: 13px;">
            <strong>Total Bayar:</strong> {{ $format($sale->paid_total) }}
        </div>
        <div style="font-size: 13px;">
            <strong>Sisa:</strong> {{ $format($sale->balance_due) }}
        </div>
    </div>
</div>

