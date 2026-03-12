<x-filament-panels::page>
    <style>
        .stock-detail-page {
            display: grid;
            gap: 1.25rem;
        }

        .stock-detail-filter {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            padding: 1rem;
        }

        .stock-detail-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 340px) minmax(0, 170px) minmax(0, 170px) minmax(0, 120px);
            gap: 0.75rem;
            align-items: end;
        }

        .stock-detail-filter label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .stock-detail-filter input,
        .stock-detail-filter select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #fff;
            min-height: 42px;
            font-size: 0.95rem;
        }

        .stock-detail-note {
            font-size: 0.85rem;
            color: #475569;
            margin-top: 0.75rem;
        }

        .stock-detail-sheet {
            background: #fff;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            overflow: hidden;
        }

        .stock-detail-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
        }

        .stock-detail-toolbar h3 {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }

        .stock-detail-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .stock-detail-badge.secret {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .stock-detail-badge.actual {
            background: #ecfdf5;
            color: #047857;
        }

        .stock-detail-table-wrap {
            overflow-x: auto;
        }

        .stock-detail-table {
            width: 100%;
            min-width: 1080px;
            border-collapse: collapse;
        }

        .stock-detail-table th,
        .stock-detail-table td {
            padding: 0.72rem 0.85rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            white-space: nowrap;
        }

        .stock-detail-table th {
            background: #f8fafc;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stock-detail-table td {
            color: #0f172a;
            font-size: 0.92rem;
        }

        .stock-detail-table td.name {
            min-width: 260px;
            white-space: normal;
        }

        .stock-detail-table td.price {
            font-family: Consolas, Monaco, monospace;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .stock-detail-table td.qty,
        .stock-detail-table td.price {
            text-align: right;
        }

        .stock-detail-empty {
            padding: 1.5rem 1rem;
            color: #64748b;
        }

        .stock-detail-pagination {
            padding: 0.9rem 1rem 1rem;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (max-width: 1024px) {
            .stock-detail-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .stock-detail-filter-grid {
                grid-template-columns: 1fr;
            }

            .stock-detail-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div
        class="stock-detail-page"
        x-data="{
            showActualPrice: JSON.parse(localStorage.getItem('stock-detail-show-actual') ?? 'false'),
            togglePrice() {
                this.showActualPrice = ! this.showActualPrice;
                localStorage.setItem('stock-detail-show-actual', JSON.stringify(this.showActualPrice));
            }
        }"
        x-on:keydown.window.ctrl.shift.z.prevent="togglePrice()"
    >
        <div class="stock-detail-filter">
            <div class="stock-detail-filter-grid">
                <div>
                    <label for="search">Cari</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kode atau nama barang"
                    >
                </div>
                <div>
                    <label for="date_from">Tanggal Dari</label>
                    <input id="date_from" type="date" wire:model.live="dateFrom">
                </div>
                <div>
                    <label for="date_to">Sampai</label>
                    <input id="date_to" type="date" wire:model.live="dateTo">
                </div>
                <div>
                    <label for="per_page">Baris</label>
                    <select id="per_page" wire:model.live="perPage">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>

            <div class="stock-detail-note">
                Harga beli tampil dalam kode rahasia. Tekan <strong>Ctrl + Shift + Z</strong> untuk ganti ke harga asli, dan tekan lagi untuk menyembunyikan kembali.
            </div>
        </div>

        <div class="stock-detail-sheet">
            <div class="stock-detail-toolbar">
                <h3>Detail Stok Aktif</h3>

                <div
                    class="stock-detail-badge secret"
                    x-show="! showActualPrice"
                >
                    Mode Harga: Rahasia
                </div>

                <div
                    class="stock-detail-badge actual"
                    x-show="showActualPrice"
                    x-cloak
                >
                    Mode Harga: Asli
                </div>
            </div>

            <div class="stock-detail-table-wrap">
                <table class="stock-detail-table">
                    <thead>
                        <tr>
                            <th>Tanggal Stok</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>No Pembelian</th>
                            <th>Qty Stok</th>
                            <th>Satuan</th>
                            <th>Harga Beli</th>
                            <th>Gudang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rows as $row)
                            <tr>
                                <td>{{ $row->purchase?->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $row->product?->code ?? '-' }}</td>
                                <td class="name">{{ $row->product?->name ?? '-' }}</td>
                                <td>{{ $row->purchase?->number ?? '-' }}</td>
                                <td class="qty">{{ number_format((float) $row->remaining_qty, 4, ',', '.') }}</td>
                                <td>{{ $row->product?->unit?->name ?? '-' }}</td>
                                <td class="price">
                                    <span x-show="! showActualPrice">
                                        {{ $this->formatSecretPrice($row->price) }}
                                    </span>
                                    <span x-show="showActualPrice" x-cloak>
                                        {{ $this->formatActualPrice($row->price) }}
                                    </span>
                                </td>
                                <td>{{ filled($row->product?->location) ? $row->product?->location : 'Utama' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="stock-detail-empty">
                                    Tidak ada stok aktif yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="stock-detail-pagination">
                {{ $this->rows->links() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
