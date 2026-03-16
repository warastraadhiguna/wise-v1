<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildProductReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProductReport extends Page
{
    protected string $view = 'filament.pages.product-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Laporan Produk';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/products';

    protected static ?int $navigationSort = 35;

    public string $dateFrom;

    public string $dateTo;

    public string $transactionType = 'sale';

    public string $rankType = 'best';

    public int $topX = 10;

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    public string $transactionLabel = 'PENJUALAN';

    public string $rankLabel = 'TERBAIK';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildProductReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            $this->transactionType,
            $this->rankType,
            $this->topX,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->transactionLabel = $report['transaction_label'];
        $this->rankLabel = $report['rank_label'];
        $this->topX = $report['limit'];
        $this->rows = $report['rows'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.products.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'transaction_type' => $this->transactionType,
            'rank_type' => $this->rankType,
            'top_x' => $this->topX,
        ]);
    }
}
