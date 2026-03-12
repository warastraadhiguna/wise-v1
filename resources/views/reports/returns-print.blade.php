<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Retur {{ $typeLabel }}</title>
    <style>
        body {
            margin: 0;
            background: #eef2f7;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .page {
            max-width: 1240px;
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

        .report-sheet {
            width: 100%;
            box-sizing: border-box;
        }

        .report-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .report-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .report-company {
            font-size: 1.375rem;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .report-period {
            font-size: 0.95rem;
            margin-top: 0.3rem;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #111827;
            padding: 0.42rem 0.5rem;
            vertical-align: top;
        }

        .report-table thead th,
        .detail-head td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #64748b;
        }

        .grand-label,
        .grand-value {
            font-weight: 700;
            background: #f8fafc;
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

        @include('reports.partials.return-report-table', [
            'company' => $company,
            'periodLabel' => $periodLabel,
            'type' => $type,
            'typeLabel' => $typeLabel,
            'rows' => $rows,
            'grandTotal' => $grandTotal,
        ])
    </div>
</body>
</html>
