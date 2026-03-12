<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Product;

trait InteractsWithProductLookup
{
    public string $productSearch = '';

    /** @var array<int, array{value: int, label: string}> */
    public array $productSearchResults = [];

    public function updatedProductSearch(string $value): void
    {
        $term = trim($value);

        if ($term === '') {
            $this->productId = null;
            $this->productSearchResults = [];

            return;
        }

        if (mb_strlen($term) < 2) {
            $this->productSearchResults = [];

            return;
        }

        $this->productSearchResults = Product::query()
            ->where(function ($query) use ($term): void {
                $query
                    ->where('code', 'like', $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%')
                    ->orWhere('name', 'like', '%' . $term . '%');
            })
            ->orderByRaw(
                "CASE
                    WHEN code LIKE ? THEN 0
                    WHEN name LIKE ? THEN 1
                    ELSE 2
                END",
                [$term . '%', $term . '%']
            )
            ->orderBy('code')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'code', 'name'])
            ->map(fn (Product $product): array => [
                'value' => (int) $product->id,
                'label' => trim(($product->code ? $product->code . ' - ' : '') . $product->name),
            ])
            ->all();
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::query()->find($productId);

        if (! $product) {
            return;
        }

        $this->productId = (string) $product->id;
        $this->productSearch = trim(($product->code ? $product->code . ' - ' : '') . $product->name);
        $this->productSearchResults = [];
    }

    public function clearProductSelection(): void
    {
        $this->productId = null;
        $this->productSearch = '';
        $this->productSearchResults = [];
    }

    protected function syncSelectedProductLabel(): void
    {
        if (! filled($this->productId)) {
            $this->productSearch = '';
            $this->productSearchResults = [];

            return;
        }

        $product = Product::query()->find((int) $this->productId);

        $this->productSearch = $product
            ? trim(($product->code ? $product->code . ' - ' : '') . $product->name)
            : '';

        $this->productSearchResults = [];
    }
}
