<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PentaPure - Account Statement</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10px;
    color: #1a1a2e;
    line-height: 1.4;
    text-transform: uppercase;
}

/* ── HEADER ── */
.header-bar {
    background: #1a2744;
    padding: 14px 20px;
    display: table;
    width: 100%;
}
.header-left { display: table-cell; vertical-align: middle; width: 65%; }
.header-right {
    display: table-cell; vertical-align: middle;
    text-align: right; width: 35%;
    color: #a8c6e0; font-size: 9px;
}
.brand-name { font-size: 22px; font-weight: bold; color: #fff; letter-spacing: 1px; }
.brand-sub  { font-size: 9px; color: #7ec8e3; margin-top: 2px; }
.logo-badge {
    display: inline-block; width: 36px; height: 36px;
    background: #e67e22; border-radius: 6px;
    text-align: center; line-height: 36px;
    color: white; font-size: 18px; font-weight: bold;
    margin-right: 10px; vertical-align: middle;
}

/* ── REPORT META BOX (matches reference image) ── */
.meta-outer {
    margin: 14px 20px 0;
    border: 1px solid #aaa;
}
.meta-title-row {
    padding: 6px 10px;
    border-bottom: 1px solid #aaa;
}
.meta-title { font-size: 13px; font-weight: bold; }
.meta-sub   { font-size: 9px; color: #444; }
.meta-split {
    display: table; width: 100%;
    border-bottom: 1px solid #aaa;
}
.meta-cell {
    display: table-cell; padding: 5px 10px;
    font-size: 9px; width: 50%;
    vertical-align: middle;
}
.meta-cell:first-child { border-right: 1px solid #aaa; }

/* ── CONTENT ── */
.content { padding: 14px 20px; }

/* ── SUMMARY STRIP ── */
.summary-strip {
    display: table; width: 100%;
    margin-bottom: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fc;
}
.summary-cell {
    display: table-cell;
    text-align: center;
    padding: 8px 4px;
    border-right: 1px solid #ddd;
    width: 25%;
}
.summary-cell:last-child { border-right: none; }
.summary-val  { font-size: 13px; font-weight: bold; color: #1a2744; }
.summary-lbl  { font-size: 8px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.color-green  { color: #1a7a37 !important; }
.color-red    { color: #b91c1c !important; }
.color-navy   { color: #1a2744 !important; }

/* ── MAIN TABLE ── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
    font-size: 9px;
}
.data-table thead th {
    background: #1a2744;
    color: #fff;
    padding: 7px 5px;
    font-size: 9px;
    font-weight: bold;
    text-align: left;
    border: 1px solid #1a2744;
    letter-spacing: 0.3px;
}
.data-table tbody td {
    padding: 7px 5px;
    border: 1px solid #d0d7e3;
    vertical-align: top;
}
.data-table tbody tr:nth-child(even) { background: #f5f7fc; }
.data-table tbody tr:hover { background: #eef2f9; }

.td-date  { width: 11%; white-space: nowrap; }
.td-desc  { width: 25%; }
.td-acc   { width: 18%; }
.td-amt   { width: 8%; text-align: right; font-weight: bold; }
.td-bal   { width: 10%; text-align: right; font-weight: bold; }
.td-by    { width: 8%; }
.td-site  { width: 8%; }
.td-bill  { width: 6%; text-align: center; }

.cat-label { font-size: 7.5px; color: #777; display: block; margin-top: 2px; text-transform: uppercase; }
.amt-in    { color: #15803d; }
.amt-out   { color: #b91c1c; }
.bal-num   { font-size: 9px; }

/* ── BALANCE BAR ── */
.balance-bar {
    display: table; width: 100%;
    margin-bottom: 14px;
}
.bal-left { display: table-cell; width: 60%; vertical-align: middle; }
.bal-right {
    display: table-cell; width: 40%; text-align: right; vertical-align: middle;
    padding: 10px 14px;
    border-radius: 4px;
}
.bal-right.positive { background: #dcfce7; border: 1px solid #86efac; }
.bal-right.negative { background: #fef2f2; border: 1px solid #fca5a5; }
.bal-label  { font-size: 9px; color: #555; font-weight: bold; }
.bal-amount { font-size: 20px; font-weight: bold; }

/* ── FOOTER ── */
.footer {
    display: table; width: 100%;
    border-top: 1px solid #ccc;
    padding-top: 12px;
    margin-top: 16px;
}
.footer-left  { display: table-cell; width: 60%; vertical-align: bottom; }
.footer-right { display: table-cell; width: 40%; text-align: right; vertical-align: bottom; }
.footer-note  { font-size: 8px; color: #888; line-height: 1.7; }
.sig-line     { border-top: 1px solid #333; width: 160px; display: inline-block; margin-bottom: 3px; }
.sig-name     { font-size: 10px; font-weight: bold; color: #1a2744; }
.sig-role     { font-size: 8px; color: #888; }

/* ── PAGE BREAK ── */
.page-break { page-break-before: always; }

/* ── BILL COVER PAGE ── */
.bill-cover {
    padding: 20px;
    text-align: center;
}
.bill-header-box {
    background: #1a2744;
    color: #fff;
    padding: 14px 20px;
    margin-bottom: 16px;
    border-radius: 4px;
    display: table;
    width: 100%;
}
.bill-img {
    max-width: 100%;
    max-height: 700px;
    display: block;
    margin: 0 auto;
    border: 1px solid #ccc;
}
.bill-footer-note {
    font-size: 9px; color: #888;
    border-top: 1px solid #ddd;
    padding-top: 6px;
    margin-top: 16px;
    text-align: center;
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- HEADER -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="header-bar">
    <div class="header-left">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 36px; height: 36px; border-radius: 6px; vertical-align: middle; margin-right: 10px; object-fit: contain;">
        <span style="display:inline-block; vertical-align:middle;">
            <span class="brand-name">PentaPure</span><br>
            <span class="brand-sub">PENTAPURE FOOD AND SPICES</span>
        </span>
    </div>
    <div class="header-right">
        <div>Report ID: RPT-{{ str_pad($reportId, 4, '0', STR_PAD_LEFT) }}</div>
        <div>Generated: {{ $generatedOn }}</div>
        <div>Cashier: {{ $cashierName }}</div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- META BOX (matches reference passbook style) -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="meta-outer">
    <div class="meta-title-row">
        <div class="meta-title">Report</div>
        <div class="meta-sub">As on {{ $generatedOn }}</div>
    </div>
    <div class="meta-split">
        <div class="meta-cell">Duration: {{ $fromDate }} - {{ $toDate }}</div>
        <div class="meta-cell">Site: {{ $site }}</div>
    </div>
    <div class="meta-split">
        <div class="meta-cell">Category: {{ $category }}</div>
        <div class="meta-cell">Opening Balance: ₹{{ number_format($openingBalance, 2) }}</div>
    </div>
    <div class="meta-split" style="border-bottom:none;">
        <div class="meta-cell" style="color: {{ $closingBalance >= 0 ? '#15803d' : '#b91c1c' }};">
            <strong>Closing Balance: ₹{{ number_format(abs($closingBalance), 2) }}</strong>
        </div>
        <div class="meta-cell"></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════ -->
<!-- CONTENT -->
<!-- ══════════════════════════════════════════════════════════════════════ -->
<div class="content">

    <!-- SUMMARY STRIP -->
    <div class="summary-strip">
        <div class="summary-cell">
            <div class="summary-val color-navy">{{ $totalRecords }}</div>
            <div class="summary-lbl">Transactions</div>
        </div>
        <div class="summary-cell">
            <div class="summary-val color-navy">₹{{ number_format($openingBalance, 2) }}</div>
            <div class="summary-lbl">Opening Balance</div>
        </div>
        <div class="summary-cell">
            <div class="summary-val color-green">+₹{{ number_format($sumIn, 2) }}</div>
            <div class="summary-lbl">Total Income</div>
        </div>
        <div class="summary-cell">
            <div class="summary-val color-red">−₹{{ number_format($sumOut, 2) }}</div>
            <div class="summary-lbl">Total Expense</div>
        </div>
    </div>

    <!-- TRANSACTION TABLE -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="td-date">Date</th>
                <th class="td-desc">Desc</th>
                <th class="td-acc">Expense Category</th>
                <th class="td-amt">Amt</th>
                <th class="td-bal">Available Balance</th>
                <th class="td-by">Added By</th>
                <th class="td-site">Site</th>
                <th class="td-bill">Bill</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $row)
            <tr>
                <td class="td-date">
                    {{ \Carbon\Carbon::parse($row['date'])->format('d-M-Y') }}
                </td>
                <td class="td-desc">
                    {{ $row['note'] ?: ($row['description'] ?: '—') }}
                    @if($row['reference'])
                        <span class="cat-label">Ref: {{ $row['reference'] }}</span>
                    @endif
                </td>
                <td class="td-acc">{{ strtoupper(str_replace('_',' ', $row['category'])) }}</td>
                <td class="td-amt {{ $row['type'] === 'IN' ? 'amt-in' : 'amt-out' }}">
                    {{ number_format($row['amount'], 2) }}
                </td>
                <td class="td-bal">
                    <strong>₹{{ number_format($row['closing_bal'], 2) }}</strong>
                </td>
                <td class="td-by">{{ $cashierName }}</td>
                <td class="td-site">{{ $row['site'] ?: 'Pentapure' }}</td>
                <td class="td-bill">
                    @if(count($row['bills']) > 0)
                        <span style="background:#1a2744; color:#fff; border-radius:8px; padding:1px 5px; font-size:8px;">
                            {{ count($row['bills']) }}
                        </span>
                    @else
                        <span style="color:#ccc;">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding:20px; text-align:center; color:#888;">
                    No transactions found for this period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- CLOSING BALANCE BAR -->
    <div class="balance-bar">
        <div class="bal-left">
            <div style="font-size:9px; color:#555; margin-bottom:4px;">
                Opening Balance: <strong>₹{{ number_format($openingBalance, 2) }}</strong>
                &nbsp;+&nbsp; Income: <strong style="color:#15803d;">₹{{ number_format($sumIn, 2) }}</strong>
                &nbsp;−&nbsp; Expense: <strong style="color:#b91c1c;">₹{{ number_format($sumOut, 2) }}</strong>
            </div>
        </div>
        <div class="bal-right {{ $closingBalance >= 0 ? 'positive' : 'negative' }}">
            <div class="bal-label">Closing Balance</div>
            <div class="bal-amount {{ $closingBalance >= 0 ? 'color-green' : 'color-red' }}">
                ₹{{ number_format(abs($closingBalance), 2) }}
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-left">
            <div class="footer-note">
                This is a system-generated statement. No signature required.<br>
                For queries, contact the PentaPure administrator.<br>
                Report Period: {{ $fromDate }} to {{ $toDate }}
                @if($includeBills && collect($rows)->flatMap(fn($r)=>$r['bills'])->isNotEmpty())
                    &nbsp;| Bill attachments follow on next pages.
                @endif
            </div>
        </div>
        <div class="footer-right">
            <div style="font-family: serif; font-size:16px; color:#1a2744; margin-bottom:3px;">PentaPure</div>
            <div class="sig-line"></div><br>
            <div class="sig-name">Authorized Signature</div>
            <div class="sig-role">PentaPure Admin</div>
        </div>
    </div>

</div>
<!-- ══ END OF STATEMENT PAGE — Bill pages are appended by FPDI merger ══ -->
</body>
</html>
