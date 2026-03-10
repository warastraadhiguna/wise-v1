<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'payment_method_id',
        'number',
        'sale_date',
        'due_date',
        'discount_percent',
        'discount_amount',
        'ppn',
        'pph',
        'grand_total',
        'paid_total',
        'balance_due',
        'payment_status',
        'payment_amount',
        'reference_number',
        'note',
        'status',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'due_date' => 'date',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'ppn' => 'decimal:2',
            'pph' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function saleDetails(): HasMany
    {
        return $this->details();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
}
