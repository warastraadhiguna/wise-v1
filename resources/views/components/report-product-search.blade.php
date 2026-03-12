@props([
    'inputId' => 'product_id',
    'label' => 'Produk',
    'searchModel' => 'productSearch',
    'resultsModel' => 'productSearchResults',
    'placeholder' => 'Cari kode / nama barang',
    'emptyLabel' => 'Semua Produk',
])

@php
    $searchValue = data_get($this, $searchModel, '');
    $results = data_get($this, $resultsModel, []);
    $hasSelection = filled(data_get($this, 'productId'));
    $showDropdown = filled(trim((string) $searchValue)) && ! $hasSelection;
@endphp

<div class="report-field">
    <label for="{{ $inputId }}_search">{{ $label }}</label>

    <div class="report-product-search">
        <div class="report-product-search__control">
            <input
                id="{{ $inputId }}_search"
                type="text"
                wire:model.live.debounce.300ms="{{ $searchModel }}"
                placeholder="{{ $placeholder }}"
                autocomplete="off"
            >

            @if (filled(trim((string) $searchValue)))
                <button
                    type="button"
                    class="report-product-search__clear"
                    wire:click="clearProductSelection"
                    aria-label="Bersihkan produk"
                >
                    &times;
                </button>
            @endif
        </div>

        @if ($showDropdown)
            <div class="report-product-search__dropdown">
                <button
                    type="button"
                    class="report-product-search__option report-product-search__option--empty"
                    wire:click="clearProductSelection"
                >
                    {{ $emptyLabel }}
                </button>

                @if (mb_strlen(trim((string) $searchValue)) < 2)
                    <div class="report-product-search__state">Ketik minimal 2 karakter.</div>
                @elseif (count($results) === 0)
                    <div class="report-product-search__state">Produk tidak ditemukan.</div>
                @else
                    @foreach ($results as $item)
                        <button
                            type="button"
                            class="report-product-search__option"
                            wire:click="selectProduct({{ (int) $item['value'] }})"
                        >
                            {{ $item['label'] }}
                        </button>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
</div>

@once
    <style>
        .report-product-search {
            position: relative;
        }

        .report-product-search__control {
            position: relative;
        }

        .report-product-search__control input {
            padding-right: 2.5rem;
        }

        .report-product-search__clear {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 1.2rem;
            line-height: 1;
            cursor: pointer;
        }

        .report-product-search__dropdown {
            position: absolute;
            top: calc(100% + 0.35rem);
            left: 0;
            right: 0;
            z-index: 40;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 0.9rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            max-height: 280px;
            overflow-y: auto;
            padding: 0.35rem;
        }

        .report-product-search__option {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 0.65rem 0.75rem;
            border-radius: 0.7rem;
            color: #0f172a;
            cursor: pointer;
        }

        .report-product-search__option:hover {
            background: #eff6ff;
            color: #0f5fb7;
        }

        .report-product-search__option--empty {
            font-weight: 600;
        }

        .report-product-search__state {
            padding: 0.75rem;
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
@endonce
