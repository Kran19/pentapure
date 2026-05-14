<!DOCTYPE html>
<html>
<head>
    <title>Dispatch Activity Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
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
                <th>ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Order Items</th>
                <th>Status</th>
                <th>Dispatch Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>#{{ $order->id }}</td>
                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                <td>
                    <strong>{{ $order->company?->name ?? 'N/A' }}</strong><br>
                    <small>By: {{ $order->creator?->name }}</small>
                </td>
                <td>
                    <ul class="items-list">
                        @foreach($order->items as $item)
                            <li>{{ $item->product?->name }} ({{ $item->grade }}): {{ $item->quantity }} {{ $item->product?->unit }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    <span class="badge {{ $order->dispatch_status === 'DONE' ? 'badge-done' : ($order->dispatch_status === 'PARTIAL' ? 'badge-partial' : 'badge-pending') }}">
                        {{ $order->dispatch_status }}
                    </span>
                </td>
                <td>
                    @if($order->dispatchLog)
                        Transporter: {{ $order->transporter?->name ?? '—' }}<br>
                        By: {{ $order->dispatchLog->user?->name }}<br>
                        <small>{{ $order->dispatchLog->created_at->format('d/m/Y H:i') }}</small>
                    @else
                        Not Dispatched
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} Pentapure Foods Factory Operations. All rights reserved.
    </div>
</body>
</html>
