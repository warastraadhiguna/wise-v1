<?php

namespace App\Domain\Reports;

use App\Models\Company;
use App\Models\Sale;
use Carbon\Carbon;

class BuildProfitLossReport
{
    /**
     * @return array{
     *     company: array{name: string, address: string, city: string},
     *     period_label: string,
     *     total_sales: float,
     *     total_cogs: float,
     *     gross_profit: float
     * }
     */
    public function handle(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $sales = Sale::query()
            ->with('details:id,sale_id,fifo_cost_amount,margin_amount')
            ->where('status', 'posted')
            ->whereDate('sale_date', '>=', $from->toDateString())
            ->whereDate('sale_date', '<=', $to->toDateString())
            ->get();

        $totalSales = round((float) $sales->sum('grand_total'), 2);
        $totalCogs = round((float) $sales->flatMap->details->sum('fifo_cost_amount'), 2);
        $grossProfit = round($totalSales - $totalCogs, 2);

        $company = Company::query()->first();

        return [
            'company' => [
                'name' => trim((string) ($company?->name ?? config('app.name', 'Company'))),
                'address' => trim((string) ($company?->address ?? '')),
                'city' => trim((string) ($company?->city ?? '')),
            ],
            'period_label' => $from->format('d-m-Y') . ' s/d ' . $to->format('d-m-Y'),
            'total_sales' => $totalSales,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
        ];
    }
}
