<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'bank_account',
        'minimum_stock_display',
        'expiration_month_limit',
        'payable_due_month_limit',
        'footer_text_1',
        'footer_text_2',
        'margin_limit',
        'ppn',
        'pph',
    ];
}
