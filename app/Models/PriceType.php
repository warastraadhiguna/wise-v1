<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'index',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'index' => 'integer',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
