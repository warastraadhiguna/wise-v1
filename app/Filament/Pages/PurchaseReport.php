<?php

namespace App\Filament\Pages;

use App\Domain\Reports\BuildPurchasesReport;
use App\Models\Purchase;
use App\Models\Supplier;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class PurchaseReport extends Page
{
    protected string $view = 'filament.pages.purchase-report';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Pembelian';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationParentItem = 'Transaksi';

    protected static ?string $slug = 'reports/purchases';

    protected static ?int $navigationSort = 31;

    public string $dateFrom;

    public string $dateTo;

    public string | int | null $supplierId = null;

    public string $detailMode = 'summary';

    /** @var array<int, array{value: string, label: string}> */
    public array $supplierOptions = [];

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
        $this->supplierOptions = Purchase::query()
            ->with('supplier:id,name,company_name')
            ->where('status', 'posted')
            ->whereNotNull('supplier_id')
            ->get()
            ->pluck('supplier')
            ->filter()
            ->unique('id')
            ->sortBy(fn (Supplier $supplier) => $supplier->company_name ?: $supplier->name)
            ->map(fn (Supplier $supplier): array => [
                'value' => (string) $supplier->id,
                'label' => $supplier->company_name ?: $supplier->name,
            ])
            ->values()
            ->all();

        $this->generateReport();
    }

    public function generateReport(): void
    {
        $report = app(BuildPurchasesReport::class)->handle(
            $this->dateFrom,
            $this->dateTo,
            filled($this->supplierId) ? (int) $this->supplierId : null,
        );

        $this->company = $report['company'];
        $this->periodLabel = $report['period_label'];
        $this->rows = $report['rows'];
        $this->grandTotal = (float) $report['grand_total'];
    }

    public function getPrintUrl(): string
    {
        return route('reports.purchases.print', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'supplier_id' => filled($this->supplierId) ? (int) $this->supplierId : null,
            'detail' => $this->detailMode === 'detail' ? 1 : 0,
        ]);
    }
}
