<x-filament-panels::page>
    <style>
        .product-report-page {
            display: grid;
            gap: 1.25rem;
        }

        .report-filter {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            padding: 1rem;
        }

        .report-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 180px) minmax(0, 180px) minmax(0, 140px) minmax(0, 180px) minmax(0, 180px) auto;
            gap: 0.75rem;
            align-items: end;
        }

        .report-filter label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .report-filter input,
        .report-filter select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #fff;
            min-height: 42px;
            font-size: 0.95rem;
        }

        .report-field.compact {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .report-actions {
            display: flex;
            gap: 0.625rem;
            align-items: center;
            min-height: 42px;
        }

        .report-preview {
            background: #e8edf3;
            border-radius: 1.25rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        @media (max-width: 1024px) {
            .report-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .report-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="product-report-page">
        <div class="report-filter no-print">
            <div class="report-filter-grid">
                <div class="report-field">
                    <label for="date_from">Waktu Dari</label>
                    <input id="date_from" type="date" wire:model="dateFrom">
                </div>
                <div class="report-field">
                    <label for="date_to">Sampai</label>
                    <input id="date_to" type="date" wire:model="dateTo">
                </div>
                <div class="report-field">
                    <label for="top_x">Top X</label>
                    <input id="top_x" type="number" min="1" max="100" wire:model="topX">
                </div>
                <div class="report-field">
                    <label for="transaction_type">Transaksi</label>
                    <select id="transaction_type" wire:model="transactionType">
                        <option value="sale">PENJUALAN</option>
                        <option value="purchase">PEMBELIAN</option>
                    </select>
                </div>
                <div class="report-field">
                    <label for="rank_type">Jenis</label>
                    <select id="rank_type" wire:model="rankType">
                        <option value="best">TERBAIK</option>
                        <option value="worst">TERBURUK</option>
                    </select>
                </div>
                <div class="report-field compact">
                    <label>Aksi</label>
                    <div class="report-actions">
                        <x-filament::button wire:click="generateReport">
                            Tampilkan
                        </x-filament::button>
                        <x-filament::button tag="a" color="gray" href="{{ $this->getPrintUrl() }}" target="_blank">
                            Cetak
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-preview">
            @include('reports.partials.product-report-table', [
                'company' => $company,
                'periodLabel' => $periodLabel,
                'transactionLabel' => $transactionLabel,
                'rankLabel' => $rankLabel,
                'topX' => $topX,
                'rows' => $rows,
            ])
        </div>
    </div>
</x-filament-panels::page>
