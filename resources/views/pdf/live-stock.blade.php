<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PentaPure - Live Stock Valuation Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #101828;
            font-size: 8.5px;
            line-height: 1.35;
            text-transform: uppercase;
        }
        table td { color: #101828 !important; }
        table th { color: #101828 !important; }

        .page {
            width: 100%;
            position: relative;
        }

        /* Branding Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border-bottom: 2px solid #f8c300;
            padding-bottom: 5px;
        }
        .header-logo-cell {
            width: 60%;
            vertical-align: middle;
        }
        .header-contact-cell {
            width: 40%;
            text-align: right;
            vertical-align: middle;
            font-size: 7.5px;
            color: #475467;
            line-height: 1.35;
        }
        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #101828;
            letter-spacing: 0.5px;
        }
        .brand-tagline {
            font-size: 7.5px;
            color: #b45309;
            font-weight: bold;
            letter-spacing: 0.8px;
        }

        /* Title */
        .title-container {
            text-align: center;
            margin: 8px 0 10px 0;
        }
        .title {
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #101828;
            padding: 2px 10px;
        }
        .title-sub {
            font-size: 8px;
            font-weight: normal;
            color: #475467;
            margin-top: 2px;
            text-transform: none;
        }

        /* Summary Stats Cards */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .stats-cell {
            width: 25%;
            padding: 0 2px;
            vertical-align: top;
        }
        .stats-card {
            border: 1px solid #d0d5dd;
            border-radius: 3px;
            padding: 4px 6px;
            text-align: center;
        }
        .stats-label {
            font-size: 6.5px;
            font-weight: bold;
            color: #475467;
            margin-bottom: 1px;
            text-transform: uppercase;
        }
        .stats-val {
            font-family: 'DejaVu Sans', sans-serif !important;
            font-size: 10px;
            font-weight: bold;
        }
        .card-amber { background: #fffdf5; border-color: #fde68a; }
        .card-amber .stats-val { color: #b45309; }
        .card-green { background: #f0fdf4; border-color: #bbf7d0; }
        .card-green .stats-val { color: #15803d; }
        .card-blue { background: #eff6ff; border-color: #bfdbfe; }
        .card-blue .stats-val { color: #1d4ed8; }
        .card-purple { background: #faf5ff; border-color: #e9d5ff; }
        .card-purple .stats-val { color: #7e22ce; }

        /* Meta block */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #fcfcfd;
            border: 1px solid #eaecf0;
            border-radius: 3px;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 7.5px;
            vertical-align: middle;
        }
        .meta-label {
            font-weight: bold;
            color: #475467;
            display: inline-block;
            width: 90px;
        }
        .meta-val {
            color: #101828;
            font-weight: 600;
        }

        /* Section Banner */
        .section-header {
            background: #f8c300;
            color: #101828;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 8.5px;
            border-radius: 2px 2px 0 0;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1px solid #344054;
            table-layout: fixed;
        }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; }

        .data-table th {
            background: #f8c300;
            color: #101828;
            padding: 4px 4px;
            font-weight: bold;
            text-align: left;
            font-size: 7.8px;
            border: 1px solid #344054;
            vertical-align: middle;
        }
        .data-table td {
            padding: 4px 4px;
            border: 1px solid #d0d5dd;
            font-size: 7.8px;
            vertical-align: middle;
            line-height: 1.25;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        /* Badges */
        .badge-stage {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 6.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge-raw { background: #fef3c7; color: #92400e; border: 0.5px solid #fde68a; }
        .badge-semi { background: #ffedd5; color: #c2410c; border: 0.5px solid #fed7aa; }
        .badge-finished, .badge-fg { background: #dcfce7; color: #15803d; border: 0.5px solid #bbf7d0; }
        .badge-packaging, .badge-pkg { background: #e0e7ff; color: #3730a3; border: 0.5px solid #c7d2fe; }

        .badge-grade {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 0.5px solid #bfdbfe;
            padding: 1px 3px;
            font-size: 6.5px;
            font-weight: bold;
            border-radius: 2px;
            margin-left: 2px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Alignments */
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }

        .amount-highlight {
            font-family: 'DejaVu Sans', sans-serif !important;
            font-weight: bold;
            color: #b45309;
        }

        .total-row td {
            background-color: #fffdf5 !important;
            font-family: 'DejaVu Sans', sans-serif !important;
            font-weight: bold;
            font-size: 8.2px;
            border-top: 1.5px solid #f8c300 !important;
            padding: 5px 4px;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-top: 1px solid #d0d5dd;
            padding-top: 6px;
            page-break-inside: avoid;
        }
        .footer-left {
            width: 60%;
            font-size: 7px;
            color: #475467;
            vertical-align: bottom;
            line-height: 1.35;
        }
        .footer-right {
            width: 40%;
            text-align: right;
            font-size: 7.5px;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #475467;
            width: 130px;
            margin-left: auto;
            margin-top: 20px;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
<div class="page">
    @php
        $logoFile = file_exists(public_path('logo.png')) 
            ? public_path('logo.png') 
            : (file_exists(public_path('images/penta-purelogo.png')) ? public_path('images/penta-purelogo.png') : null);
        $logoBase64 = $logoFile ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;

        $totalItemsCount = count($items);
        $totalQtySum = 0;
        foreach ($items as $it) {
            $totalQtySum += (float) ($it['quantity'] ?? 0);
        }
    @endphp

    <!-- Branding Header -->
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <table style="border-collapse: collapse;">
                    <tr>
                        @if($logoBase64)
                        <td style="width: 58px; vertical-align: middle; padding: 0 8px 0 0;">
                            <img src="{{ $logoBase64 }}" style="width: 50px; height: 50px; object-fit: contain; display: block;">
                        </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="brand-title">PentaPure</div>
                            <div class="brand-tagline">FOOD &amp; SPICES PVT. LTD.</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-contact-cell">
                <div><strong>Email:</strong> info@pentapure.com</div>
                <div><strong>Phone:</strong> +91 98765 43210</div>
                <div><strong>Web:</strong> www.pentapure.com</div>
            </td>
        </tr>
    </table>

    <!-- Document Title -->
    <div class="title-container">
        <div class="title">
            PENTAPURE LIVE STOCK AS ON DATE {{ \Carbon\Carbon::parse($date ?? now())->format('d-m-Y') }}
        </div>
        <div class="title-sub">
            @if(!empty($date))
                Historical Stock Valuation Report as on {{ \Carbon\Carbon::parse($date)->format('d M Y') }} (Portrait)
            @else
                Real-Time Live Stock Inventory Status (Portrait)
            @endif
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <table class="stats-table">
        <tr>
            <td class="stats-cell">
                <div class="stats-card card-amber">
                    <div class="stats-label">Total Items</div>
                    <div class="stats-val">{{ $totalItemsCount }}</div>
                </div>
            </td>
            <td class="stats-cell">
                <div class="stats-card card-green">
                    <div class="stats-label">Total Stock Qty</div>
                    <div class="stats-val">{{ number_format($totalQtySum, 2) }}</div>
                </div>
            </td>
            <td class="stats-cell">
                @if(empty($isStockManager))
                <div class="stats-card card-purple">
                    <div class="stats-label">Total Valuation (Ref)</div>
                    <div class="stats-val">&#8377;{{ number_format($totalValuation, 2) }}</div>
                </div>
                @else
                <div class="stats-card card-purple">
                    <div class="stats-label">Active Stages</div>
                    <div class="stats-val">{{ count($stages) }} Stages</div>
                </div>
                @endif
            </td>
            <td class="stats-cell">
                <div class="stats-card card-blue">
                    <div class="stats-label">Report Mode</div>
                    <div class="stats-val">{{ empty($date) ? 'LIVE' : 'HISTORICAL' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Metadata Table -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="meta-label">Generated On:</span>
                <span class="meta-val">{{ $generatedOn }}</span>
            </td>
            <td style="width: 50%;">
                <span class="meta-label">Included Stages:</span>
                <span class="meta-val">
                    @php
                        $stageLabels = array_map(function($st) {
                            $st = strtoupper($st);
                            return $st === 'FINISHED' ? 'FG' : $st;
                        }, $stages);
                    @endphp
                    {{ implode(', ', $stageLabels) }}
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Report Type:</span>
                <span class="meta-val">{{ !empty($isStockManager) ? 'Live Stock Report' : (empty($date) ? 'Stock Valuation (Live)' : 'Stock Valuation (Historical)') }}</span>
            </td>
            <td>
                <span class="meta-label">Valuation Ref:</span>
                <span class="meta-val">{{ !empty($isStockManager) ? 'N/A' : 'Internal Product Reference Rates' }}</span>
            </td>
        </tr>
    </table>

    <div class="section-header">{{ !empty($isStockManager) ? '📦 Stock Inventory Details' : '📦 Stock Inventory & Valuation Details' }}</div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                @if(empty($isStockManager))
                    <th style="width: 4%;" class="text-center">#</th>
                    <th style="width: 40%;">Product Name</th>
                    <th style="width: 27%;">Location Breakdown</th>
                    <th style="width: 13%;" class="text-right">Available Qty</th>
                    <th style="width: 7%;" class="text-right">Rate</th>
                    <th style="width: 9%;" class="text-right">Valuation (&#8377;)</th>
                @else
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 50%;">Product Name</th>
                    <th style="width: 28%;">Location Breakdown</th>
                    <th style="width: 17%;" class="text-right">Available Qty</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                @php
                    $stageRaw = strtoupper($item['stage'] ?? '');
                    $stageLabel = match($stageRaw) {
                        'FINISHED', 'FG' => 'FG',
                        'PACKAGING', 'PKG' => 'PKG',
                        default => $stageRaw
                    };
                    $badgeClass = match($stageRaw) {
                        'RAW' => 'badge-raw',
                        'SEMI' => 'badge-semi',
                        'FINISHED', 'FG' => 'badge-finished',
                        'PACKAGING', 'PKG' => 'badge-packaging',
                        default => 'badge-raw'
                    };
                    $gradeTrim = strtoupper(trim($item['grade'] ?? ''));
                    $hasGrade = !empty($gradeTrim) && !in_array($gradeTrim, ['NONE', 'N/A', 'NA', 'N / A', 'DEFAULT', '-'], true);
                    $gradeLabel = $hasGrade ? (str_starts_with($gradeTrim, 'GRADE') ? $gradeTrim : 'GRADE ' . $gradeTrim) : '';
                @endphp
                <tr>
                    <td class="text-center" style="font-weight: bold; color: #475467;">{{ $idx + 1 }}</td>
                    <td>
                        <span style="font-weight: bold; color: #101828;">{{ $item['name'] }}</span>
                        &nbsp;<span class="badge-stage {{ $badgeClass }}">{{ $stageLabel }}</span>
                        @if($hasGrade)
                            &nbsp;<span class="badge-grade">{{ $gradeLabel }}</span>
                        @endif
                    </td>
                    <td style="font-size: 7.2px;">{!! $item['location'] !!}</td>
                    <td class="text-right" style="font-weight: bold;">
                        {{ number_format($item['quantity'], 2) }} <span style="font-size: 6.8px; font-weight: normal; color: #475467;">{{ $item['unit'] }}</span>
                    </td>
                    @if(empty($isStockManager))
                        <td class="text-right">&#8377;{{ number_format($item['rate'], 2) }}</td>
                        <td class="text-right amount-highlight">&#8377;{{ number_format($item['amount'], 2) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ empty($isStockManager) ? 6 : 4 }}" class="text-center" style="padding: 15px; color: #667085;">
                        No live stock records found matching selected filters.
                    </td>
                </tr>
            @endforelse

            @if(!empty($items))
                @if(empty($isStockManager))
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL STOCK VALUATION (REF):</td>
                        <td class="text-right amount-highlight">&#8377;{{ number_format($totalValuation, 2) }}</td>
                    </tr>
                @else
                    <tr class="total-row">
                        <td colspan="3" class="text-right">TOTAL ITEMS / QUANTITY:</td>
                        <td class="text-right amount-highlight">{{ $totalItemsCount }} items ({{ number_format($totalQtySum, 2) }})</td>
                    </tr>
                @endif
            @endif
        </tbody>
    </table>

    @if(empty($isStockManager))
    <div style="font-size: 6.8px; color: #667085; font-style: italic; margin-bottom: 6px;">
        * Note: Rates and valuation amounts listed above are based on internal stock reference costs and are not linked to sales panels.
    </div>
    @endif

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                <div><strong>Notes:</strong></div>
                <div>• This is an automated, system-generated stock inventory report printed in portrait format.</div>
                @if(!empty($date))
                    <div>• Stock quantities and estimated valuations reflect recorded transactions up to {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</div>
                @else
                    <div>• Stock quantities reflect real-time live inventory recorded in the system.</div>
                @endif
            </td>
            <td class="footer-right">
                <div class="signature-line"></div>
                <div><strong>Authorized Signatory</strong></div>
                <div style="color: #475467; font-size: 7px;">PentaPure Food &amp; Spices Pvt. Ltd.</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
