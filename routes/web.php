<?php

use App\Domain\Pos\Actions\RecalculatePurchasePaymentSummary;
use App\Domain\Pos\Actions\RecalculateSalePaymentSummary;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
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

Route::middleware('auth')->get('/sales/{sale}/print/{type?}', function (int $sale, string $type = 'nota') {
    abort_unless(in_array($type, ['nota', 'struk'], true), 404);

    $sale = Sale::withTrashed()
        ->with([
            'customer:id,name,company_name,address',
            'paymentMethod:id,name,is_cash',
            'details.product:id,code,name',
            'details.product.unit:id,name',
            'user:id,name',
        ])
        ->findOrFail($sale);

    abort_unless($sale->status === 'posted', 404);

    return view($type === 'struk' ? 'filament.sales.print-sale-struk' : 'filament.sales.print-sale', [
        'sale' => $sale,
        'company' => Company::query()->first(),
        'printType' => $type,
    ]);
})->name('sales.print');

Route::middleware('auth')->delete('/sales/{sale}/payments/{payment}', function (Sale $sale, int $payment) {
    $paymentRecord = $sale->payments()
        ->whereKey($payment)
        ->firstOrFail();

    $paymentRecord->delete();

    app(RecalculateSalePaymentSummary::class)->handle((int) $sale->id);

    return back();
})->name('sales.payments.destroy');
