<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetailFifoAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_detail_id',
        'purchase_detail_id',
        'qty',
        'unit_cost',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class);
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class);
    }
}
