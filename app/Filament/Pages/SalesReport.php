<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildSalesReport;
use App\Models\Sale;
use App\Models\User;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.sales-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $slug = 'reports/sales';

    protected static ?int $navigationSort = 30;

    public string $dateFrom;

    public string $dateTo;

    public string | int | null $cashierId = null;

    public string $detailMode = 'summary';

    /** @var array<int, array{value: string, label: string}> */
    public array $cashierOptions = [];

    /** @var array{name: string, address: string, city: string} */
    public array $company = [
        'name' => '',
        'address' => '',
        'city' => '',
    ];

    public string $periodLabel = '';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public float $grandTotal = 0.0;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->cashierOptions = Sale::query()
            ->with('user:id,name')
            ->where('status', 'posted')
            ->whereNotNull('user_id')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(fn (User $user): array => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ])
            ->values()
            ->all();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildSalesReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            filled($this->cashierId) ? (int) $this->cashierId : null,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->rows = $report['rows'];
        $this->grandTotal = (float) $report['grand_total'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.sales.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'cashier_id' => filled($this->cashierId) ? (int) $this->cashierId : null,
            'detail' => $this->detailMode === 'detail' ? 1 : 0,
        ]);
    }
}
