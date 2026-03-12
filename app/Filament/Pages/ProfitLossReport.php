<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildProfitLossReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProfitLossReport extends Page
{
    protected string $view = 'filament.pages.profit-loss-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/profit-loss';

    protected static ?int $navigationSort = 32;

    public string $dateFrom;

    public string $dateTo;

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    public float $totalSales = 0.0;

    public float $totalCogs = 0.0;

    public float $grossProfit = 0.0;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildProfitLossReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->totalSales = (float) $report['total_sales'];
        $this->totalCogs = (float) $report['total_cogs'];
        $this->grossProfit = (float) $report['gross_profit'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.profit-loss.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }
}
