<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>PentaPure - Account Statement</title>
<style>
@page {
    margin: 15px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 9px;
    color: #1e293b;
    line-height: 1.35;
    background: #ffffff;
}

.pdf-container {
    padding: 15px 25px;
}

/* ── HEADER ── */
.header-table {
    width: 100%;
    background: #ffc107;
    padding: 10px 14px;
    border-radius: 4px;
    margin-bottom: 15px;
    border-collapse: collapse;
}
.brand-name { font-size: 18px; font-weight: bold; color: #000000; letter-spacing: 0.5px; }
.brand-sub  { font-size: 8px; font-weight: bold; color: #222222; margin-top: 1px; }

/* ── META BOX ── */
.meta-table {
    width: 100%;
    margin-bottom: 12px;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
}
.meta-table td {
    padding: 6px 10px;
    font-size: 9px;
    border: 1px solid #cbd5e1;
}
.meta-title { font-size: 12px; font-weight: bold; color: #0f172a; }

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
    background: #ffc107;
    color: #000000;
    padding: 7px 6px;
    font-weight: bold;
    text-align: left;
    border: 1px solid #94a3b8;
    font-size: 9px;
}
.data-table tbody td {
    padding: 6px 6px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
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

/* ── FOOTER ── */
.footer-table {
    width: 100%;
    border-top: 1px solid #cbd5e1;
    padding-top: 10px;
    margin-top: 15px;
    border-collapse: collapse;
}
.footer-note  { font-size: 8px; color: #64748b; line-height: 1.4; }
.sig-line     { border-top: 1px solid #334155; width: 140px; display: inline-block; margin-bottom: 3px; }
.sig-name     { font-size: 9px; font-weight: bold; color: #0f172a; }
.sig-role     { font-size: 8px; color: #64748b; }
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
                        @if(file_exists(public_path('logo.png')))
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 40px; height: 40px; object-fit: contain;">
                        @endif
                    </td>
                    <td style="text-align: left; padding-left: 8px; vertical-align: middle; border: none; background: transparent;">
                        <div class="brand-name">PentaPure</div>
                        <div class="brand-sub">FOOD &amp; SPICES PVT.LTD.</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 40%; text-align: right; color: #101828; font-size: 8.5px; vertical-align: middle;">
            <div>Report ID: RPT-{{ str_pad($reportId, 4, '0', STR_PAD_LEFT) }}</div>
            <div>Generated: {{ $generatedOn }}</div>
            <div>Cashier: {{ $cashierName }}</div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td colspan="2" style="border-bottom: 1px solid #cbd5e1; background: #f1f5f9; padding: 8px 10px;">
            <span class="meta-title">
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
                <th style="width: 85px;">Date</th>
                <th>Description</th>
                <th style="width: 90px;">Category</th>
                <th style="width: 85px; text-align: right;">Amt</th>
                <th style="width: 95px; text-align: right;">Balance</th>
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
                <td colspan="5" style="padding:15px; text-align:center; color:#888;">
                    No transactions found for this period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="balance-table">
        <tr>
            <td style="width: 55%; font-size: 8.5px; color: #555;">
                Opening Balance: <strong>{{ number_format($openingBalance, 2) }}</strong>
                &nbsp;+&nbsp; Income: <strong style="color:#15803d;">{{ number_format($sumIn, 2) }}</strong>
                &nbsp;−&nbsp; Expense: <strong style="color:#b91c1c;">{{ number_format($sumOut, 2) }}</strong>
            </td>
            <td style="width: 45%;" class="bal-right {{ $closingBalance >= 0 ? 'positive' : 'negative' }}">
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
            <td style="width: 40%; text-align: right; vertical-align: bottom; padding-right: 10px;">
                <div style="font-size: 14px; font-weight: bold; color: #1a2744; margin-bottom: 2px;">PentaPure</div>
                <div class="sig-line"></div><br>
                <div class="sig-name">Authorized Signature</div>
                <div class="sig-role">PentaPure Admin</div>
            </td>
        </tr>
    </table>

</div>
</div>
</body>
</html>
