<!DOCTYPE html>
<html>
<head>
    <title>Dispatch Activity Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; text-transform: uppercase; background-color: #ffffff; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #f8c300; padding-bottom: 10px; }
        .header .brand-title { font-size: 24px; font-weight: bold; color: #101828; margin: 0; }
        .header .tagline { font-size: 14px; font-weight: bold; color: #101828; margin-top: 5px; }
        .header .report-title { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 14px; font-weight: bold; color: #344054; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f8c300; color: #101828; text-align: left; padding: 8px; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; vertical-align: top; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-done { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-partial { background: #e2e3e5; color: #383d41; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
        .items-list { margin: 0; padding-left: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 10px;">
            <tr>
                <td style="width: 20%; text-align: left; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: middle; border: none; background: transparent;">
                    <div class="brand-title">PentaPure</div>
                    <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: middle; border: none; background: transparent;">
                    @if(extension_loaded('gd') && file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="report-title">Dispatch Order Activity Report</div>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer / Company</th>
                <th style="width: 35%;">Items & Details</th>
                <th>Status</th>
                <th>Dispatch Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                @php
                    $label = $order->dispatch_status;
                    if ($order->dispatch_status === 'DONE') {
                        $label = $order->dispatch_logs_count > 1 ? 'Partial Dispatch' : 'Fully Dispatch';
                    } elseif ($order->dispatch_status === 'PARTIAL') {
                        $label = 'Partial Pending';
                    } elseif ($order->dispatch_status === 'PENDING') {
                        $label = 'Pending';
                    }
                @endphp
                <tr>
                    <td><strong>#{{ $order->id }}</strong></td>
                    <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        <strong>{{ $order->company?->name ?? 'N/A' }}</strong><br>
                        <span style="font-size:10px; color:#666;">By: {{ $order->creator?->name ?? 'System' }}</span>
                    </td>
                    <td>
                        <ul class="items-list" style="margin: 0; padding-left: 15px; list-style-type: none; padding: 0;">
                        @foreach($order->items as $item)
                            <li style="margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                - {{ $item->product ? $item->product->formatName($item->grade) : 'Unknown' }}: <strong>{{ $item->quantity }} {{ $item->product?->unit }}</strong>
                                @if($order->dispatch_status === 'PARTIAL' || ($order->dispatch_status === 'DONE' && $order->dispatch_logs_count > 1))
                                    <div style="font-size:10px; color:#555; padding-left: 10px;">
                                        Dispatched: {{ $item->dispatched_qty ?? 0 }} {{ $item->product?->unit }} |
                                        Pending: {{ max(0, $item->quantity - ($item->dispatched_qty ?? 0)) }} {{ $item->product?->unit }}
                                    </div>
                                @endif
                            </li>
                        @endforeach
                        </ul>
                        @if($order->notes)
                            <div style="font-size:10px; font-style:italic; margin-top:5px; color:#666;">Note: {{ $order->notes }}</div>
                        @endif
                    </td>
                    <td>{{ $label }}</td>
                    <td>
                        @if($order->dispatchLog)
                            <div>Transporter: {{ $order->transporter?->name ?? 'N/A' }}</div>
                            <div style="font-size:10px; color:#666;">Dispatched by: {{ $order->dispatchLog->user?->name }}</div>
                            @if($order->dispatchLog->driver_no)
                                <div style="font-size:10px; color:#666;">Driver: {{ $order->dispatchLog->driver_no }}</div>
                            @endif
                            @if($order->dispatchLog->lr_no)
                                <div style="font-size:10px; color:#666;">LR No: {{ $order->dispatchLog->lr_no }}</div>
                            @endif
                        @else
                            <span style="color:#888;">Not dispatched yet</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by PentaPure ERP System on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
