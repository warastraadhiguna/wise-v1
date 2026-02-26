<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'price_type_id',
        'name',
        'company_name',
        'address',
        'email',
        'phone',
        'bank_account',
        'point',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class);
    }
}
