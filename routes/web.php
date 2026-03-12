<?php

use App\Domain\Pos\Actions\DeletePurchaseReturnDetailAction;
use App\Domain\Pos\Actions\DeleteSaleReturnDetailAction;
use App\Domain\Pos\Actions\RecalculatePurchasePaymentSummary;
use App\Domain\Pos\Actions\RecalculateSalePaymentSummary;
use App\Domain\Reports\BuildDebtReceivableReport;
use App\Domain\Reports\BuildProfitLossDetailReport;
use App\Domain\Reports\BuildProfitLossReport;
use App\Domain\Reports\BuildProductDetailReport;
use App\Domain\Reports\BuildProductReport;
use App\Domain\Reports\BuildProductSalesChartReport;
use App\Domain\Reports\BuildPurchasesReport;
use App\Domain\Reports\BuildReturnsReport;
use App\Domain\Reports\BuildSalesReport;
use App\Domain\Reports\BuildStockLedgerReport;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/purchases/{purchase}/print', function (int $purchase) {
    $purchase = Purchase::withTrashed()
        ->with([
            'supplier:id,name',
            'paymentMethod:id,name',
            'details.product:id,code,name',
            'returns.user:id,name',
            'returns.details.product:id,code,name',
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

Route::middleware('auth')->delete('/purchases/{purchase}/return-details/{returnDetail}', function (Purchase $purchase, int $returnDetail) {
    try {
        app(DeletePurchaseReturnDetailAction::class)->handle((int) $purchase->id, $returnDetail);
    } catch (ValidationException $exception) {
        return back()->withErrors($exception->errors());
    }

    return back();
})->name('purchases.return-details.destroy');

Route::middleware('auth')->get('/sales/{sale}/print/{type?}', function (int $sale, string $type = 'nota') {
    abort_unless(in_array($type, ['nota', 'struk'], true), 404);

    $sale = Sale::withTrashed()
        ->with([
            'customer:id,name,company_name,address',
            'paymentMethod:id,name,is_cash',
            'details.product:id,code,name',
            'details.product.unit:id,name',
            'returns.user:id,name',
            'returns.details.product:id,code,name',
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

Route::middleware('auth')->delete('/sales/{sale}/return-details/{returnDetail}', function (Sale $sale, int $returnDetail) {
    try {
        app(DeleteSaleReturnDetailAction::class)->handle((int) $sale->id, $returnDetail);
    } catch (ValidationException $exception) {
        return back()->withErrors($exception->errors());
    }

    return back();
})->name('sales.return-details.destroy');

Route::middleware('auth')->get('/reports/sales/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $cashierId = request()->integer('cashier_id');
    $showDetail = request()->boolean('detail');

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildSalesReport::class)->handle(
        $dateFrom,
        $dateTo,
        $cashierId ?: null,
    );

    return view('reports.sales-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'rows' => $report['rows'],
        'grandTotal' => $report['grand_total'],
        'showDetail' => $showDetail,
    ]);
})->name('reports.sales.print');

Route::middleware('auth')->get('/reports/purchases/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $supplierId = request()->integer('supplier_id');
    $showDetail = request()->boolean('detail');

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildPurchasesReport::class)->handle(
        $dateFrom,
        $dateTo,
        $supplierId ?: null,
    );

    return view('reports.purchases-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'rows' => $report['rows'],
        'grandTotal' => $report['grand_total'],
        'showDetail' => $showDetail,
    ]);
})->name('reports.purchases.print');

Route::middleware('auth')->get('/reports/returns/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $type = request()->string('type')->toString();

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildReturnsReport::class)->handle(
        $dateFrom,
        $dateTo,
        $type,
    );

    return view('reports.returns-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'type' => $report['type'],
        'typeLabel' => $report['type_label'],
        'rows' => $report['rows'],
        'grandTotal' => $report['grand_total'],
    ]);
})->name('reports.returns.print');

Route::middleware('auth')->get('/reports/debt-receivable/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $type = request()->string('type')->toString();
    $status = request()->string('status')->toString();
    $showDetail = request()->boolean('detail');

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildDebtReceivableReport::class)->handle(
        $dateFrom,
        $dateTo,
        $type,
        $status,
    );

    return view('reports.debt-receivable-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'typeLabel' => $report['type_label'],
        'partnerLabel' => $report['partner_label'],
        'rows' => $report['rows'],
        'summary' => $report['summary'],
        'showDetail' => $showDetail,
    ]);
})->name('reports.debt-receivable.print');

Route::middleware('auth')->get('/reports/stock-ledger/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $productId = request()->integer('product_id');
    $refType = request()->string('ref_type')->toString();

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildStockLedgerReport::class)->handle(
        $dateFrom,
        $dateTo,
        $productId ?: null,
        $refType,
    );

    return view('reports.stock-ledger-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'rows' => $report['rows'],
        'summary' => $report['summary'],
    ]);
})->name('reports.stock-ledger.print');

Route::middleware('auth')->get('/reports/products/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $transactionType = request()->string('transaction_type')->toString();
    $rankType = request()->string('rank_type')->toString();
    $topX = request()->integer('top_x');

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildProductReport::class)->handle(
        $dateFrom,
        $dateTo,
        $transactionType,
        $rankType,
        $topX > 0 ? $topX : 10,
    );

    return view('reports.product-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'transactionLabel' => $report['transaction_label'],
        'rankLabel' => $report['rank_label'],
        'topX' => $report['limit'],
        'rows' => $report['rows'],
    ]);
})->name('reports.products.print');

Route::middleware('auth')->get('/reports/product-detail/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $productId = request()->integer('product_id');

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildProductDetailReport::class)->handle(
        $dateFrom,
        $dateTo,
        $productId ?: null,
    );

    return view('reports.product-detail-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'rows' => $report['rows'],
        'summary' => $report['summary'],
    ]);
})->name('reports.product-detail.print');

Route::middleware('auth')->get('/reports/product-sales-chart/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();
    $productId = request()->integer('product_id');
    $groupBy = request()->string('group_by')->toString();

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildProductSalesChartReport::class)->handle(
        $dateFrom,
        $dateTo,
        $productId ?: null,
        $groupBy,
    );

    return view('reports.product-sales-chart-print', [
        'company' => $report['company'],
        'product' => $report['product'],
        'periodLabel' => $report['period_label'],
        'chartTitle' => $report['chart_title'],
        'rows' => $report['rows'],
        'totalQty' => $report['total_qty'],
        'maxQty' => $report['max_qty'],
    ]);
})->name('reports.product-sales-chart.print');

Route::middleware('auth')->get('/reports/profit-loss/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildProfitLossReport::class)->handle(
        $dateFrom,
        $dateTo,
    );

    return view('reports.profit-loss-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'totalSales' => $report['total_sales'],
        'totalCogs' => $report['total_cogs'],
        'grossProfit' => $report['gross_profit'],
    ]);
})->name('reports.profit-loss.print');

Route::middleware('auth')->get('/reports/profit-loss-detail/print', function () {
    $dateFrom = request()->string('date_from')->toString();
    $dateTo = request()->string('date_to')->toString();

    abort_unless(filled($dateFrom) && filled($dateTo), 404);

    $report = app(BuildProfitLossDetailReport::class)->handle(
        $dateFrom,
        $dateTo,
    );

    return view('reports.profit-loss-detail-print', [
        'company' => $report['company'],
        'periodLabel' => $report['period_label'],
        'sales' => $report['sales'],
        'purchases' => $report['purchases'],
        'profitLoss' => $report['profit_loss'],
    ]);
})->name('reports.profit-loss-detail.print');
