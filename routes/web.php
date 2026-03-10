<?php

use App\Domain\Pos\Actions\RecalculatePurchasePaymentSummary;
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

Route::middleware('auth')->delete('/purchases/{purchase}/payments/{payment}', function (Purchase $purchase, int $payment) {
    $paymentRecord = $purchase->payments()
        ->whereKey($payment)
        ->firstOrFail();

    $paymentRecord->delete();

    app(RecalculatePurchasePaymentSummary::class)->handle((int) $purchase->id);

    return back();
})->name('purchases.payments.destroy');
