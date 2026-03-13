<?php

namespace App\Filament\Pages;

use App\Models\PurchaseDetail;
use App\Support\SecretPriceCode;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use UnitEnum;

class StockDetailPage extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.stock-detail-page';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Detail Stok';

    protected static string | UnitEnum | null $navigationGroup = 'Data Umum';

    protected static ?string $slug = 'reports/stock-detail';

    protected static ?int $navigationSort = 35;

    public string $search = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 100;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        return PurchaseDetail::query()
            ->with([
                'purchase:id,number,purchase_date,status',
                'product:id,code,name,location,unit_id',
                'product.unit:id,name',
            ])
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_details.product_id')
            ->whereNull('purchase_details.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->whereNull('products.deleted_at')
            ->where('purchases.status', 'posted')
            ->where('purchase_details.remaining_qty', '>', 0)
            ->when(filled($this->search), function (Builder $query): void {
                $term = trim($this->search);

                $query->where(function (Builder $nested) use ($term): void {
                    $nested
                        ->where('products.code', 'like', '%' . $term . '%')
                        ->orWhere('products.name', 'like', '%' . $term . '%');
                });
            })
            ->when(filled($this->dateFrom), fn (Builder $query): Builder => $query->whereDate('purchases.purchase_date', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn (Builder $query): Builder => $query->whereDate('purchases.purchase_date', '<=', $this->dateTo))
            ->orderBy('purchases.purchase_date')
            ->orderBy('products.name')
            ->orderBy('products.code')
            ->orderBy('purchase_details.id')
            ->select('purchase_details.*')
            ->paginate($this->perPage);
    }

    public function formatActualPrice(int|float|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public function formatSecretPrice(int|float|string|null $value): string
    {
        return SecretPriceCode::encode($value);
    }
}
