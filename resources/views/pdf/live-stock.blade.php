<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PentaPure - Live Stock Valuation Report</title>
        <style>
        @page { margin: 18px; }
        * { box-sizing: border-box; }        body { font-family: DejaVu Sans, sans-serif; color: #101828; font-size: 13px; line-height: 1.45; text-transform: uppercase; }
        table td { color: #101828 !important; }
        table th { color: #101828 !important; }

        .page { border: 1px solid #1f2937; padding: 42px 46px 24px; min-height: 1060px; border-bottom: 7px solid #f8c300; }
        .top { display: table; width: 100%; padding-bottom: 22px; border-bottom: 1px solid #667085; }
        .brand, .contact { display: table-cell; vertical-align: top; width: 50%; }
        .contact { text-align: left; padding-left: 110px; font-size: 14px; }
        .contact div { margin-bottom: 9px; }
        .brand-text { display: inline-block; vertical-align: middle; }
        .brand-title { font-size: 34px; font-weight: 800; color: #111827; }
        .tagline { font-size: 14px; margin-top: 2px; color: #111827; }
        .title { text-align: center; margin: 34px 0 26px; font-size: 32px; font-weight: 800; letter-spacing: 1px; color: #111827; }
        .meta { display: table; width: 100%; margin-bottom: 34px; font-size: 16px; }
        .meta-col { display: table-cell; width: 50%; }
        .meta-row { margin-bottom: 11px; }
        .label { display: inline-block; min-width: 130px; font-weight: 600; }
        .colon { display: inline-block; width: 20px; text-align: center; }
        .section-label { background: #f8c300; color: #101828; display: inline-block; padding: 8px 28px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 15px; }
        .box { border: 1px solid #d0d5dd; border-radius: 5px; padding: 28px 30px; margin-top: -1px; margin-bottom: 30px; background-color: #fffdf5; }
        .details { display: table; width: 100%; }
        .details-col { display: table-cell; width: 50%; vertical-align: top; }
        .divider { border-left: 1px solid #d0d5dd; padding-left: 34px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8c300; color: #101828; padding: 14px 10px; border: 1px solid #667085; font-size: 14px; text-align: left; }
        td { padding: 13px 10px; border: 1px solid #d9dee7; vertical-align: top; }
        td.center, th.center { text-align: center; }
        td.amount { text-align: right; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 11px; }
        .badge-raw { background: #fff8e1; color: #b37400; }
        .badge-semi { background: #fff8e1; color: #ff6f00; }
        .badge-finished { background: #e8f5e9; color: #1b5e20; }
        .summary { display: table; width: 100%; }
        .summary-item { display: table-cell; text-align: center; border-right: 1px solid #d0d5dd; width: 33.33%; }
        .summary-item:last-child { border-right: 0; }
        .summary-number { font-size: 22px; font-weight: 800; }
        .summary-label { font-size: 12px; margin-top: 4px; color: #667085; }
        .footer { margin-top: 38px; padding-top: 26px; border-top: 1px solid #98a2b3; display: table; width: 100%; }
        .notes, .sign { display: table-cell; width: 50%; vertical-align: bottom; }
        .notes-title { color: #f8c300; font-weight: 800; margin-bottom: 12px; text-shadow: 0.5px 0.5px 0 #000; }

        .sign { text-align: center; padding-left: 130px; }
        .scribble { font-family: DejaVu Sans, sans-serif; font-size: 28px; margin-bottom: 6px; }
        .line { border-top: 1px solid #667085; margin: 0 auto 8px; width: 210px; }
    </style>
</head>
<body>
<div class="page">
    <div class="top">
        <div class="brand">
            @if(file_exists(public_path('logo.png')))
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 82px; height: 82px; vertical-align: middle; margin-right: 12px; object-fit: contain;">
            @endif
            <div class="brand-text">
                <div class="brand-title">PentaPure</div>
                <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
            </div>
        </div>
        <div class="contact">
            <div>info@pentapure.com</div>
            <div>+91 98765 43210</div>
            <div>www.pentapure.com</div>
        </div>
    </div>
    <div class="title">
        @if(!empty($date))
            Stock Valuation Report <br>
            <span style="font-size: 16px; font-weight: normal; color: #667085; text-transform: none;">
                Up To {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
            </span>
        @else
            Live Stock Valuation Report
        @endif
    </div>

    <div class="meta">
        <div class="meta-col">
            <div class="meta-row"><span class="label">Report Type</span><span class="colon">:</span>{{ empty($date) ? 'Stock Valuation (Live)' : 'Stock Valuation (Historical)' }}</div>
            <div class="meta-row"><span class="label">Generated On</span><span class="colon">:</span>{{ $generatedOn }}</div>
        </div>
        <div class="meta-col">
            <div class="meta-row"><span class="label">Included Stages</span><span class="colon">:</span>{{ implode(', ', $stages) }}</div>
            <div class="meta-row"><span class="label">Valuation Ref</span><span class="colon">:</span>Internal Reference</div>
        </div>
    </div>

    <div class="section-label">Stock Valuation Details</div>
    <div style="margin-top: 10px; margin-bottom: 20px;">
        <span style="font-size: 11px; color: #667085; font-style: italic;">Note: Rates and amounts listed below are for stock valuation reference only and are not linked to sales panels.</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30%; color:#111827;">Product Name</th>
                <th style="width:12%; color:#111827;">Stage</th>
                <th style="width:23%; color:#111827;">Location</th>
                <th style="width:15%; text-align: right; color:#111827;">Available Qty</th>
                <th style="width:10%; text-align: right; color:#111827;">Rate / Unit</th>
                <th style="width:10%; text-align: right; color:#111827;">Valuation (Rs.)</th>
            </tr>
        </thead>

        <tbody>
            @forelse($items as $item)
                @php
                    $gStr = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? ' ' . strtoupper($item['grade']) : '';
                    $displayType = strtolower($item['stage']) === 'finished' ? 'fg' : strtolower($item['stage']);
                @endphp
                <tr>
                    <td style="font-weight: 600;">{{ $item['name'] }}{{ $gStr }} ({{ $displayType }})</td>
                    <td>
                        <span class="badge {{ $item['stage'] === 'RAW' ? 'badge-raw' : ($item['stage'] === 'SEMI' ? 'badge-semi' : 'badge-finished') }}">
                            {{ $item['stage'] === 'FINISHED' ? 'FG' : $item['stage'] }}
                        </span>
                    </td>
                    <td style="font-size: 11px; text-transform: uppercase;">{{ $item['location'] }}</td>
                    <td style="text-align: right; font-weight: 600;">
                        {{ number_format($item['quantity'], 2) }} <span style="font-size: 10px; font-weight: normal; color: #667085;">{{ $item['unit'] }}</span>
                    </td>
                    <td style="text-align: right;">₹{{ number_format($item['rate'], 2) }}</td>
                    <td class="amount">₹{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">No live stock records found matching filters.</td>
                </tr>
            @endforelse
            @if(!empty($items))
                <tr style="background-color: #fffdf5; font-weight: bold; font-size: 14px;">
                    <td colspan="5" style="text-align: right; border-top: 2px solid #f8c300; padding: 15px 10px;">Total Stock Valuation (Ref):</td>
                    <td class="amount" style="border-top: 2px solid #f8c300; padding: 15px 10px; color: #b37400;">₹{{ number_format($totalValuation, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <div class="notes">
            <div class="notes-title">Notes:</div>
            <div>
                @if(!empty($date))
                    This is a system generated report representing stock estimates up to the selected date.<br>
                @else
                    This is a system generated report representing live stock estimates.<br>
                @endif
                Valuations are based on reference costs at product creation.
            </div>
        </div>

        <div class="sign">
            <div class="scribble">PentaPure</div>
            <div class="line"></div>
            <strong>Authorized Signature</strong><br>
            PentaPure Admin
        </div>
    </div>
</div>
</body>
</html>
