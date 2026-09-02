<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $dispatchNo }} - Dispatch Note</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #101828;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .page { width: 100%; position: relative; }
        
        /* Header & Logo */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border-bottom: 2px solid #f8c300; padding-bottom: 6px; }
        .header-logo-cell { width: 60%; vertical-align: middle; }
        .header-contact-cell { width: 40%; text-align: right; vertical-align: middle; font-size: 9px; color: #475467; }
        .brand-title { font-size: 20px; font-weight: 800; color: #101828; letter-spacing: 0.5px; }
        .brand-tagline { font-size: 9px; color: #d88a00; font-weight: 700; letter-spacing: 1px; }
        
        .title { text-align: center; font-size: 15px; font-weight: 800; letter-spacing: 1px; color: #101828; margin: 4px 0 10px 0; }
        
        /* Top Metadata Grid */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; background-color: #fcfcfd; border: 1px solid #eaecf0; border-radius: 4px; }
        .meta-table td { padding: 6px 10px; vertical-align: middle; font-size: 9.5px; }
        .meta-label { font-weight: bold; color: #475467; display: inline-block; width: 85px; }
        .meta-value { color: #101828; }
        
        .badge { display: inline-block; padding: 2px 6px; font-size: 9px; font-weight: 700; border-radius: 4px; text-transform: uppercase; }
        .badge-dispatched { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .badge-pending { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
        
        /* Details Grid: Customer & Transport */
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .details-cell { width: 49%; vertical-align: top; }
        .details-spacer { width: 2%; }
        
        .section-header { background: #f8c300; color: #101828; padding: 5px 8px; font-weight: bold; font-size: 10.5px; border-radius: 4px 4px 0 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-box { border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 4px 4px; padding: 6px 8px; min-height: 85px; }
        .section-box table { width: 100%; border-collapse: collapse; }
        .section-box td { padding: 3px 0; vertical-align: top; }
        .section-box td.lbl { width: 110px; font-weight: bold; color: #475467; }
        .section-box td.val { color: #101828; }
        
        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .items-table th { background: #f8c300; color: #101828; padding: 6px 8px; font-weight: bold; text-align: left; font-size: 9.5px; border: 1px solid #344054; }
        .items-table td { padding: 6px 8px; border: 1px solid #d0d5dd; font-size: 9.5px; }
        .items-table tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 2px solid #f8c300; }
        
        /* Footer signatures */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 15px; border-top: 1px solid #eaecf0; padding-top: 10px; }
        .footer-left { width: 50%; font-size: 8px; color: #667085; vertical-align: bottom; }
        .footer-right { width: 50%; text-align: right; font-size: 10px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #475467; width: 160px; margin-left: auto; margin-top: 25px; margin-bottom: 4px; }
        
        .text-green { color: #027a48; font-weight: bold; }
        .text-red { color: #b54708; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
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
                        <td style="width: 50px; vertical-align: middle; padding: 0;">
                            @if(file_exists(public_path('logo.png')))
                                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 45px; height: 45px; object-fit: contain;">
                            @endif
                        </td>
                        <td style="vertical-align: middle; padding: 0 0 0 8px;">
                            <div class="brand-title">PentaPure</div>
                            <div class="brand-tagline">FOOD &amp; SPICES PVT.LTD.</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-contact-cell">
                <div style="margin-bottom: 1px;">Phone: +91 98765 43210</div>
                <div style="margin-bottom: 1px;">Email: info@pentapure.com</div>
                <div>Web: www.pentapure.com</div>
            </td>
        </tr>
    </table>

    <div class="title">DISPATCH NOTE</div>

    <!-- Top Metadata Grid: Order Date, Order No, Dispatch Date, Dispatch No, Dispatch By, Order By -->
    <table class="meta-table">
        <tr>
            <td style="width: 33.33%;">
                <div style="margin-bottom: 4px;"><span class="meta-label">Order Date</span><span class="meta-value">: {{ $orderDate }}</span></div>
                <div><span class="meta-label">Order No.</span><span class="meta-value">: <strong>{{ $orderNo }}</strong></span></div>
            </td>
            <td style="width: 33.33%;">
                <div style="margin-bottom: 4px;"><span class="meta-label">Dispatch Date</span><span class="meta-value">: {{ $dispatchDate }}</span></div>
                <div><span class="meta-label">Dispatch No.</span><span class="meta-value">: <strong>{{ $dispatchNo }}</strong></span></div>
            </td>
            <td style="width: 33.33%;">
                <div style="margin-bottom: 4px;"><span class="meta-label">Dispatch By</span><span class="meta-value">: <strong>{{ $generatedBy }}</strong></span></div>
                <div><span class="meta-label">Order By</span><span class="meta-value">: <strong>{{ $orderGeneratedBy }}</strong></span></div>
            </td>
        </tr>
    </table>

    <!-- Customer & Transport Details -->
    <table class="details-table">
        <tr>
            <!-- Customer Box -->
            <td class="details-cell">
                <div class="section-header">CUSTOMER DETAILS</div>
                <div class="section-box">
                    <table>
                        <tr><td class="lbl">Customer Name</td><td class="val">: <strong>{{ $company->name }}</strong></td></tr>
                        <tr><td class="lbl">Address</td><td class="val">: {{ $company->address ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Pincode</td><td class="val">: {{ $company->pincode ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">GST Number</td><td class="val">: {{ $company->gst ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Customer Number</td><td class="val">: {{ $company->contact ?? 'N/A' }}</td></tr>
                    </table>
                </div>
                
                <!-- Dispatch Type Box -->
                <div style="margin-top: 8px; padding: 6px 8px; border: 1px solid #d0d5dd; border-radius: 4px; background: #fff;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 95px; font-weight: bold; color: #475467; font-size: 9.5px; vertical-align: middle;">Dispatch Type</td>
                            <td style="font-size: 9.5px; color: #101828;">: <span class="badge {{ $totalPendingQty <= 0 ? 'badge-dispatched' : 'badge-pending' }}">{{ $dispatchType }}</span></td>
                        </tr>
                    </table>
                </div>
            </td>
            
            <td class="details-spacer"></td>
            
            <!-- Transport Box (Without LR Number) -->
            <td class="details-cell">
                <div class="section-header">TRANSPORT DETAILS</div>
                <div class="section-box">
                    <table>
                        <tr><td class="lbl">Transporter</td><td class="val">: <strong>{{ $transporter->name ?? 'N/A' }}</strong></td></tr>
                        <tr><td class="lbl">Contact Number</td><td class="val">: {{ $transporter->contact ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Vehicle Number</td><td class="val">: {{ $transporter->vehicles ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Dispatch Date</td><td class="val">: {{ $dispatchDate }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Full-Width Remarks / Special Instructions Box with Yellow Header Bar -->
    @if(!empty($remarks))
    <div style="margin-bottom: 10px;">
        <div class="section-header" style="border-radius: 4px 4px 0 0; margin-bottom: 0;">REMARKS / SPECIAL INSTRUCTIONS</div>
        <div class="section-box" style="border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 4px 4px; padding: 8px 10px; min-height: auto; font-size: 9.5px; color: #101828; line-height: 1.4; background: #fff;">
            {!! nl2br(e($remarks)) !!}
        </div>
    </div>
    @endif

    <!-- Item Details -->
    <div class="section-header" style="border-radius: 4px 4px 0 0; margin-bottom: 0;">ITEM DETAILS</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No.</th>
                <th style="width: 25%;">Product Name</th>
                <th style="width: 11%;" class="text-right">Ordered Qty</th>
                <th style="width: 12%;" class="text-right">Prev. Dispatched Qty.</th>
                <th style="width: 12%;" class="text-right">Current Dispatch Qty.</th>
                <th style="width: 11%;" class="text-right">Rate (&#8377;)</th>
                <th style="width: 12%;" class="text-right">Amount (&#8377;)</th>
                <th style="width: 12%;" class="text-right">Pending Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $di)
                @php
                    $orderItem = $di->orderItem;
                    $pendingQty = max(0, $orderItem->quantity - $orderItem->dispatched_qty);
                    $prevDispatched = max(0, (float)$orderItem->dispatched_qty - (float)$di->quantity);
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $orderItem->product ? $orderItem->product->formatName($orderItem->grade) : 'Unknown' }}</strong>
                    </td>
                    <td class="text-right text-green">{{ number_format($orderItem->quantity) }} KG</td>
                    <td class="text-right">{{ number_format($prevDispatched) }} KG</td>
                    <td class="text-right"><strong>{{ number_format($di->quantity) }} KG</strong></td>
                    <td class="text-right">
                        @if($orderItem->price > 0)
                            &#8377;{{ number_format($orderItem->price, 2) }}
                        @else
                            <span class="text-red">&#8377;0.00</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($orderItem->price > 0)
                            &#8377;{{ number_format($orderItem->price * $di->quantity, 2) }}
                        @else
                            <span class="text-red">&#8377;0.00</span>
                        @endif
                    </td>
                    <td class="text-right text-red">{{ number_format($pendingQty) }} KG</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td class="text-right text-green">{{ number_format($totalOrderedQty) }} KG</td>
                <td class="text-right">{{ number_format($totalPrevDispatchedQty) }} KG</td>
                <td class="text-right"><strong>{{ number_format($totalDispatchedQty) }} KG</strong></td>
                <td class="text-right"></td>
                <td class="text-right">&#8377;{{ number_format($totalAmount, 2) }}</td>
                <td class="text-right text-red">{{ number_format($totalPendingQty) }} KG</td>
            </tr>
        </tbody>
    </table>

    <!-- Use a wrapper to prevent orphan/broken page layouts on signatures and summary boxes -->
    <div class="page-break-avoid">
        
        <!-- Dispatch Timeline for Order -->
        @if(count($dispatchHistory) > 1)
        <div class="section-header" style="border-radius: 4px 4px 0 0; margin-bottom: 0; margin-top: 10px;">DISPATCH TIMELINE FOR THIS ORDER</div>
        <table class="items-table" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 10%;" class="text-center">Round</th>
                    <th style="width: 20%;">Date & Time</th>
                    <th style="width: 15%;">Dispatch ID</th>
                    <th style="width: 55%;">Items Dispatched</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dispatchHistory as $historyLog)
                    <tr style="{{ $historyLog->id === $log->id ? 'background-color: #f6fef9;' : '' }}">
                        <td class="text-center">
                            <strong>#{{ $loop->iteration }}</strong>
                            @if($historyLog->id === $log->id)
                                <br><span style="color:#027a48; font-size: 8px;">(Current)</span>
                            @endif
                        </td>
                        <td>{{ $historyLog->created_at->format('d-M-Y h:i A') }}</td>
                        <td><strong>DSP-{{ str_pad($historyLog->id, 4, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>
                            @foreach($historyLog->dispatchItems as $hItem)
                                <div style="margin-bottom: 2px;">
                                    &bull; {{ $hItem->orderItem->product ? $hItem->orderItem->product->formatName($hItem->orderItem->grade) : 'Unknown' }}: 
                                    <strong style="color: #175cd3;">{{ number_format($hItem->quantity) }} KG</strong>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Signatures and footer -->
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Generated By: {{ $generatedBy }}<br>
                    Generated On: {{ $generatedOn }}<br>
                    Dispatch ID: {{ $log->id }}
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