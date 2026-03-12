<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan {{ $typeLabel }}</title>
    <style>
        body {
            margin: 0;
            background: #eef2f7;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .page {
            max-width: 1320px;
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            border: 1px solid #dbe3ee;
            border-radius: 0.9rem;
            padding: 0.9rem 1rem;
            background: #fff;
        }

        .summary-label {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .summary-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
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

        .report-table thead th {
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

        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }

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

        @include('reports.partials.debt-receivable-report-table', [
            'company' => $company,
            'periodLabel' => $periodLabel,
            'typeLabel' => $typeLabel,
            'partnerLabel' => $partnerLabel,
            'rows' => $rows,
            'summary' => $summary,
            'showDetail' => $showDetail,
        ])
    </div>
</body>
</html>
