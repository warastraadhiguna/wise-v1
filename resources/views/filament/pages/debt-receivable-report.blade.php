<x-filament-panels::page>
    <style>
        .debt-report-page {
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
            grid-template-columns: minmax(0, 180px) minmax(0, 180px) minmax(0, 180px) minmax(0, 180px) minmax(0, 140px) auto;
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

        .report-sheet {
            width: 100%;
            min-width: 1240px;
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            border: 1px solid #dbe3ee;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: #fff;
        }

        .summary-label {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .summary-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
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

        .report-table thead th {
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

        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }

        @media (max-width: 1024px) {
            .report-filter-grid,
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .report-filter-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="debt-report-page">
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
                        <option value="purchase">Purchase / Hutang</option>
                        <option value="sale">Sale / Piutang</option>
                    </select>
                </div>
                <div class="report-field">
                    <label for="status">Status</label>
                    <select id="status" wire:model="status">
                        <option value="all">Tampilkan Semua</option>
                        <option value="open">Belum Selesai</option>
                        <option value="closed">Sudah Selesai</option>
                    </select>
                </div>
                <div class="report-field">
                    <label for="detail_mode">Detail</label>
                    <select id="detail_mode" wire:model="detailMode">
                        <option value="summary">Ringkas</option>
                        <option value="detail">Detail</option>
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
            @include('reports.partials.debt-receivable-report-table', [
                'company' => $company,
                'periodLabel' => $periodLabel,
                'typeLabel' => $typeLabel,
                'partnerLabel' => $partnerLabel,
                'rows' => $rows,
                'summary' => $summary,
                'showDetail' => $detailMode === 'detail',
            ])
        </div>
    </div>
</x-filament-panels::page>
