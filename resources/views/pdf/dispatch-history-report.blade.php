<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispatch History Report - PentaPure</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 8mm 8mm;
        }
        body { font-family: 'DejaVu Sans', sans-serif; color: #101828; font-size: 8.5px; line-height: 1.35; text-transform: uppercase; }
        * { box-sizing: border-box; }
        .page { width: 100%; position: relative; }
        
        /* Branding header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; border-bottom: 2px solid #f8c300; padding-bottom: 4px; }
        .header-logo-cell { width: 60%; vertical-align: middle; }
        .header-contact-cell { width: 40%; text-align: right; vertical-align: middle; font-size: 8px; color: #475467; }
        .brand-title { font-size: 17px; font-weight: 800; color: #101828; letter-spacing: 0.5px; }
        .brand-tagline { font-size: 8px; color: #d88a00; font-weight: bold; letter-spacing: 1px; }
        
        /* Title */
        .title-container { text-align: center; margin: 4px 0 8px 0; }
        .title { display: inline-block; font-size: 13px; font-weight: 800; letter-spacing: 1px; color: #101828; padding: 0 10px; }
        .title-line { height: 1px; background: #eaecf0; margin-top: 2px; }
        
        /* Metadata block */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; background-color: #fcfcfd; border: 1px solid #eaecf0; border-radius: 3px; }
        .meta-table td { padding: 4px 8px; font-size: 8px; vertical-align: middle; }
        .meta-label { font-weight: bold; color: #475467; display: inline-block; width: 75px; }
        .meta-value { color: #101828; }
        
        /* Stats cards */
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .stats-card-cell { width: 16.66%; padding: 0 2px; }
        .stats-card { border: 1px solid #d0d5dd; border-radius: 3px; padding: 4px 2px; text-align: center; }
        .stats-label { font-size: 6.5px; font-weight: bold; text-transform: uppercase; color: #475467; margin-bottom: 1px; }
        .stats-value { font-size: 10px; font-weight: 800; }
        
        .stats-yellow { background-color: #fffbeb; border-color: #fef08a; }
        .stats-yellow .stats-value { color: #b45309; }
        .stats-green { background-color: #ecfdf3; border-color: #abefc6; }
        .stats-green .stats-value { color: #027a48; }
        .stats-blue { background-color: #eff8ff; border-color: #b2ddff; }
        .stats-blue .stats-value { color: #175cd3; }
        .stats-orange { background-color: #fffaeb; border-color: #fedf89; }
        .stats-orange .stats-value { color: #b54708; }
        .stats-red { background-color: #fef3f2; border-color: #fecdca; }
        .stats-red .stats-value { color: #b42318; }
        .stats-purple { background-color: #f9f5ff; border-color: #e9d7fe; }
        .stats-purple .stats-value { color: #6941c6; }
        .stats-teal { background-color: #f0fdf9; border-color: #99f6e4; }
        .stats-teal .stats-value { color: #0f766e; }
        
        /* Section headers */
        .section-header { background: #f8c300; color: #101828; padding: 4px 6px; font-weight: bold; font-size: 9px; border-radius: 3px 3px 0 0; text-transform: uppercase; margin-top: 6px; }
        
        /* Data table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data-table th { background: #f8c300; color: #101828; padding: 6px 7px; font-weight: bold; text-align: left; font-size: 8.5px; border: 1px solid #344054; }
        .data-table td { padding: 6px 7px; border: 1px solid #d0d5dd; font-size: 8px; vertical-align: middle; }
        .data-table tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 1.5px solid #111c31; }
        
        /* Badges */
        .badge { display: inline-block; padding: 3px 6px; border-radius: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; white-space: nowrap; }
        .badge-fully-dispatched, .badge-completed { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .badge-partial-dispatch, .badge-partial { background: #eff8ff; color: #175cd3; border: 1px solid #b2ddff; }
        .badge-partial-pending { background: #fff8eb; color: #b45309; border: 1px solid #fef08a; }
        .badge-pending { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
        .badge-cancelled { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        
        /* Side by Side Summaries */
        .summary-container-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-cell { width: 49%; vertical-align: top; }
        .summary-spacer { width: 2%; }
        
        .summary-subtable { width: 100%; border-collapse: collapse; border: 1px solid #d0d5dd; }
        .summary-subtable th { background: #f4f5f7; color: #344054; padding: 4px 6px; font-weight: bold; font-size: 7.5px; border: 1px solid #d0d5dd; text-align: left; }
        .summary-subtable td { padding: 4px 6px; border: 1px solid #e4e7ec; font-size: 7.5px; }
        .summary-subtable tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 1.5px solid #344054; }
        
        /* Footer signature */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 15px; border-top: 1px solid #eaecf0; padding-top: 8px; }
        .footer-left { width: 50%; font-size: 7.5px; color: #667085; vertical-align: bottom; }
        .footer-right { width: 50%; text-align: right; font-size: 8.5px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #475467; width: 140px; margin-left: auto; margin-top: 25px; margin-bottom: 3px; }
        
        .text-green { color: #027a48; font-weight: bold; }
        .text-red { color: #b42318; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .page-break { page-break-before: always; }
        .page-break-avoid { page-break-inside: avoid; }
    </style>
</head>
<body>
<div class="page">
    
    <!-- Branding Header -->
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 40px; vertical-align: middle; padding: 0;">
                            @if(file_exists(public_path('logo.png')))
                                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 35px; height: 35px; object-fit: contain;">
                            @endif
                        </td>
                        <td style="vertical-align: middle; padding: 0 0 0 8px;">
                            <div class="brand-title">PENTAPURE</div>
                            <div class="brand-tagline">FOOD &amp; SPICES PVT.LTD.</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-contact-cell">
                <div style="margin-bottom: 1px;">Email: info@pentapure.com</div>
                <div style="margin-bottom: 1px;">Phone: +91 98765 43210</div>
                <div>Web: www.pentapure.com</div>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="title-container">
        <div class="title-line"></div>
        <div class="title">DISPATCH HISTORY REPORT</div>
        <div class="title-line"></div>
    </div>

    <!-- Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <div style="margin-bottom: 4px;"><span class="meta-label">Report ID</span><span class="meta-value">: {{ $reportId }}</span></div>
                <div style="margin-bottom: 4px;"><span class="meta-label">Generated By</span><span class="meta-value">: {{ $userName }}</span></div>
                <div><span class="meta-label">Generated On</span><span class="meta-value">: {{ $generatedOn }}</span></div>
            </td>
            <td style="width: 50%; border-left: 2px solid #e4e7ec; padding-left: 15px;">
                <div style="margin-bottom: 4px;"><span class="meta-label">From Date</span><span class="meta-value">: {{ $fromDate }}</span></div>
                <div><span class="meta-label">To Date</span><span class="meta-value">: {{ $toDate }}</span></div>
            </td>
        </tr>
    </table>

    <!-- Stats Cards -->
    <table class="stats-table">
        <tr>
            <td class="stats-card-cell">
                <div class="stats-card stats-yellow">
                    <div class="stats-label">Total Dispatches</div>
                    <div class="stats-value">{{ $totalRecords }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-green">
                    <div class="stats-label">Fully Dispatched</div>
                    <div class="stats-value">{{ $fullyDispatchedCount ?? 0 }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-blue">
                    <div class="stats-label">Partial Dispatch</div>
                    <div class="stats-value">{{ $partialDispatchCount ?? 0 }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-orange">
                    <div class="stats-label">Pending</div>
                    <div class="stats-value">{{ $pendingCount ?? 0 }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-purple">
                    <div class="stats-label">Total Value</div>
                    <div class="stats-value">Rs. {{ number_format($totalValue) }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-teal">
                    <div class="stats-label">Total Quantity</div>
                    <div class="stats-value">{{ number_format($totalQuantity) }} KG</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Dispatch History Table -->
    <div class="section-header" style="border-radius: 3px 3px 0 0; margin-bottom: 0;">DISPATCH HISTORY</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 9%;">Dispatch ID</th>
                <th style="width: 10%;">Order Date</th>
                <th style="width: 8%;" class="text-center">Due By Days</th>
                <th style="width: 14%;">Customer</th>
                <th style="width: 24%;">Product Details</th>
                <th style="width: 7%; color: #027a48;" class="text-right">Ord. Qty</th>
                <th style="width: 7%; color: #b37400;" class="text-right">Disp. Qty</th>
                <th style="width: 7%; color: #b42318;" class="text-right">Pend. Qty</th>
                <th style="width: 8%;" class="text-right">Revenue</th>
                <th style="width: 6%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $logRow)
                @php
                    $rawStatus = strtoupper(trim(str_replace('_', ' ', $logRow['status'] ?? 'PENDING')));
                    $badgeClass = match($rawStatus) {
                        'FULLY DISPATCHED', 'COMPLETED', 'DONE' => 'badge-fully-dispatched',
                        'PARTIAL DISPATCH', 'PARTIAL' => 'badge-partial-dispatch',
                        'PARTIAL PENDING' => 'badge-partial-pending',
                        'CANCELLED' => 'badge-cancelled',
                        default => 'badge-pending',
                    };
                    $itemCount = count($logRow['items'] ?? []);
                @endphp
                @foreach($logRow['items'] as $itemIdx => $item)
                    <tr>
                        @if($itemIdx === 0)
                            <td rowspan="{{ $itemCount }}" class="text-center" style="vertical-align: middle;"><strong>{{ $logRow['dispatch_id'] }}</strong></td>
                            <td rowspan="{{ $itemCount }}" class="text-center" style="vertical-align: middle;">{{ $logRow['order_date'] }}</td>
                            <td rowspan="{{ $itemCount }}" class="text-center" style="vertical-align: middle; font-weight: bold; color: #344054;">
                                {{ $logRow['due_days_text'] ?? '0 Days' }}
                            </td>
                            <td rowspan="{{ $itemCount }}" style="vertical-align: middle;"><strong>{{ $logRow['customer'] }}</strong></td>
                        @endif
                        <td>
                            <div style="font-weight: bold; color: #101828;">{{ $item['product'] }}</div>
                            <div style="color: #667085; font-size: 7.5px; margin-top: 2px;">
                                <span style="color: #344054;">Grade:</span> {{ $item['grade'] }} | 
                                <span style="color: #344054;">Loc:</span> {{ $item['locations'] }}
                            </div>
                        </td>
                        <td class="text-right text-green"><strong>{{ $item['ordered_qty_formatted'] ?? number_format($item['ordered_qty'] ?? 0) . ' KG' }}</strong></td>
                        <td class="text-right" style="color: #b37400;"><strong>{{ $item['dispatch_qty_formatted'] ?? number_format($item['qty'] ?? 0) . ' KG' }}</strong></td>
                        <td class="text-right" style="color: {{ ($item['pending_qty'] ?? 0) > 0 ? '#b42318' : 'inherit' }};"><strong>{{ $item['pending_qty_formatted'] ?? number_format($item['pending_qty'] ?? 0) . ' KG' }}</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($item['amount'], 2) }}</strong></td>
                        @if($itemIdx === 0)
                            <td rowspan="{{ $itemCount }}" class="text-center" style="vertical-align: middle;">
                                <span class="badge {{ $badgeClass }}">
                                    {{ $rawStatus }}
                                </span>
                            </td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #667085;">No dispatch history found for the selected filters.</td>
                </tr>
            @endforelse
            @if(count($rows) > 0)
                <tr class="total-row">
                    <td colspan="5">TOTAL</td>
                    <td class="text-right text-green">{{ number_format($totalOrderedQty ?? 0) }} KG</td>
                    <td class="text-right" style="color: #b37400;">{{ number_format($totalQuantity) }} KG</td>
                    <td class="text-right" style="color: {{ ($totalPendingQty ?? 0) > 0 ? '#b42318' : 'inherit' }};">{{ number_format($totalPendingQty ?? 0) }} KG</td>
                    <td class="text-right">Rs. {{ number_format($totalValue, 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="page-break-avoid">
        <!-- Grouped Summaries -->
        <table class="summary-container-table">
            <tr>
                <!-- Customer Summary -->
                <td class="summary-cell">
                    <div class="section-header" style="margin-top: 0;">CUSTOMER-WISE SUMMARY</div>
                    <table class="summary-subtable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th style="width: 30%;" class="text-center">Dispatch Count</th>
                                <th style="width: 35%;" class="text-right">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $cTotalQty = 0; $cTotalCount = 0; @endphp
                            @forelse($customerSummary as $cs)
                                @php $cTotalQty += $cs['qty']; $cTotalCount += $cs['count']; @endphp
                                <tr>
                                    <td><strong>{{ $cs['customer'] }}</strong></td>
                                    <td class="text-center">{{ $cs['count'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($cs['qty']) }} KG</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center" style="color: #667085;">No summary data</td></tr>
                            @endforelse
                            @if(count($customerSummary) > 0)
                                <tr class="total-row">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $cTotalCount }}</td>
                                    <td class="text-right">{{ number_format($cTotalQty) }} KG</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
                
                <td class="summary-spacer"></td>
                
                <!-- Product Summary -->
                <td class="summary-cell">
                    <div class="section-header" style="margin-top: 0;">PRODUCT-WISE SUMMARY</div>
                    <table class="summary-subtable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width: 30%;" class="text-center">Dispatch Count</th>
                                <th style="width: 35%;" class="text-right">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $pTotalQty = 0; $pTotalCount = 0; @endphp
                            @forelse($productSummary as $ps)
                                @php $pTotalQty += $ps['qty']; $pTotalCount += $ps['count']; @endphp
                                <tr>
                                    <td><strong>{{ $ps['product'] }}</strong></td>
                                    <td class="text-center">{{ $ps['count'] }}</td>
                                    <td class="text-right"><strong>{{ number_format($ps['qty']) }} KG</strong></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center" style="color: #667085;">No summary data</td></tr>
                            @endforelse
                            @if(count($productSummary) > 0)
                                <tr class="total-row">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $pTotalCount }}</td>
                                    <td class="text-right">{{ number_format($pTotalQty) }} KG</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Signatures and footer -->
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Generated By: {{ $userName }}<br>
                    Generated On: {{ $generatedOn }}<br>
                    Report ID: {{ $reportId }}
                </td>
                <td class="footer-right">
                    <div class="signature-line"></div>
                    <strong>Authorized Signature</strong><br>
                    PentaPure ERP System
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>