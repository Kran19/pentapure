<!DOCTYPE html>
<html>
<head>
    <title>Dispatch Activity Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; text-transform: uppercase; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1a4a7c; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; }
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
        <h1>Pentapure Foods</h1>
        <p>Dispatch Order Activity Report</p>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product (Grade)</th>
                <th>Unit</th>
                <th style="text-align:right;">Order Qty</th>
                <th style="text-align:right;">Dispatch Rate</th>
                <th style="text-align:right;">Total Product Revenue</th>
                <th style="text-align:right;">Pending Qty</th>
                <th> </th>
            </tr>
        </thead>

        <tbody>
            @foreach($orders as $order)
                @php
                    $orderTotalRevenue = 0;
                @endphp

                <tr>
                    <td colspan="7" style="background:#fafafa;">
                        <strong>#{{ $order->id }}</strong> | {{ $order->created_at->format('d/m/Y') }} | {{ $order->company?->name ?? 'N/A' }}
                    </td>
                </tr>

                @foreach($order->items as $item)
                    @php
                        $qty = (float) ($item->quantity ?? 0);
                        $sent = (float) ($item->dispatched_qty ?? 0);
                        $pending = max(0, $qty - $sent);

                        // dispatch rate/unit: use order item rate if present, otherwise fallback to 0
                        $rate = (float) ($item->rate ?? $item->dispatch_rate ?? 0);
                        $lineRevenue = $qty * $rate;
                        $orderTotalRevenue += $lineRevenue;
                    @endphp
                    <tr>
                        <td>{{ $item->product?->name }} ({{ $item->grade }})</td>
                        <td>{{ $item->product?->unit ?? '' }}</td>
                        <td style="text-align:right;">{{ number_format($qty, 2) }}</td>
                        <td style="text-align:right;">{{ number_format($rate, 2) }}</td>
                        <td style="text-align:right;">₹{{ number_format($lineRevenue, 2) }}</td>
                        <td style="text-align:right;">
                            {{ number_format($pending, 2) }}
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="4" style="text-align:right; font-weight:bold;">Total Product Revenue</td>
                    <td style="text-align:right; font-weight:bold;">₹{{ number_format($orderTotalRevenue, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>

    </table>

    <div class="footer">
        &copy; {{ date('Y') }} Pentapure Foods Factory Operations. All rights reserved.
    </div>
</body>
</html>
