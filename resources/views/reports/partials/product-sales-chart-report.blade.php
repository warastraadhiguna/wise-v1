@php
    $formatQty = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');

    $chartWidth = 1100;
    $chartHeight = 420;
    $paddingLeft = 68;
    $paddingRight = 36;
    $paddingTop = 42;
    $paddingBottom = 56;
    $plotWidth = $chartWidth - $paddingLeft - $paddingRight;
    $plotHeight = $chartHeight - $paddingTop - $paddingBottom;
    $pointsCount = max(1, count($rows));
    $stepX = $pointsCount > 1 ? $plotWidth / ($pointsCount - 1) : 0;
    $maxValue = max(1, (float) $maxQty);

    $points = collect($rows)->values()->map(function ($row, $index) use ($paddingLeft, $paddingTop, $plotHeight, $stepX, $maxValue) {
        $x = $paddingLeft + ($stepX * $index);
        $y = $paddingTop + $plotHeight - (($row['qty'] / $maxValue) * $plotHeight);

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'label' => $row['label'],
            'qty' => (float) $row['qty'],
        ];
    });

    $polylinePoints = $points->map(fn ($point) => $point['x'] . ',' . $point['y'])->implode(' ');
    $ticks = 5;
@endphp

<div class="report-sheet" style="width: 100%; min-width: 1180px; background: #fff; border: 1px solid #cbd5e1; padding: 1.5rem; box-sizing: border-box;">
    <div style="text-align: center; margin-bottom: 1rem; color: #111827;">
        <div style="font-size: 2rem; font-weight: 800; color: #0f5fb7;">{{ $chartTitle }}</div>
        <div style="font-size: 1rem; margin-top: 0.25rem;">{{ $periodLabel }}</div>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem;">
        <div style="border: 1px solid #cbd5e1; border-radius: 0.75rem; padding: 0.75rem 1rem; background: #fff;">
            <div style="font-size: 0.82rem; color: #64748b;">Total Penjualan</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: #0f172a;">{{ $formatQty($totalQty) }}</div>
        </div>
    </div>

    <svg width="100%" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="{{ $chartTitle }}">
        <rect x="0" y="0" width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="#ffffff"/>

        @for ($i = 0; $i <= $ticks; $i++)
            @php
                $value = ($maxValue / $ticks) * ($ticks - $i);
                $y = $paddingTop + (($plotHeight / $ticks) * $i);
            @endphp
            <line x1="{{ $paddingLeft }}" y1="{{ $y }}" x2="{{ $paddingLeft + $plotWidth }}" y2="{{ $y }}" stroke="#dbe3ee" stroke-width="1"/>
            <text x="{{ $paddingLeft - 10 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="12" fill="#475569">
                {{ $formatQty($value) }}
            </text>
        @endfor

        <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop }}" x2="{{ $paddingLeft }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#334155" stroke-width="1.5"/>
        <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop + $plotHeight }}" x2="{{ $paddingLeft + $plotWidth }}" y2="{{ $paddingTop + $plotHeight }}" stroke="#334155" stroke-width="1.5"/>

        @if ($points->isNotEmpty())
            <polyline fill="none" stroke="#1668c7" stroke-width="3" points="{{ $polylinePoints }}"/>

            @foreach ($points as $point)
                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="#1668c7"/>
                <text x="{{ $point['x'] }}" y="{{ $point['y'] - 12 }}" text-anchor="middle" font-size="12" fill="#0f172a" font-weight="700">
                    {{ $formatQty($point['qty']) }}
                </text>
                <text x="{{ $point['x'] }}" y="{{ $paddingTop + $plotHeight + 22 }}" text-anchor="middle" font-size="12" fill="#475569">
                    {{ $point['label'] }}
                </text>
            @endforeach
        @endif

        <text x="24" y="{{ $paddingTop + ($plotHeight / 2) }}" transform="rotate(-90 24 {{ $paddingTop + ($plotHeight / 2) }})" text-anchor="middle" font-size="16" fill="#1668c7">
            Penjualan
        </text>
    </svg>

    @if (blank($product['id'] ?? null))
        <div style="margin-top: 0.75rem; color: #64748b;">Pilih produk untuk menampilkan grafik.</div>
    @endif
</div>
