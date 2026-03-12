<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithProductLookup;
use App\Domain\Reports\BuildProductSalesChartReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProductSalesChartReport extends Page
{
    use InteractsWithProductLookup;

    protected string $view = 'filament.pages.product-sales-chart-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Grafik Penjualan Produk';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/product-sales-chart';

    protected static ?int $navigationSort = 37;

    public string $dateFrom;

    public string $dateTo;

    public string | int | null $productId = null;

    public string $granularity = 'month';

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    /** @var array{id: int|null, code: string, name: string} */
    public array $product = [
        'id' => null,
        'code' => '',
        'name' => '',
    ];

    public string $periodLabel = '';

    public string $chartTitle = '';

    /** @var array<int, array{label: string, qty: float}> */
    public array $rows = [];

    public float $totalQty = 0.0;

    public float $maxQty = 1.0;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->syncSelectedProductLabel();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildProductSalesChartReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            filled($this->productId) ? (int) $this->productId : null,
            $this->granularity,
        );

        $this->company = $report['company'];
        $this->product = $report['product'];
        $this->periodLabel = $report['period_label'];
        $this->chartTitle = $report['chart_title'];
        $this->rows = $report['rows'];
        $this->totalQty = (float) $report['total_qty'];
        $this->maxQty = (float) $report['max_qty'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.product-sales-chart.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'product_id' => filled($this->productId) ? (int) $this->productId : null,
            'group_by' => $this->granularity,
        ]);
    }
}
