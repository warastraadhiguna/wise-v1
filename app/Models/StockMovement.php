<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by', // alias lama, akan diarahkan ke user_id
        'product_id',
        'qty_change',
        'ref_type',
        'ref_id',
        'balance_after',
        'happened_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_change' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'happened_at' => 'datetime',
        ];
    }

    // Kompatibilitas untuk kode lama yang masih kirim key "created_by".
    public function setCreatedByAttribute($value): void
    {
        $this->attributes['user_id'] = $value;
    }

    public function getCreatedByAttribute(): ?int
    {
        return isset($this->attributes['user_id'])
            ? (int) $this->attributes['user_id']
            : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
