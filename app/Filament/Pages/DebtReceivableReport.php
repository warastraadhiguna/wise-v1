<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildDebtReceivableReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class DebtReceivableReport extends Page
{
    protected string $view = 'filament.pages.debt-receivable-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Laporan Hutang Piutang';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationParentItem = 'Keuangan';

    protected static ?string $slug = 'reports/debt-receivable';

    protected static ?int $navigationSort = 33;

    public string $dateFrom;

    public string $dateTo;

    public string $type = 'purchase';

    public string $status = 'all';

    public string $detailMode = 'detail';

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    public string $typeLabel = 'HUTANG PEMBELIAN';

    public string $partnerLabel = 'Supplier';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array{total: float, returned: float, paid: float, balance: float} */
    public array $summary = [
        'total' => 0.0,
        'returned' => 0.0,
        'paid' => 0.0,
        'balance' => 0.0,
    ];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildDebtReceivableReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            $this->type,
            $this->status,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->typeLabel = $report['type_label'];
        $this->partnerLabel = $report['partner_label'];
        $this->rows = $report['rows'];
        $this->summary = $report['summary'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.debt-receivable.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'type' => $this->type,
            'status' => $this->status,
            'detail' => $this->detailMode === 'detail' ? 1 : 0,
        ]);
    }
}
