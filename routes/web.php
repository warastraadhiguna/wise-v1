<?php

use App\Models\Purchase;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/purchases/{purchase}/print', function (int $purchase) {
    $purchase = Purchase::withTrashed()
        ->with([
            'supplier:id,name',
            'paymentMethod:id,name',
            'details.product:id,code,name',
        ])
        ->findOrFail($purchase);

    return view('filament.purchases.print-purchase', [
        'purchase' => $purchase,
    ]);
})->name('purchases.print');
