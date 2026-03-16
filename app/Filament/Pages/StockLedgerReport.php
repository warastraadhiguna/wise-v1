<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithProductLookup;
use App\Domain\Reports\BuildStockLedgerReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class StockLedgerReport extends Page
{
    use InteractsWithProductLookup;

    protected string $view = 'filament.pages.stock-ledger-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'History Stok';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/stock-ledger';

    protected static ?int $navigationSort = 34;

    public string $dateFrom;

    public string $dateTo;

    public string | int | null $productId = null;

    public string $refType = 'all';

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array{qty_in: float, qty_out: float} */
    public array $summary = [
        'qty_in' => 0.0,
        'qty_out' => 0.0,
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->syncSelectedProductLabel();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildStockLedgerReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            filled($this->productId) ? (int) $this->productId : null,
            $this->refType,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->rows = $report['rows'];
        $this->summary = $report['summary'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.stock-ledger.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'product_id' => filled($this->productId) ? (int) $this->productId : null,
            'ref_type' => $this->refType,
        ]);
    }
}
