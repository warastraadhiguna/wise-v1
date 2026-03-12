<x-filament-panels::page>
    <style>
        .profit-loss-page {
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
            grid-template-columns: minmax(0, 180px) minmax(0, 180px) auto;
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

        .report-filter input {
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

        .report-actions .fi-btn,
        .report-actions a {
            min-height: 42px;
        }

        .report-preview {
            background: #e8edf3;
            border-radius: 1.25rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        .profit-sheet {
            width: 100%;
            min-width: 860px;
            background: #fff;
            border: 1px solid #cbd5e1;
            padding: 2.5rem 3rem;
            box-sizing: border-box;
        }

        .profit-header {
            text-align: center;
            color: #111827;
            margin-bottom: 2rem;
        }

        .profit-title {
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .profit-company {
            font-size: 1.35rem;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .profit-period {
            font-size: 0.95rem;
            margin-top: 0.35rem;
        }

        .profit-content {
            max-width: 460px;
        }

        .profit-section {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .profit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        .profit-table td {
            padding: 0.35rem 0;
            vertical-align: top;
        }

        .profit-table td:first-child {
            width: 58%;
        }

        .profit-table td:nth-child(2) {
            width: 8%;
            text-align: center;
        }

        .profit-table td:last-child {
            width: 34%;
            text-align: right;
        }

        .profit-divider td {
            padding-top: 0.5rem;
            border-top: 1px solid #111827;
        }

        @media (max-width: 768px) {
            .report-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="profit-loss-page">
        <div class="report-filter no-print">
            <div class="report-filter-grid">
                <div>
                    <label for="date_from">Waktu Dari</label>
                    <input id="date_from" type="date" wire:model="dateFrom">
                </div>
                <div>
                    <label for="date_to">Sampai</label>
                    <input id="date_to" type="date" wire:model="dateTo">
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
            @include('reports.partials.profit-loss-report-content', [
                'company' => $company,
                'periodLabel' => $periodLabel,
                'totalSales' => $totalSales,
                'totalCogs' => $totalCogs,
                'grossProfit' => $grossProfit,
            ])
        </div>
    </div>
</x-filament-panels::page>
