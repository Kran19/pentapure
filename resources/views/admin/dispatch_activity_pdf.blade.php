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
                    @if(file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
                <td style="width: 60%; text-align: center; vertical-align: middle; border: none; background: transparent;">
                    <div class="brand-title">PentaPure</div>
                    <div class="tagline">FOOD &amp; SPICES PVT.LTD.</div>
                </td>
                <td style="width: 20%; text-align: right; vertical-align: middle; border: none; background: transparent;">
                    @if(file_exists(public_path('logo.png')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.png'))) }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="report-title">Dispatch Order Activity Report</div>
        <p>Generated on: {{ now()->format('d-m-Y, h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">Order ID</th>
                <th style="width: 11%;">Date & Time</th>
                <th style="width: 16%;">Customer</th>
                <th style="width: 18%;">Product</th>
                <th style="width: 8%; text-align: center;">Grade</th>
                <th style="width: 9%; text-align: right;">Ordered</th>
                <th style="width: 9%; text-align: right;">Dispatched</th>
                <th style="width: 8%; text-align: right;">Pending</th>
                <th style="width: 9%; text-align: center;">Status</th>
                <th style="width: 14%;">Transporter</th>
            </tr>
        </thead>
        @php
            $groupedOrders = $orders->groupBy(function($order) {
                $d = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
                return $d ? $d->format('Y-m-d') : 'Unknown';
            });
        @endphp
        <tbody>
            @foreach($groupedOrders as $dateKey => $dateOrders)
                <tr style="background-color: #f1f5f9;">
                    <td colspan="10" style="padding: 6px 8px; font-weight: bold; font-size: 11px; color: #1e293b; background: #e2e8f0; border: 1px solid #cbd5e1;">
                        DATE: {{ $dateKey !== 'Unknown' ? \Carbon\Carbon::parse($dateKey)->format('d M Y (l)') : 'Other Date' }}
                        <span style="font-size: 9px; color: #475569; margin-left: 8px;">
                            ({{ count($dateOrders) }} {{ count($dateOrders) === 1 ? 'Order' : 'Orders' }})
                        </span>
                    </td>
                </tr>
                @foreach($dateOrders as $order)
                @php
                    $orderDate = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
                    $items = $order->items;
                    $itemCount = max(1, $items->count());
                    $isDoneOrder = in_array($order->dispatch_status, ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED']);

                    $label = 'Pending';
                    if ($isDoneOrder) {
                        $label = 'Fully Dispatch';
                    } elseif (in_array($order->dispatch_status, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING'])) {
                        $label = 'Partial';
                    } elseif ($order->dispatch_status === 'PENDING' || !$order->dispatch_status) {
                        $label = 'Pending';
                    }
                @endphp

                @if($items->isEmpty())
                <tr>
                    <td style="text-align: center;"><strong>#{{ $order->id }}</strong></td>
                    <td>{{ $orderDate ? $orderDate->format('d M Y, h:i A') : '—' }}</td>
                    <td><strong>{{ $order->company?->name ?? 'N/A' }}</strong></td>
                    <td colspan="5" style="text-align: center; color: #888;">No items</td>
                    <td style="text-align: center;">{{ $label }}</td>
                    <td>{{ $order->transporter?->name ?? '—' }}</td>
                </tr>
                @else
                @foreach($items as $itemIdx => $item)
                @php
                    $isFirst = ($itemIdx === 0);
                    $dispatched = ($item->dispatched_qty > 0) ? (float)$item->dispatched_qty : ($isDoneOrder ? (float)$item->quantity : 0);
                    $pending = max(0, (float)$item->quantity - $dispatched);
                    $unit = $item->product?->unit ?? 'kg';
                @endphp
                <tr>
                    @if($isFirst)
                    <td rowspan="{{ $itemCount }}" style="text-align: center; vertical-align: middle;">
                        <strong>#{{ $order->id }}</strong>
                    </td>
                    <td rowspan="{{ $itemCount }}" style="vertical-align: middle;">
                        {{ $orderDate ? $orderDate->format('d M Y, h:i A') : '—' }}
                    </td>
                    <td rowspan="{{ $itemCount }}" style="vertical-align: middle;">
                        <strong>{{ $order->company?->name ?? 'N/A' }}</strong><br>
                        <span style="font-size: 9px; color: #666;">By: {{ $order->creator?->name ?? 'System' }}</span>
                        @if($order->notes)
                            <div style="font-size: 9px; color: #666; font-style: italic; margin-top: 3px;">Note: {{ $order->notes }}</div>
                        @endif
                    </td>
                    @endif

                    <td>
                        {{ $item->product?->name ?? 'Unknown' }}
                        @if($item->product?->type)
                            <span style="font-size: 9px; color: #777;">({{ ($item->product->type === 'FINISHED' || $item->product->type === 'FG') ? 'FG' : $item->product->type }})</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        {{ ($item->grade && !in_array(strtoupper(trim($item->grade)), ['NONE', 'N/A', 'NA', ''])) ? $item->grade : '—' }}
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($item->quantity, 2) }} {{ $unit }}
                    </td>
                    <td style="text-align: right; color: #16a34a; font-weight: bold;">
                        {{ number_format($dispatched, 2) }} {{ $unit }}
                    </td>
                    <td style="text-align: right; color: {{ $pending > 0 ? '#dc2626' : '#16a34a' }}; font-weight: bold;">
                        {{ $pending > 0 ? number_format($pending, 2) . ' ' . $unit : '0.00' }}
                    </td>

                    @if($isFirst)
                    <td rowspan="{{ $itemCount }}" style="text-align: center; vertical-align: middle;">
                        <strong>{{ $label }}</strong>
                    </td>
                    <td rowspan="{{ $itemCount }}" style="vertical-align: middle;">
                        @if($order->dispatchLog)
                            <div>{{ $order->transporter?->name ?? 'N/A' }}</div>
                            <div style="font-size: 9px; color: #666;">By: {{ $order->dispatchLog->user?->name }}</div>
                            @if($order->dispatchLog->lr_no)
                                <div style="font-size: 9px; color: #666;">LR: {{ $order->dispatchLog->lr_no }}</div>
                            @endif
                        @else
                            <span style="color: #888;">Not dispatched yet</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
                @endif
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by PentaPure ERP System on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>
</html>
