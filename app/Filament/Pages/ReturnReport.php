<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildReturnsReport;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ReturnReport extends Page
{
    protected string $view = 'filament.pages.return-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Laporan Retur';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/returns';

    protected static ?int $navigationSort = 32;

    public string $dateFrom;

    public string $dateTo;

    public string $type = 'purchase';

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    public string $typeLabel = 'PEMBELIAN';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public float $grandTotal = 0.0;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildReturnsReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            $this->type,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->typeLabel = $report['type_label'];
        $this->rows = $report['rows'];
        $this->grandTotal = (float) $report['grand_total'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.returns.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'type' => $this->type,
        ]);
    }
}
