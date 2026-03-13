<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithProductLookup;
use App\Domain\Reports\BuildProductDetailReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProductDetailReport extends Page
{
    use InteractsWithProductLookup;

    protected string $view = 'filament.pages.product-detail-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Laporan Detail Produk';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationParentItem = 'Stok';

    protected static ?string $slug = 'reports/product-detail';

    protected static ?int $navigationSort = 36;

    public string $dateFrom;

    public string $dateTo;

    public string | int | null $productId = null;

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array{masuk: float, keluar: float, ret_beli: float, ret_jual: float} */
    public array $summary = [
        'masuk' => 0.0,
        'keluar' => 0.0,
        'ret_beli' => 0.0,
        'ret_jual' => 0.0,
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
        $report = app(BuildProductDetailReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            filled($this->productId) ? (int) $this->productId : null,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->rows = $report['rows'];
        $this->summary = $report['summary'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.product-detail.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'product_id' => filled($this->productId) ? (int) $this->productId : null,
        ]);
    }
}
