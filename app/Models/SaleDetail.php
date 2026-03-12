<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'sale_id',
        'product_id',
        'qty',
        'remaining_qty',
        'price',
        'discount_percent',
        'discount_amount',
        'fifo_cost_amount',
        'margin_amount',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:4',
            'remaining_qty' => 'decimal:4',
            'price' => 'decimal:4',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'fifo_cost_amount' => 'decimal:4',
            'margin_amount' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fifoAllocations(): HasMany
    {
        return $this->hasMany(SaleDetailFifoAllocation::class);
    }
}
