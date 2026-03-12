@php
    $format = static fn ($value): string => number_format((float) $value, 0, ',', '.');
    $returns = collect($returns ?? []);
    $showDelete = (bool) ($showDelete ?? false);
@endphp

@if ($returns->isNotEmpty())
    <div style="border: 1px solid #fed7aa; border-radius: 10px; overflow: hidden; background: #fff;">
        <div style="padding: 10px 12px; background: #fff7ed; font-weight: 700; color: #9a3412;">
            Riwayat Retur
        </div>
        @foreach ($returns as $return)
            <div style="padding: 10px 12px; border-top: 1px solid #ffedd5;">
                <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 6px;">
                    <div style="font-weight: 700;">
                        {{ $return->number ?: ('Retur #' . $return->id) }}
                    </div>
                    <div style="font-weight: 700;">
                        Rp {{ $format($return->total_amount) }}
                    </div>
                </div>
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 6px;">
                    {{ $return->return_date?->format('d/m/Y') ?? '-' }} | {{ $return->user?->name ?? '-' }}
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Alasan:</strong> {{ $return->reason }}
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background: #fffaf5;">
                            <th style="padding: 6px 8px; text-align: left; border: 1px solid #ffedd5;">Barang</th>
                            <th style="padding: 6px 8px; text-align: right; border: 1px solid #ffedd5; width: 90px;">Qty</th>
                            <th style="padding: 6px 8px; text-align: right; border: 1px solid #ffedd5; width: 130px;">Subtotal</th>
                            @if ($showDelete)
                                <th style="padding: 6px 8px; text-align: center; border: 1px solid #ffedd5; width: 90px;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($return->details as $detail)
                            <tr>
                                <td style="padding: 6px 8px; border: 1px solid #ffedd5;">
                                    {{ trim(($detail->product?->code ? $detail->product->code . ' - ' : '') . ($detail->product?->name ?? 'Produk')) }}
                                </td>
                                <td style="padding: 6px 8px; text-align: right; border: 1px solid #ffedd5;">
                                    {{ number_format((float) $detail->qty, 4, ',', '.') }}
                                </td>
                                <td style="padding: 6px 8px; text-align: right; border: 1px solid #ffedd5;">
                                    {{ $format($detail->subtotal) }}
                                </td>
                                @if ($showDelete)
                                    <td style="padding: 6px 8px; text-align: center; border: 1px solid #ffedd5;">
                                        <form method="POST" action="{{ route('purchases.return-details.destroy', ['purchase' => $purchase->id, 'returnDetail' => $detail->id]) }}" onsubmit="return confirm('Hapus detail retur ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border: 1px solid #ef4444; background: #fff; color: #b91c1c; padding: 4px 8px; border-radius: 6px; cursor: pointer; font-size: 11px;">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
