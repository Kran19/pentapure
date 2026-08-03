<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PentaPure - Sales Order {{ $orderNo }}</title>
    <style>
        @page { margin: 15px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #101828; font-size: 10px; line-height: 1.35; text-transform: uppercase; }
        .page { border: 1px solid #1f2937; padding: 15px 20px; position: relative; }
        
        /* Header styling */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 2px solid #e4e7ec; padding-bottom: 5px; }
        .header-logo-cell { width: 60%; vertical-align: middle; }
        .header-contact-cell { width: 40%; text-align: right; vertical-align: middle; font-size: 9px; color: #475467; }
        .brand-title { font-size: 22px; font-weight: 800; color: #101828; line-height: 1.1; }
        .brand-tagline { font-size: 10px; color: #f8c300; font-weight: bold; margin-top: 1px; }
        
        .title { text-align: center; margin: 8px 0; font-size: 18px; font-weight: 800; letter-spacing: 2px; color: #101828; }
        
        /* Top Metadata Grid */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #d0d5dd; }
        .meta-table td { padding: 6px 10px; border: 1px solid #e4e7ec; width: 33.33%; vertical-align: middle; }
        .meta-label { font-weight: bold; color: #344054; display: inline-block; width: 105px; }
        .meta-value { color: #101828; }
        
        /* Badges */
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .badge-dispatched { background: #ecfdf3; color: #027a48; border: 1px solid #abefc6; }
        .badge-pending { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
        
        /* Details Grid: Customer & Transport */
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .details-cell { width: 49%; vertical-align: top; }
        .details-spacer { width: 2%; }
        
        .section-header { background: #f8c300; color: #101828; padding: 4px 8px; font-weight: bold; font-size: 11px; border-radius: 4px 4px 0 0; text-transform: uppercase; }
        .section-box { border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 4px 4px; padding: 6px 8px; min-height: 90px; }
        .section-box table { width: 100%; border-collapse: collapse; }
        .section-box td { padding: 3px 0; vertical-align: top; }
        .section-box td.lbl { width: 100px; font-weight: bold; color: #475467; }
        .section-box td.val { color: #101828; }
        
        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .items-table th { background: #f8c300; color: #101828; padding: 6px 8px; font-weight: bold; text-align: left; font-size: 10px; border: 1px solid #344054; }
        .items-table td { padding: 6px 8px; border: 1px solid #d0d5dd; font-size: 10px; }
        .items-table tr.total-row td { font-weight: bold; background: #f9fafb; border-top: 2px solid #f8c300; }
        
        /* Summary & LR Block */
        .summary-lr-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary-cell { width: 49%; vertical-align: top; }
        
        .lr-box { border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 4px 4px; padding: 6px; text-align: center; min-height: 110px; background: #f9fafb; }
        .lr-img { max-height: 95px; max-width: 100%; object-fit: contain; border: 1px solid #eaecf0; border-radius: 4px; }
        .lr-empty { padding-top: 30px; color: #667085; font-style: italic; }
        
        /* Remarks Section */
        .remarks-header { background: #f8c300; color: #101828; padding: 4px 8px; font-weight: bold; font-size: 10px; border-radius: 4px 4px 0 0; text-transform: uppercase; }
        .remarks-box { border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 4px 4px; padding: 8px 10px; min-height: 45px; color: #344054; font-size: 10px; background: #fff; line-height: 1.4; }
        
        /* Footer signatures */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 20px; border-top: 1px solid #eaecf0; padding-top: 10px; }
        .footer-left { width: 50%; font-size: 8px; color: #667085; vertical-align: bottom; }
        .footer-right { width: 50%; text-align: right; font-size: 10px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #475467; width: 160px; margin-left: auto; margin-top: 30px; margin-bottom: 4px; }
        
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
                <div style="margin-bottom: 1px;">📞 +91 98765 43210</div>
                <div style="margin-bottom: 1px;">✉️ info@pentapure.com</div>
                <div>🌐 www.pentapure.com</div>
            </td>
        </tr>
    </table>

    <div class="title">SALES ORDER</div>

    <!-- Top Metadata Grid -->
    <table class="meta-table">
        <tr>
            <td>
                <div style="margin-bottom: 4px;"><span class="meta-label">Order No.</span><span class="meta-value">: <strong>{{ $orderNo }}</strong></span></div>
            </td>
            <td>
                <div style="margin-bottom: 4px;"><span class="meta-label">Order Date</span><span class="meta-value">: {{ $orderDate }}</span></div>
                <div><span class="meta-label">Generated On</span><span class="meta-value">: {{ $generatedOn }}</span></div>
            </td>
            <td>
                <div style="margin-bottom: 4px;"><span class="meta-label">Status</span><span class="meta-value">: <span class="badge {{ $status == 'OPEN' ? 'badge-pending' : 'badge-dispatched' }}">{{ $status }}</span></span></div>
                <div style="margin-bottom: 4px;"><span class="meta-label">Generated By</span><span class="meta-value">: {{ $generatedBy }}</span></div>
            </td>
        </tr>
    </table>

    <!-- Customer & Transport Details -->
    <table class="details-table">
        <tr>
            <!-- Customer Box -->
            <td class="details-cell">
                <div class="section-header">👤 Customer Details</div>
                <div class="section-box">
                    <table>
                        <tr><td class="lbl">Customer Name</td><td class="val">: <strong>{{ $company->name }}</strong></td></tr>
                        <tr><td class="lbl">Mobile Number</td><td class="val">: {{ $company->contact ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Address</td><td class="val">: {{ $company->address ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">GST Number</td><td class="val">: {{ $company->gst ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </td>
            
            <td class="details-spacer"></td>
            
            <!-- Transport Box -->
            <td class="details-cell">
                <div class="section-header">🚚 Transport Details</div>
                <div class="section-box">
                    <table>
                        <tr><td class="lbl">Transporter Name</td><td class="val">: <strong>{{ $transporter->name ?? 'N/A' }}</strong></td></tr>
                        <tr><td class="lbl">Contact Number</td><td class="val">: {{ $transporter->contact ?? 'N/A' }}</td></tr>
                        <tr><td class="lbl">Vehicle Number</td><td class="val">: {{ $transporter->vehicles ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Item Details -->
    <div class="section-header" style="border-radius: 4px 4px 0 0; margin-bottom: 0;">📦 Item Details</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No.</th>
                <th style="width: 45%;">Product Name</th>
                <th style="width: 15%;" class="text-right">Qty</th>
                <th style="width: 15%;" class="text-right">Rate (₹)</th>
                <th style="width: 20%;" class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $item->product ? $item->product->formatName($item->grade) : 'Unknown' }}</strong>
                    </td>
                    <td class="text-right text-green">{{ number_format($item->quantity) }} KG</td>
                    <td class="text-right">
                        @if($item->price > 0)
                            ₹{{ number_format($item->price, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        <strong>
                            @if($item->price > 0)
                                ₹{{ number_format($item->price * $item->quantity, 2) }}
                            @else
                                -
                            @endif
                        </strong>
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right">TOTAL</td>
                <td class="text-right text-green">{{ number_format($totalOrderedQty) }} KG</td>
                <td class="text-right"></td>
                <td class="text-right text-red">₹{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Use a wrapper to prevent orphan/broken page layouts on signatures and summary boxes -->
    <div class="page-break-avoid">
        
        <!-- Dispatch Timeline for Order -->
        @if(isset($dispatchHistory) && count($dispatchHistory) > 1)
        <div class="section-header" style="border-radius: 4px 4px 0 0; margin-bottom: 0; margin-top: 10px;">⏱️ Dispatch Timeline for this Order</div>
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
                    <tr style="{{ (isset($log) && $historyLog->id === $log->id) ? 'background-color: #f6fef9;' : '' }}">
                        <td class="text-center">
                            <strong>#{{ $loop->iteration }}</strong>
                            @if(isset($log) && $historyLog->id === $log->id)
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



        <!-- Remarks -->
        <div class="remarks-header">💬 Remarks / Special Instructions</div>
        <div class="remarks-box">
            {!! nl2br(e($remarks)) !!}
        </div>

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
