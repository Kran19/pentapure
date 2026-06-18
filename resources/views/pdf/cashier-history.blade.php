<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PentaPure - Transaction History Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            line-height: 1.5;
            text-transform: uppercase;
        }

        /* ── HEADER BAR ── */
        .header-bar {
            background: #1a2744;
            padding: 20px 30px;
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 40%;
            color: #ffffff;
            font-size: 10px;
        }
        .header-right div { margin-bottom: 4px; }
        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
        }
        .brand-tagline {
            font-size: 10px;
            color: #7ec8e3;
            margin-top: 2px;
        }
        .logo-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #e67e22;
            border-radius: 8px;
            text-align: center;
            line-height: 40px;
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin-right: 12px;
            vertical-align: middle;
        }

        /* ── PAGE CONTENT ── */
        .content { padding: 25px 30px; }

        /* ── TITLE ── */
        .report-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #1a2744;
            margin: 20px 0 15px;
            letter-spacing: 2px;
        }

        /* ── META INFO ── */
        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .meta-left, .meta-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .meta-right { text-align: right; }
        .meta-label { color: #666; font-weight: bold; }
        .meta-value { color: #1a2744; font-weight: bold; margin-left: 5px; }

        /* ── SECTION LABEL ── */
        .section-label {
            background: #1a2744;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 15px;
            display: inline-block;
            border-radius: 3px;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── CASHIER DETAILS BOX ── */
        .details-box {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .details-grid {
            display: table;
            width: 100%;
        }
        .details-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .detail-row {
            margin-bottom: 6px;
        }
        .detail-label {
            color: #666;
            font-weight: bold;
            display: inline-block;
            min-width: 70px;
        }
        .detail-sep {
            color: #666;
            margin: 0 6px;
        }

        /* ── TABLE ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table thead th {
            background: #1a2744;
            color: #ffffff;
            padding: 10px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #1a2744;
        }
        .data-table tbody td {
            padding: 10px 8px;
            border: 1px solid #e0e0e0;
            text-align: center;
            font-size: 10px;
            vertical-align: middle;
        }
        .data-table tbody tr:nth-child(even) {
            background: #f8f9fc;
        }

        /* ── STATUS BADGES ── */
        .badge-in {
            background: #d4edda;
            color: #155724;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-out {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        /* ── SUMMARY BOX ── */
        .summary-box {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px 10px;
            margin-bottom: 25px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
            vertical-align: top;
            width: 20%;
        }
        .summary-icon {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            line-height: 28px;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .summary-icon-blue { background: #e3f2fd; color: #1565c0; }
        .summary-icon-green { background: #e8f5e9; color: #2e7d32; }
        .summary-icon-orange { background: #fff3e0; color: #e65100; }
        .summary-icon-red { background: #fce4ec; color: #c62828; }
        .summary-icon-purple { background: #f3e5f5; color: #6a1b9a; }
        .summary-number {
            font-size: 18px;
            font-weight: bold;
            color: #1a2744;
        }
        .summary-label {
            font-size: 9px;
            color: #888;
            text-transform: uppercase;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 30px;
            display: table;
            width: 100%;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }
        .footer-note-label {
            color: #1a2744;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .footer-note {
            color: #888;
            font-size: 9px;
            line-height: 1.6;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 180px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .signature-text {
            font-style: italic;
            font-weight: bold;
            color: #1a2744;
            font-size: 10px;
        }
        .signature-role {
            color: #888;
            font-size: 9px;
        }

        .amount-in { color: #155724; font-weight: bold; }
        .amount-out { color: #c62828; font-weight: bold; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header-bar">
        <div class="header-left">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 40px; height: 40px; vertical-align: middle; margin-right: 12px; object-fit: contain;">
            <span style="display:inline-block; vertical-align:middle;">
                <span class="brand-name">PentaPure</span><br>
                <span class="brand-tagline">PENTAPURE FOOD &amp; SPICES PVT.LTD.</span>
            </span>
        </div>
        <div class="header-right">
            <div>✉ info@pentapure.com</div>
            <div>📞 +91 98765 43210</div>
            <div>🌐 www.pentapure.com</div>
        </div>
    </div>

    <div class="content">
        <!-- TITLE -->
        <div class="report-title">HISTORY REPORT</div>

        <!-- META ROW -->
        <div class="meta-row">
            <div class="meta-left">
                <div><span class="meta-label">Report ID</span> <span class="detail-sep">:</span> <span class="meta-value">RPT-{{ str_pad($reportId, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div><span class="meta-label">Generated On</span> <span class="detail-sep">:</span> <span class="meta-value">{{ $generatedOn }}</span></div>
            </div>
            <div class="meta-right" style="text-align:left; padding-left:40px;">
                <div><span class="meta-label">From Date</span> <span class="detail-sep">:</span> <span class="meta-value">{{ $fromDate }}</span></div>
                <div><span class="meta-label">To Date</span> <span class="detail-sep">:</span> <span class="meta-value">{{ $toDate }}</span></div>
            </div>
        </div>

        <!-- CASHIER DETAILS -->
        <div class="section-label">Cashier Details</div>
        <div class="details-box">
            <div class="details-grid">
                <div class="details-col">
                    <div class="detail-row"><span class="detail-label">Name</span><span class="detail-sep">:</span> {{ $cashierName }}</div>
                    <div class="detail-row"><span class="detail-label">Role</span><span class="detail-sep">:</span> Cashier</div>
                    <div class="detail-row"><span class="detail-label">User ID</span><span class="detail-sep">:</span> {{ $cashierId }}</div>
                </div>
                <div class="details-col">
                    <div class="detail-row"><span class="detail-label">Company</span><span class="detail-sep">:</span> PentaPure Pvt. Ltd.</div>
                    <div class="detail-row"><span class="detail-label">Period</span><span class="detail-sep">:</span> {{ $fromDate }} — {{ $toDate }}</div>
                </div>
            </div>
        </div>

        <!-- TRANSACTION TABLE -->
        <div class="section-label">History Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:12%;">ID</th>
                    <th style="width:12%;">Type</th>
                    <th style="width:14%;">Date</th>
                    <th style="width:14%;">Category</th>
                    <th style="width:14%;">Amount (₹)</th>
                    <th style="width:29%;">Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $idx => $tx)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>TXN-{{ str_pad($tx['id'], 4, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        @if($tx['type'] === 'IN')
                            <span class="badge-in">Income</span>
                        @else
                            <span class="badge-out">Expense</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($tx['date'])->format('d M Y') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $tx['category'] ?? 'General')) }}</td>
                    <td class="{{ $tx['type'] === 'IN' ? 'amount-in' : 'amount-out' }}">
                        {{ $tx['type'] === 'OUT' ? '−' : '+' }}{{ number_format($tx['amount'], 2) }}
                    </td>
                    <td class="text-left">{{ $tx['note'] ?: ($tx['reference'] ? 'Ref: '.$tx['reference'] : '—') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:20px; color:#888;">No transactions found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- SUMMARY -->
        <div class="section-label">Summary</div>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-icon summary-icon-blue">📋</div><br>
                    <span class="summary-number">{{ $totalRecords }}</span><br>
                    <span class="summary-label">Total Records</span>
                </div>
                <div class="summary-item">
                    <div class="summary-icon summary-icon-green">✅</div><br>
                    <span class="summary-number">{{ $totalIn }}</span><br>
                    <span class="summary-label">Income (IN)</span>
                </div>
                <div class="summary-item">
                    <div class="summary-icon summary-icon-orange">📤</div><br>
                    <span class="summary-number">{{ $totalOut }}</span><br>
                    <span class="summary-label">Expense (OUT)</span>
                </div>
                <div class="summary-item">
                    <div class="summary-icon summary-icon-purple">💰</div><br>
                    <span class="summary-number" style="color:#155724;">{{ number_format($sumIn, 2) }}</span><br>
                    <span class="summary-label">Total Income (₹)</span>
                </div>
                <div class="summary-item">
                    <div class="summary-icon summary-icon-red">📉</div><br>
                    <span class="summary-number" style="color:#c62828;">{{ number_format($sumOut, 2) }}</span><br>
                    <span class="summary-label">Total Expense (₹)</span>
                </div>
            </div>
        </div>

        <!-- NET BALANCE -->
        <div style="text-align:right; margin-bottom:25px; padding:12px 20px; background: {{ $balance >= 0 ? '#e8f5e9' : '#fce4ec' }}; border-radius:6px; border:1px solid {{ $balance >= 0 ? '#c8e6c9' : '#f8bbd0' }};">
            <span style="font-size:12px; color:#666; font-weight:bold;">Net Balance: </span>
            <span style="font-size:20px; font-weight:bold; color:{{ $balance >= 0 ? '#2e7d32' : '#c62828' }};">
                ₹{{ number_format(abs($balance), 2) }}
            </span>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div class="footer-left">
                <div class="footer-note-label">Notes:</div>
                <div class="footer-note">
                    This is a system generated report.<br>
                    For any queries, contact the administrator.
                </div>
            </div>
            <div class="footer-right">
                <div style="font-family: DejaVu Sans, sans-serif; font-size:18px; color:#333; margin-bottom:2px;">PentaPure</div>
                <div class="signature-line"></div><br>
                <span class="signature-text">Authorized Signature</span><br>
                <span class="signature-role">PentaPure Admin</span>
            </div>
        </div>
    </div>

</body>
</html>
