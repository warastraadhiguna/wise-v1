<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildProfitLossDetailReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProfitLossDetailReport extends Page
{
    protected string $view = 'filament.pages.profit-loss-detail-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi Detail';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationParentItem = 'Keuangan';

    protected static ?string $slug = 'reports/profit-loss-detail';

    protected static ?int $navigationSort = 33;

    public string $dateFrom;

    public string $dateTo;

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    /** @var array{total_sales: float} */
    public array $sales = [
        'total_sales' => 0,
    ];

    /** @var array{total_recorded: float, total_cash: float, total_credit: float, credit_paid_total: float, credit_unpaid_total: float, debt_paid: float, debt_unpaid: float} */
    public array $purchases = [
        'total_recorded' => 0,
        'total_cash' => 0,
        'total_credit' => 0,
        'credit_paid_total' => 0,
        'credit_unpaid_total' => 0,
        'debt_paid' => 0,
        'debt_unpaid' => 0,
    ];

    /** @var array{total_sales: float, cogs_sold: float, profit_in_hand: float, inventory_value: float, debt_unpaid: float, total_profit: float} */
    public array $profitLoss = [
        'total_sales' => 0,
        'cogs_sold' => 0,
        'profit_in_hand' => 0,
        'inventory_value' => 0,
        'debt_unpaid' => 0,
        'total_profit' => 0,
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildProfitLossDetailReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->sales = $report['sales'];
        $this->purchases = $report['purchases'];
        $this->profitLoss = $report['profit_loss'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.profit-loss-detail.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);
    }
}
