<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'payment_method_id',
        'number',
        'purchase_date',
        'due_date',
        'discount_percent',
        'discount_amount',
        'ppn',
        'pph',
        'payment_amount',
        'reference_number',
        'note',
        'status',
        'posted_at'
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'due_date' => 'date',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'ppn' => 'decimal:2',
            'pph' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->details();
    }
}
