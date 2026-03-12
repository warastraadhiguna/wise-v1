<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnDetailAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_detail_id',
        'sale_detail_fifo_allocation_id',
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

    public function saleReturnDetail(): BelongsTo
    {
        return $this->belongsTo(SaleReturnDetail::class);
    }

    public function saleDetailFifoAllocation(): BelongsTo
    {
        return $this->belongsTo(SaleDetailFifoAllocation::class);
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class);
    }
}
