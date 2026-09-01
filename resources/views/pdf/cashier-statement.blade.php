<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PentaPure - Account Statement</title>
<style>
@page {
    margin: 0;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #1a1a2e;
    line-height: 1.4;
    text-transform: uppercase;
}

/* ── HEADER ── */
.header-table {
    width: 100%;
    background: #f8c300;
    padding: 14px 20px;
}
.brand-name { font-size: 22px; font-weight: bold; color: #101828; letter-spacing: 1px; }
.brand-sub  { font-size: 9px; color: #101828; margin-top: 2px; }

/* ── META BOX ── */
.meta-table {
    width: 100%;
    margin: 14px 20px 0;
    border-collapse: collapse;
    border: 1px solid #aaa;
}
.meta-table td {
    padding: 6px 10px;
    font-size: 9px;
    border: 1px solid #aaa;
}
.meta-title { font-size: 13px; font-weight: bold; }
.meta-sub   { font-size: 9px; color: #444; }

/* ── CONTENT ── */
.content { padding: 14px 20px; }

/* ── SUMMARY STRIP ── */
.summary-table {
    width: 100%;
    margin-bottom: 12px;
    border-collapse: collapse;
    border: 1px solid #ddd;
    background: #f8f9fc;
}
.summary-table td {
    text-align: center;
    padding: 8px 4px;
    border: 1px solid #ddd;
    width: 25%;
}
.summary-val  { font-size: 13px; font-weight: bold; color: #1a2744; }
.summary-lbl  { font-size: 8px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.color-green  { color: #1a7a37; }
.color-red    { color: #b91c1c; }

/* ── MAIN TABLE ── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
    font-size: 9px;
}
.data-table thead th {
    background: #f8c300;
    color: #101828;
    padding: 7px 5px;
    font-weight: bold;
    text-align: left;
    border: 1px solid #667085;
}
.data-table tbody td {
    padding: 7px 5px;
    border: 1px solid #d0d7e3;
    vertical-align: top;
}
.data-table tbody tr:nth-child(even) { background: #f5f7fc; }

.amt-in    { color: #15803d; }
.amt-out   { color: #b91c1c; }

/* ── BALANCE BAR ── */
.balance-table {
    width: 100%;
    margin-bottom: 14px;
}
.balance-table td {
    vertical-align: middle;
}
.bal-right {
    text-align: right; 
    padding: 10px 14px;
    border-radius: 4px;
}
.bal-right.positive { background: #dcfce7; border: 1px solid #86efac; }
.bal-right.negative { background: #fef2f2; border: 1px solid #fca5a5; }
.bal-label  { font-size: 9px; color: #555; font-weight: bold; }
.bal-amount { font-size: 20px; font-weight: bold; }

/* ── FOOTER ── */
.footer-table {
    width: 100%;
    border-top: 1px solid #ccc;
    padding-top: 12px;
    margin-top: 16px;
}
.footer-note  { font-size: 8px; color: #888; line-height: 1.7; }
.sig-line     { border-top: 1px solid #333; width: 160px; display: inline-block; margin-bottom: 3px; }
.sig-name     { font-size: 10px; font-weight: bold; color: #1a2744; }
.sig-role     { font-size: 8px; color: #888; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="width: 65%; color: #101828;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60px; padding: 0; vertical-align: middle; border: none; background: transparent;">
                        @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                        @endif
                    </td>
                    <td style="text-align: left; padding-left: 10px; vertical-align: middle; border: none; background: transparent;">
                        <div class="brand-name">PentaPure</div>
                        <div class="brand-sub">FOOD &amp; SPICES PVT.LTD.</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 35%; text-align: right; color: #101828; font-size: 9px; vertical-align: middle;">
            <div>Report ID: RPT-{{ str_pad($reportId, 4, '0', STR_PAD_LEFT) }}</div>
            <div>Generated: {{ $generatedOn }}</div>
            <div>Cashier: {{ $cashierName }}</div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td colspan="2" style="border-bottom: 1px solid #aaa; background: #f0f0f0; padding: 10px;">
            <span class="meta-title" style="font-size: 15px; font-weight: bold;">
                DURATION: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
            </span>
        </td>
    </tr>
    <tr>
        <td style="width: 50%;">CATEGORY: {{ $category }}</td>
        <td style="width: 50%;">SITE: {{ $site }}</td>
    </tr>
    <tr>
        <td style="color: #15803d; font-weight: bold;">OPENING BALANCE: {{ number_format($openingBalance, 2) }}</td>
        <td style="color: #b91c1c; font-weight: bold;">CLOSING BALANCE: {{ number_format($closingBalance, 2) }}</td>
    </tr>
</table>

<div class="content">

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 90px;">Date</th>
                <th>Description</th>
                <th style="width: 90px;">Category</th>
                <th style="width: 90px; text-align: right;">Amt</th>
                <th style="width: 100px; text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-M-Y') }}</td>
                <td>
                    {{ $row['note'] ?: ($row['description'] ?: '—') }}
                    @if($row['reference'])
                        <br><span style="font-size: 7.5px; color: #777;">Ref: {{ $row['reference'] }}</span>
                    @endif
                </td>
                <td>{{ strtoupper(str_replace('_',' ', $row['category'])) }}</td>
                <td style="text-align: right;" class="{{ $row['type'] === 'IN' ? 'amt-in' : 'amt-out' }}">
                    {{ number_format($row['amount'], 2) }}
                </td>
                <td style="text-align: right;">
                    <strong>{{ number_format($row['closing_bal'], 2) }}</strong>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding:20px; text-align:center; color:#888;">
                    No transactions found for this period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="balance-table">
        <tr>
            <td style="width: 60%; font-size: 9px; color: #555;">
                Opening Balance: <strong>{{ number_format($openingBalance, 2) }}</strong>
                &nbsp;+&nbsp; Income: <strong style="color:#15803d;">{{ number_format($sumIn, 2) }}</strong>
                &nbsp;−&nbsp; Expense: <strong style="color:#b91c1c;">{{ number_format($sumOut, 2) }}</strong>
            </td>
            <td style="width: 40%;" class="bal-right {{ $closingBalance >= 0 ? 'positive' : 'negative' }}">
                <div class="bal-label">Closing Balance</div>
                <div class="bal-amount {{ $closingBalance >= 0 ? 'color-green' : 'color-red' }}">
                    {{ number_format(abs($closingBalance), 2) }}
                </div>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td style="width: 60%; vertical-align: bottom;">
                <div class="footer-note">
                    This is a system-generated statement. No signature required.<br>
                    For queries, contact the PentaPure administrator.<br>
                    Report Period: {{ $fromDate }} to {{ $toDate }}
                    @if($includeBills && collect($rows)->flatMap(fn($r)=>$r['bills'])->isNotEmpty())
                        &nbsp;| Bill attachments follow on next pages.
                    @endif
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom;">
                <div style="font-family: DejaVu Sans, sans-serif; font-size:16px; color:#1a2744; margin-bottom:3px;">PentaPure</div>
                <div class="sig-line"></div><br>
                <div class="sig-name">Authorized Signature</div>
                <div class="sig-role">PentaPure Admin</div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
