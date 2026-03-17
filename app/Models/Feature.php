<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'resource',
        'resource_class',
        'navigation_group',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(FeatureRule::class)->orderBy('role');
    }
}
