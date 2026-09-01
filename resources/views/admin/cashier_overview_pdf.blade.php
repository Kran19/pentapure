<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cashier Overview Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #f8c300;
            padding-bottom: 15px;
        }
        .header .brand-title { font-size: 24px; font-weight: bold; color: #101828; margin: 0; }
        .header .tagline { font-size: 14px; font-weight: bold; color: #101828; margin-top: 5px; }
        .header .report-title { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 14px; font-weight: bold; color: #344054; text-transform: uppercase; }
        .header p {
            margin: 0;
            color: #666;
        }
        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-box td {
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .summary-box .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-box .value {
            font-size: 18px;
            font-weight: bold;
        }
        .value.income { color: #22c55e; }
        .value.expense { color: #ef4444; }
        .value.balance { color: #344054; }
        
        .cashier-breakdown {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .cashier-breakdown th, .cashier-breakdown td {
            border: 1px solid #eee;
            padding: 8px;
            text-align: left;
        }
        .cashier-breakdown th {
            background-color: #f8c300;
            color: #101828;
            font-weight: bold;
        }

        .tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .tx-table th, .tx-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .tx-table th {
            background-color: #f8c300;
            color: #101828;
        }
        .tx-table tr:nth-child(even) { background-color: #f9fafb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 10px;">
            <tr>
                <td style="width: 20%; text-align: left; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: middle; border: none; background: transparent;">
                    <div class="brand-title">PENTAPURE</div>
                    <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="report-title">Cashier Overview Statement</div>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <table class="summary-box">
        <tr>
            <td>
                <div class="label">Total Income</div>
                <div class="value income">₹{{ number_format($pageData['summary']['totalIn'], 2) }}</div>
            </td>
            <td>
                <div class="label">Total Expenses</div>
                <div class="value expense">₹{{ number_format($pageData['summary']['totalOut'], 2) }}</div>
            </td>
            <td>
                <div class="label">Net Balance</div>
                <div class="value balance">₹{{ number_format($pageData['summary']['balance'], 2) }}</div>
            </td>
        </tr>
    </table>

    <h3 style="color:#344054; border-bottom:1px solid #ddd; padding-bottom:5px;">Cashier Breakdown</h3>
    <table class="cashier-breakdown">
        <thead>
            <tr>
                <th>Cashier Name</th>
                <th class="text-right">Income</th>
                <th class="text-right">Expense</th>
                <th class="text-right">Net Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pageData['summary']['byCashier'] as $c)
            <tr>
                <td><strong>{{ strtoupper($c['name']) }}</strong></td>
                <td class="text-right" style="color:#22c55e;">₹{{ number_format($c['in'], 2) }}</td>
                <td class="text-right" style="color:#ef4444;">₹{{ number_format($c['out'], 2) }}</td>
                <td class="text-right" style="font-weight:bold; color:{{ $c['balance'] >= 0 ? '#344054' : '#ef4444' }};">
                    ₹{{ number_format($c['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="color:#344054; border-bottom:1px solid #ddd; padding-bottom:5px;">Transaction Ledger</h3>
    <table class="tx-table">
        <thead>
            <tr>
                <th width="15%">Date</th>
                <th width="15%">Cashier</th>
                <th width="10%">Category</th>
                <th width="35%">Particulars / Note</th>
                <th width="10%">Ref / Bill</th>
                <th width="15%" class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pageData['transactions'] as $t)
            <tr>
                <td>{{ $t->created_at->format('d M Y') }}<br><span style="color:#666;font-size:10px;">{{ $t->created_at->format('h:i A') }}</span></td>
                <td><strong>{{ $t->user->name ?? 'Unknown' }}</strong></td>
                <td>{{ strtoupper($t->category) }}</td>
                <td>
                    {{ $t->note ?? '—' }}
                    @if($t->description)
                        <br><span style="font-size:10px; color:#666;">{{ $t->description }}</span>
                    @endif
                </td>
                <td>{{ $t->reference ?? '—' }}</td>
                <td class="text-right" style="font-weight:bold; color: {{ $t->type === 'IN' ? '#22c55e' : '#ef4444' }}">
                    {{ $t->type === 'IN' ? '+' : '-' }}{{ number_format($t->amount, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        This is a system-generated statement. No signature required.<br>
        PentaPure Production Management System
    </div>

</body>
</html>
