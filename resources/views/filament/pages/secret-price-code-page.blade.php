<x-filament-panels::page>
    <style>
        .secret-price-page {
            display: grid;
            gap: 1.25rem;
        }

        .secret-card {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            padding: 1rem;
        }

        .secret-card h3 {
            margin: 0 0 0.9rem;
            font-size: 1rem;
            color: #0f172a;
        }

        .secret-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .secret-subgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .secret-field label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .secret-field input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #fff;
            min-height: 42px;
            font-size: 0.95rem;
            text-transform: uppercase;
        }

        .secret-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .secret-note {
            font-size: 0.85rem;
            color: #475569;
            margin-top: 0.75rem;
        }

        .secret-calculator {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .secret-output {
            display: flex;
            align-items: center;
            min-height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #f8fafc;
            font-weight: 700;
            color: #0f172a;
        }

        @media (max-width: 900px) {
            .secret-grid,
            .secret-calculator {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="secret-price-page">
        <div class="secret-card">
            <h3>Mapping Kode Rahasia</h3>

            <div class="secret-grid">
                <div>
                    <div class="secret-subgrid">
                        @foreach (range(0, 9) as $digit)
                            <div class="secret-field">
                                <label for="digit_{{ $digit }}">Angka {{ $digit }}</label>
                                <input
                                    id="digit_{{ $digit }}"
                                    type="text"
                                    maxlength="20"
                                    wire:model.live="digits.{{ $digit }}"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="secret-subgrid">
                        @foreach (range(2, 8) as $repeat)
                            <div class="secret-field">
                                <label for="repeat_{{ $repeat }}">X{{ $repeat }}</label>
                                <input
                                    id="repeat_{{ $repeat }}"
                                    type="text"
                                    maxlength="20"
                                    wire:model.live="repeats.{{ $repeat }}"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @error('digits')
                <div class="secret-note" style="color: #dc2626;">{{ $message }}</div>
            @enderror

            <div class="secret-actions">
                <x-filament::button wire:click="save">
                    Simpan Mapping
                </x-filament::button>
            </div>

            <div class="secret-note">
                Semua token digit dan pengali harus unik. Kalau tidak unik, kalkulator decode akan ambigu.
            </div>
        </div>

        <div class="secret-card">
            <h3>Kalkulator Kode Rahasia</h3>

            <div class="secret-calculator">
                <div class="secret-field">
                    <label for="calculator_actual">Harga Asli</label>
                    <input
                        id="calculator_actual"
                        type="text"
                        wire:model.defer="calculatorActual"
                        placeholder="Contoh: 125000"
                    >
                    <div class="secret-actions">
                        <x-filament::button size="sm" wire:click="calculateFromActual">
                            Hitung ke Secret
                        </x-filament::button>
                    </div>
                </div>

                <div class="secret-field">
                    <label for="calculator_secret">Secret Price</label>
                    <input
                        id="calculator_secret"
                        type="text"
                        wire:model.defer="calculatorSecret"
                        placeholder="Contoh: DKOKX"
                    >
                    @error('calculatorSecret')
                        <div class="secret-note" style="color: #dc2626;">{{ $message }}</div>
                    @enderror
                    <div class="secret-actions">
                        <x-filament::button size="sm" color="gray" wire:click="calculateFromSecret">
                            Hitung ke Harga
                        </x-filament::button>
                    </div>
                </div>

                <div class="secret-field">
                    <label>Hasil Angka</label>
                    <div class="secret-output">
                        {{ filled($calculatorDecoded) ? number_format((float) str_replace('.', '', $calculatorDecoded), 0, ',', '.') : '-' }}
                    </div>
                    <div class="secret-note">
                        Nilai murni hasil decode/encode saat ini.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
