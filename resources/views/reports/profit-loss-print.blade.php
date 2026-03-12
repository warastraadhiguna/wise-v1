<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            margin: 0;
            background: #eef2f7;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .page {
            max-width: 980px;
            margin: 18px auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            padding: 18px 22px 24px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 14px;
        }

        .btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #111827;
            padding: 8px 14px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .btn.primary {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }

        .profit-sheet {
            width: 100%;
            box-sizing: border-box;
            padding: 1rem 1.5rem 2rem;
        }

        .profit-header {
            text-align: center;
            color: #111827;
            margin-bottom: 2rem;
        }

        .profit-title {
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .profit-company {
            font-size: 1.35rem;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .profit-period {
            font-size: 0.95rem;
            margin-top: 0.35rem;
        }

        .profit-content {
            max-width: 460px;
        }

        .profit-section {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .profit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        .profit-table td {
            padding: 0.35rem 0;
            vertical-align: top;
        }

        .profit-table td:first-child {
            width: 58%;
        }

        .profit-table td:nth-child(2) {
            width: 8%;
            text-align: center;
        }

        .profit-table td:last-child {
            width: 34%;
            text-align: right;
        }

        .profit-divider td {
            padding-top: 0.5rem;
            border-top: 1px solid #111827;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                border: none;
                padding: 0;
                max-width: none;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <button class="btn" type="button" onclick="window.close()">Tutup</button>
            <button class="btn primary" type="button" onclick="window.print()">Cetak</button>
        </div>

        @include('reports.partials.profit-loss-report-content', [
            'company' => $company,
            'periodLabel' => $periodLabel,
            'totalSales' => $totalSales,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
        ])
    </div>
</body>
</html>
