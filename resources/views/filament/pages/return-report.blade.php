<x-filament-panels::page>
    <style>
        .return-report-page {
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
            grid-template-columns: minmax(0, 180px) minmax(0, 180px) minmax(0, 180px) auto;
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

        .report-actions {
            display: flex;
            gap: 0.625rem;
            align-items: center;
            min-height: 42px;
        }

        .report-field.compact {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .report-preview {
            background: #e8edf3;
            border-radius: 1.25rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        .report-sheet {
            width: 100%;
            min-width: 1180px;
            background: #fff;
            border: 1px solid #cbd5e1;
            padding: 2rem 2.25rem;
            box-sizing: border-box;
        }

        .report-header {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #111827;
        }

        .report-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .report-company {
            font-size: 1.375rem;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .report-period {
            font-size: 0.95rem;
            margin-top: 0.3rem;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            color: #111827;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #111827;
            padding: 0.42rem 0.5rem;
            vertical-align: top;
        }

        .report-table thead th,
        .detail-head td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #64748b;
        }

        .grand-label,
        .grand-value {
            font-weight: 700;
            background: #f8fafc;
        }

        @media (max-width: 1024px) {
            .report-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .report-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="return-report-page">
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
                    <label for="type">Jenis</label>
                    <select id="type" wire:model="type">
                        <option value="purchase">PEMBELIAN</option>
                        <option value="sale">PENJUALAN</option>
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
            @include('reports.partials.return-report-table', [
                'company' => $company,
                'periodLabel' => $periodLabel,
                'type' => $type,
                'typeLabel' => $typeLabel,
                'rows' => $rows,
                'grandTotal' => $grandTotal,
            ])
        </div>
    </div>
</x-filament-panels::page>
