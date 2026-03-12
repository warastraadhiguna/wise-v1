<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'purchase_id',
        'product_id',
        'qty',
        'remaining_qty',
        'price',
        'discount_percent',
        'discount_amount',
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (PurchaseDetail $purchaseDetail): void {
    //         if ($purchaseDetail->remaining_qty === null) {
    //             $purchaseDetail->remaining_qty = $purchaseDetail->qty;
    //         }
    //     });
    // }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fifoAllocations(): HasMany
    {
        return $this->hasMany(SaleDetailFifoAllocation::class);
    }

    public function returnDetails(): HasMany
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }
}
