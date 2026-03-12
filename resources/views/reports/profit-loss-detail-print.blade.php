<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Laba Rugi Detail</title>
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

        @include('reports.partials.profit-loss-detail-report-content', [
            'company' => $company,
            'periodLabel' => $periodLabel,
            'sales' => $sales,
            'purchases' => $purchases,
            'profitLoss' => $profitLoss,
        ])
    </div>
</body>
</html>
