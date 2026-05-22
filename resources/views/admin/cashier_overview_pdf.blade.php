<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cashier Overview Statement</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a2744;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1a2744;
            margin: 0 0 5px 0;
            font-size: 24px;
            text-transform: uppercase;
        }
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
        .value.balance { color: #1a2744; }
        
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
            background-color: #f8fafc;
            color: #1a2744;
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
            background-color: #1a2744;
            color: white;
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
        <h1>Cashier Overview Statement</h1>
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

    <h3 style="color:#1a2744; border-bottom:1px solid #ddd; padding-bottom:5px;">Cashier Breakdown</h3>
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
                <td class="text-right" style="font-weight:bold; color:{{ $c['balance'] >= 0 ? '#1a2744' : '#ef4444' }};">
                    ₹{{ number_format($c['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="color:#1a2744; border-bottom:1px solid #ddd; padding-bottom:5px;">Transaction Ledger</h3>
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
