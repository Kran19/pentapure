<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PentaPure - Dispatch History Report</title>
    <style>
        @page { margin: 15px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #101828; font-size: 9px; line-height: 1.35; text-transform: uppercase; }
        .page { border: 1px solid #1f2937; padding: 15px 20px; }
        
        /* Header styling */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border-bottom: 2px solid #e4e7ec; padding-bottom: 4px; }
        .header-logo-cell { width: 60%; vertical-align: middle; }
        .header-contact-cell { width: 40%; text-align: right; vertical-align: middle; font-size: 8.5px; color: #475467; }
        .brand-title { font-size: 20px; font-weight: 800; color: #101828; line-height: 1.1; }
        .brand-tagline { font-size: 9px; color: #f8c300; font-weight: bold; margin-top: 1px; }
        
        .title-container { text-align: center; margin: 10px 0; }
        .title-line { display: inline-block; width: 60px; border-top: 2px solid #f8c300; vertical-align: middle; margin: 0 10px; }
        .title { display: inline-block; font-size: 16px; font-weight: 800; letter-spacing: 1.5px; color: #101828; vertical-align: middle; }
        
        /* Top Metadata Grid */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1px solid #d0d5dd; }
        .meta-table td { padding: 5px 8px; border: 1px solid #e4e7ec; vertical-align: middle; font-size: 8.5px; }
        .meta-label { font-weight: bold; color: #344054; display: inline-block; width: 95px; }
        .meta-value { color: #101828; }
        
        /* Stats cards row */
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .stats-card-cell { width: 16.66%; padding: 0 4px; }
        .stats-card-cell:first-child { padding-left: 0; }
        .stats-card-cell:last-child { padding-right: 0; }
        .stats-card { border-radius: 4px; padding: 6px; text-align: center; border: 1px solid #d0d5dd; }
        
        /* Color variations for stats */
        .stats-yellow { border-color: #fdec98; background: #fffdf5; color: #b37400; }
        .stats-green { border-color: #abefc6; background: #f6fef9; color: #027a48; }
        .stats-orange { border-color: #fedf89; background: #fffdf5; color: #b54708; }
        .stats-red { border-color: #fecdca; background: #fffbfa; color: #b42318; }
        .stats-purple { border-color: #d6bbfb; background: #fcfaff; color: #5f259f; }
        .stats-teal { border-color: #99ffd6; background: #f2fff9; color: #007a4b; }
        
        .stats-label { font-size: 7px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        .stats-value { font-size: 11px; font-weight: 800; }
        
        .section-header { background: #f8c300; color: #101828; padding: 4px 8px; font-weight: bold; font-size: 10px; border-radius: 3px 3px 0 0; text-transform: uppercase; margin-top: 8px; }
        
        /* Main table styling */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-table th { background: #f8c300; color: #101828; padding: 5px 6px; font-weight: bold; text-align: left; font-size: 8.5px; border: 1px solid #344054; }
        .data-table td { padding: 5px 6px; border: 1px solid #d0d5dd; font-size: 8px; vertical-align: middle; }
        .data-table tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 1.5px solid #111c31; }
        
        /* Badges */
        .badge { display: inline-block; padding: 1px 4px; border-radius: 2px; font-weight: bold; font-size: 7.5px; text-transform: uppercase; }
        .badge-completed { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .badge-pending { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
        .badge-cancelled { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        
        /* Side by Side Summaries */
        .summary-container-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary-cell { width: 49%; vertical-align: top; }
        .summary-spacer { width: 2%; }
        
        .summary-subtable { width: 100%; border-collapse: collapse; border: 1px solid #d0d5dd; }
        .summary-subtable th { background: #f4f5f7; color: #344054; padding: 4px 6px; font-weight: bold; font-size: 8px; border: 1px solid #d0d5dd; text-align: left; }
        .summary-subtable td { padding: 4px 6px; border: 1px solid #e4e7ec; font-size: 8px; }
        .summary-subtable tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 1.5px solid #344054; }
        
        /* Note block */
        .note-box { border: 1px solid #d0d5dd; border-radius: 4px; padding: 8px; background: #f9fafb; margin-top: 10px; line-height: 1.4; color: #344054; }
        .note-title { font-weight: bold; color: #101828; margin-bottom: 3px; }
        .note-list { margin: 0; padding-left: 12px; }
        
        /* Footer signature */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 20px; border-top: 1px solid #eaecf0; padding-top: 8px; }
        .footer-left { width: 50%; font-size: 8px; color: #667085; vertical-align: bottom; }
        .footer-right { width: 50%; text-align: right; font-size: 9px; vertical-align: bottom; }
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
                            @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
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
                <div style="margin-bottom: 1px;">✉️ info@pentapure.com</div>
                <div style="margin-bottom: 1px;">📞 +91 98765 43210</div>
                <div>🌐 www.pentapure.com</div>
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
                    <div class="stats-label">Completed</div>
                    <div class="stats-value">{{ $completedCount }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-orange">
                    <div class="stats-label">Pending</div>
                    <div class="stats-value">{{ $pendingCount }}</div>
                </div>
            </td>
            <td class="stats-card-cell">
                <div class="stats-card stats-red">
                    <div class="stats-label">Cancelled</div>
                    <div class="stats-value">{{ $cancelledCount }}</div>
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
    <div class="section-header" style="border-radius: 3px 3px 0 0; margin-bottom: 0;">📋 Dispatch History</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">Dispatch ID</th>
                <th style="width: 8%;">Order ID</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 12%;">Customer</th>
                <th style="width: 22%;">Product Details</th>
                <th style="width: 8%; color: #027a48;" class="text-right">Ord. Qty</th>
                <th style="width: 8%; color: #b37400;" class="text-right">Disp. Qty</th>
                <th style="width: 8%; color: #b42318;" class="text-right">Pend. Qty</th>
                <th style="width: 9%;" class="text-right">Revenue</th>
                <th style="width: 8%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $row)
                <tr>
                    <td>{{ $row['dispatch_id'] }}</td>
                    <td>{{ $row['order_id'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td>
                        <div style="font-weight: bold;">{{ $row['product'] }}</div>
                        <div style="color: #667085; font-size: 7px; margin-top: 2px;">
                            <span style="color: #344054;">Grade:</span> {{ $row['grade'] }} | 
                            <span style="color: #344054;">Loc:</span> {{ $row['locations'] }}
                        </div>
                    </td>
                    <td class="text-right text-green"><strong>{{ $row['ordered_qty_formatted'] ?? number_format($row['ordered_qty'] ?? 0) . ' KG' }}</strong></td>
                    <td class="text-right" style="color: #b37400;"><strong>{{ $row['dispatch_qty_formatted'] ?? number_format($row['qty'] ?? 0) . ' KG' }}</strong></td>
                    <td class="text-right" style="color: {{ ($row['pending_qty'] ?? 0) > 0 ? '#b42318' : 'inherit' }};"><strong>{{ $row['pending_qty_formatted'] ?? number_format($row['pending_qty'] ?? 0) . ' KG' }}</strong></td>
                    <td class="text-right"><strong>Rs. {{ number_format($row['amount'], 2) }}</strong></td>
                    <td class="text-center">
                        <span class="badge {{ $row['status'] === 'COMPLETED' ? 'badge-completed' : ($row['status'] === 'CANCELLED' ? 'badge-cancelled' : 'badge-pending') }}">
                            {{ $row['status'] }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px; color: #667085;">No dispatch history found for the selected filters.</td>
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
                    <div class="section-header" style="margin-top: 0;">👥 Customer-Wise Summary</div>
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
                                    <td class="text-right text-green">{{ number_format($cs['qty']) }} KG</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No data available</td></tr>
                            @endforelse
                            @if(count($customerSummary) > 0)
                                <tr class="total-row">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $cTotalCount }}</td>
                                    <td class="text-right text-green">{{ number_format($cTotalQty) }} KG</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
                
                <td class="summary-spacer"></td>
                
                <!-- Product Summary -->
                <td class="summary-cell">
                    <div class="section-header" style="margin-top: 0;">📦 Product-Wise Summary</div>
                    <table class="summary-subtable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width: 30%;" class="text-center">Dispatch Count</th>
                                <th style="width: 35%;" class="text-right">Total Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $pTotalQty = 0; $pTotalCount = 0; @endphp
                            @forelse($productSummary as $ps)
                                @php $pTotalQty += $ps['qty']; $pTotalCount += $ps['count']; @endphp
                                <tr>
                                    <td><strong>{{ $ps['product'] }}</strong></td>
                                    <td class="text-center">{{ $ps['count'] }}</td>
                                    <td class="text-right text-green">{{ number_format($ps['qty']) }} KG</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No data available</td></tr>
                            @endforelse
                            @if(count($productSummary) > 0)
                                <tr class="total-row">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $pTotalCount }}</td>
                                    <td class="text-right text-green">{{ number_format($pTotalQty) }} KG</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Note block -->
        <div class="note-box">
            <div class="note-title">NOTE:</div>
            <ul class="note-list">
                <li>Quantity includes both full and partial dispatches.</li>
                <li>Cancelled dispatches/orders are excluded from total quantity and total value.</li>
                <li>Amount is calculated based on dispatched quantity and sales rate.</li>
            </ul>
        </div>

        <!-- Footer Signatures -->
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

    <!-- LR Copy Section: Placed at the very end of the document, below, if available -->
    @if(count($lrCopies) > 0)
        <div class="page-break"></div>
        <div class="section-header" style="text-align: center; font-size: 12px; padding: 6px 12px;">📦 ATTACHED LORRY RECEIPTS (LR COPIES)</div>
        <div style="margin-top: 15px;">
            @foreach($lrCopies as $lr)
                @if(extension_loaded('gd') && file_exists(public_path($lr['path'])))
                    <div class="page-break-avoid" style="margin-bottom: 25px; text-align: center; border: 1px solid #d0d5dd; border-radius: 6px; padding: 10px; background: #f9fafb;">
                        <div style="font-size: 10px; font-weight: bold; color: #101828; margin-bottom: 8px; border-bottom: 1px solid #e4e7ec; padding-bottom: 4px;">
                            Dispatch: {{ $lr['dispatch_id'] }} | Order: {{ $lr['order_id'] }} | Customer: {{ $lr['customer'] }}
                        </div>
                        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path($lr['path']))) }}" style="max-height: 400px; max-width: 100%; border: 1px solid #eaecf0; border-radius: 4px; padding: 4px; object-fit: contain;">
                    </div>
                @endif
            @endforeach
        </div>
    @endif

</div>
</body>
</html>
