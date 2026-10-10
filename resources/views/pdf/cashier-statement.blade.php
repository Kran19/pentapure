<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PPF - ACCOUNT STATEMENT</title>
<style>
@page {
    size: A4 portrait;
    margin: 12px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 9px;
    color: #1e293b;
    line-height: 1.35;
    background: #ffffff;
    text-transform: uppercase !important;
}
table, th, td, div, span, p, a, strong, small {
    text-transform: uppercase !important;
}

.pdf-container {
    padding: 8px 12px;
}

/* ── HEADER ── */
.header-table {
    width: 100%;
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 4px;
    margin-bottom: 15px;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
    border-top: 3px solid #f59e0b;
}
.brand-name { font-size: 18px; font-weight: bold; color: #0f172a; letter-spacing: 0.5px; text-transform: uppercase; }
.brand-sub  { font-size: 8px; font-weight: bold; color: #475569; margin-top: 1px; text-transform: uppercase; }

/* ── META BOX ── */
.meta-table {
    width: 100%;
    margin-bottom: 12px;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
    background: #ffffff;
}
.meta-table td {
    padding: 6px 10px;
    font-size: 9px;
    border: 1px solid #cbd5e1;
    text-transform: uppercase;
}
.meta-title { font-size: 12px; font-weight: bold; color: #0f172a; text-transform: uppercase; }

/* ── CONTENT ── */
.content { width: 100%; }

/* ── MAIN TABLE ── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 9px;
}
.data-table thead th {
    background: #f1f5f9;
    color: #0f172a;
    padding: 7px 6px;
    font-weight: bold;
    text-align: left;
    border: 1px solid #cbd5e1;
    font-size: 9px;
    text-transform: uppercase;
}
.data-table tbody td {
    padding: 6px 6px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
    text-transform: uppercase;
}
.data-table tbody tr:nth-child(even) { background: #f8fafc; }

.amt-in    { color: #16a34a; font-weight: bold; }
.amt-out   { color: #dc2626; font-weight: bold; }

/* ── BALANCE BAR ── */
.balance-table {
    width: 100%;
    margin-bottom: 14px;
    border-collapse: collapse;
}
.balance-table td {
    vertical-align: middle;
    text-transform: uppercase;
}
.bal-right {
    text-align: right; 
    padding: 8px 14px;
    border-radius: 4px;
}
.bal-right.positive { background: #f0fdf4; border: 1px solid #86efac; }
.bal-right.negative { background: #fef2f2; border: 1px solid #fca5a5; }
.bal-label  { font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase; }
.bal-amount { font-size: 15px; font-weight: bold; margin-top: 1px; }
.color-green { color: #16a34a; }
.color-red   { color: #dc2626; }

/* ── FOOTER ── */
.footer-table {
    width: 100%;
    border-top: 1px solid #cbd5e1;
    padding-top: 10px;
    margin-top: 15px;
    border-collapse: collapse;
}
.footer-note  { font-size: 8px; color: #64748b; line-height: 1.4; text-transform: uppercase; }
.sig-line     { border-top: 1px solid #334155; width: 140px; display: inline-block; margin-bottom: 3px; }
.sig-name     { font-size: 9px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
.sig-role     { font-size: 8px; color: #64748b; text-transform: uppercase; }
</style>
</head>
<body>
<div class="pdf-container">

<table class="header-table">
    <tr>
        <td style="width: 60%; color: #101828; vertical-align: middle;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 45px; padding: 0; vertical-align: middle; border: none; background: transparent;">
                        @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 40px; height: 40px; object-fit: contain;">
                        @endif
                    </td>
                    <td style="text-align: left; padding-left: 8px; vertical-align: middle; border: none; background: transparent;">
                        <div class="brand-name">PPF</div>
                        <div class="brand-sub">FOOD &amp; SPICES PVT. LTD.</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 40%; text-align: right; color: #334155; font-size: 8.5px; vertical-align: middle; line-height: 1.45;">
            <div><strong style="color:#0f172a;">REPORT ID:</strong> RPT-{{ str_pad($reportId, 4, '0', STR_PAD_LEFT) }}</div>
            <div><strong style="color:#0f172a;">GENERATED:</strong> {{ strtoupper($generatedOn) }}</div>
            <div><strong style="color:#0f172a;">CASHIER:</strong> {{ strtoupper($cashierName) }}</div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td colspan="2" style="border-bottom: 1px solid #cbd5e1; background: #f1f5f9; padding: 8px 10px;">
            <span class="meta-title">
                @if(empty($from) && empty($to) && empty(request('from')) && empty(request('to')))
                    DURATION: ALL TIME (ALL RECORDS)
                @else
                    DURATION: {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}
                @endif
            </span>
        </td>
    </tr>
    <tr>
        <td colspan="2">SITE: {{ strtoupper($site) }}</td>
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
                <th style="width: 85px;">DATE</th>
                <th>DESCRIPTION</th>
                <th style="width: 95px; text-align: right;">AMT</th>
                <th style="width: 105px; text-align: right;">BALANCE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $row)
            <tr>
                <td>
                    {{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}
                    <br><span style="font-size: 7.5px; color: #64748b;">{{ strtoupper(\Carbon\Carbon::parse($row['date'])->format('h:i A')) }}</span>
                </td>
                <td>
                    <div style="font-weight: bold; color: #0f172a;">{{ strtoupper($row['note'] ?: ($row['description'] ?: '—')) }}</div>
                    @if(!empty($row['description']) && !empty($row['note']) && strtoupper($row['description']) !== strtoupper($row['note']))
                        <div style="font-size: 7.5px; color: #64748b;">{{ strtoupper($row['description']) }}</div>
                    @endif
                    @if(!empty($row['reference']))
                        <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">
                            <span>REF: {{ strtoupper($row['reference']) }}</span>
                        </div>
                    @endif
                </td>
                <td style="text-align: right;" class="{{ $row['type'] === 'IN' ? 'amt-in' : 'amt-out' }}">
                    {{ $row['type'] === 'IN' ? '+' : '-' }}{{ number_format($row['amount'], 2) }}
                </td>
                <td style="text-align: right;">
                    <strong>{{ number_format($row['closing_bal'], 2) }}</strong>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding:15px; text-align:center; color:#888;">
                    NO TRANSACTIONS FOUND FOR THIS PERIOD.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="balance-table">
        <tr>
            <td style="width: 55%; font-size: 8.5px; color: #555;">
                OPENING BALANCE: <strong>{{ number_format($openingBalance, 2) }}</strong>
                &nbsp;+&nbsp; INCOME: <strong style="color:#15803d;">{{ number_format($sumIn, 2) }}</strong>
                &nbsp;−&nbsp; EXPENSE: <strong style="color:#b91c1c;">{{ number_format($sumOut, 2) }}</strong>
            </td>
            <td style="width: 45%;" class="bal-right {{ $closingBalance >= 0 ? 'positive' : 'negative' }}">
                <div class="bal-label">CLOSING BALANCE</div>
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
                    THIS IS A SYSTEM-GENERATED STATEMENT. NO SIGNATURE REQUIRED.<br>
                    FOR QUERIES, CONTACT THE PPF ADMINISTRATOR.<br>
                    REPORT PERIOD: {{ (empty($from) && empty($to) && empty(request('from')) && empty(request('to'))) ? 'ALL TIME (ALL RECORDS)' : (\Carbon\Carbon::parse($fromDate)->format('d-m-Y') . ' TO ' . \Carbon\Carbon::parse($toDate)->format('d-m-Y')) }}
                    @if($includeBills && collect($rows)->flatMap(fn($r)=>$r['bills'])->isNotEmpty())
                        &nbsp;| BILL ATTACHMENTS FOLLOW ON NEXT PAGES.
                    @endif
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom; padding-right: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: #1a2744; margin-bottom: 2px;">PPF</div>
                <div class="sig-line"></div><br>
                <div class="sig-name">AUTHORIZED SIGNATURE</div>
                <div class="sig-role">PPF ADMIN</div>
            </td>
        </tr>
    </table>

</div>
</div>
</body>
</html>
