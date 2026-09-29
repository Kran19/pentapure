@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <div>
      <h2 style="margin:0; font-size:1.5rem; font-weight:700;">🚚 Dispatch Order Activity</h2>
      <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.85rem;">Date-wise tracking of order dispatches, item deliveries, and pending quantities.</p>
    </div>
    <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ route(request()->segment(1) . '.dispatch.pdf') }}', {{ json_encode(request()->all()) }}, this)" style="width:auto; padding:0.6rem 1.2rem; background:var(--secondary); cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
      📥 Download PDF Report
    </button>
  </div>

  <!-- KPI Summary Cards -->
  @if(!empty($pageData['stats']))
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <a href="{{ route(request()->segment(1) . '.dispatch.activity', array_merge(request()->except('status'), ['status' => ''])) }}" class="card" style="padding:1rem 1.2rem; border-left:4px solid var(--primary-light, #8A5A00); text-decoration:none; color:inherit; transition:transform 0.15s ease;">
      <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px;">Total Orders</div>
      <div style="font-size:1.6rem; font-weight:800; color:var(--text-main); margin-top:4px;">{{ $pageData['stats']['total'] }}</div>
      <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">In selected period</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.dispatch.activity', array_merge(request()->except('status'), ['status' => 'PENDING'])) }}" class="card" style="padding:1rem 1.2rem; border-left:4px solid #ef4444; text-decoration:none; color:inherit; transition:transform 0.15s ease;">
      <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#ef4444; letter-spacing:0.5px;">Pending Orders</div>
      <div style="font-size:1.6rem; font-weight:800; color:#ef4444; margin-top:4px;">{{ $pageData['stats']['pending'] }}</div>
      <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">Awaiting dispatch</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.dispatch.activity', array_merge(request()->except('status'), ['status' => 'PARTIAL'])) }}" class="card" style="padding:1rem 1.2rem; border-left:4px solid #f59e0b; text-decoration:none; color:inherit; transition:transform 0.15s ease;">
      <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#f59e0b; letter-spacing:0.5px;">Partial Orders</div>
      <div style="font-size:1.6rem; font-weight:800; color:#f59e0b; margin-top:4px;">{{ $pageData['stats']['partial'] }}</div>
      <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">Partial delivery in progress</div>
    </a>
    <a href="{{ route(request()->segment(1) . '.dispatch.activity', array_merge(request()->except('status'), ['status' => 'FULLY_DISPATCH'])) }}" class="card" style="padding:1rem 1.2rem; border-left:4px solid #16a34a; text-decoration:none; color:inherit; transition:transform 0.15s ease;">
      <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#16a34a; letter-spacing:0.5px;">Fully Dispatched</div>
      <div style="font-size:1.6rem; font-weight:800; color:#16a34a; margin-top:4px;">{{ $pageData['stats']['fully_dispatched'] }}</div>
      <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">100% fulfilled</div>
    </a>
  </div>
  @endif

  <!-- Filters -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <form method="GET" action="{{ route(request()->segment(1) . '.dispatch.activity') }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
      <div style="flex:1; min-width:200px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted); font-weight:600;">Status</label>
        <select name="status" class="form-control" style="width:100%; font-weight:600;" onchange="this.form.submit()">
          <option value="" {{ in_array(request('status'), ['', 'ALL']) ? 'selected' : '' }}>All</option>
          <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
          <option value="PARTIAL" {{ in_array(request('status'), ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH']) ? 'selected' : '' }}>Partial</option>
          <option value="FULLY_DISPATCH" {{ in_array(request('status'), ['FULLY_DISPATCH', 'FULLY_DISPATCHED']) ? 'selected' : '' }}>Fully Dispatch</option>
        </select>
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted); font-weight:600;">📅 From Date</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" style="width:100%;">
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted); font-weight:600;">📅 To Date</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" style="width:100%;">
      </div>
      <div style="display:flex; gap:0.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.2rem; font-weight:600;">🔍 Filter</button>
        <a href="{{ route(request()->segment(1) . '.dispatch.activity') }}" class="btn" style="width:auto; padding:0.6rem 1.2rem; background:var(--glass-bg); color:var(--text); font-weight:600;">🔄 Reset</a>
      </div>
    </form>
  </div>

  @if($pageData['orders']->isEmpty())
    <div class="card" style="padding:3rem; text-align:center;">
      <div style="font-size:2.5rem; margin-bottom:0.5rem;">📦</div>
      <h3 style="margin:0 0 0.5rem; color:var(--text-main);">No Orders Found</h3>
      <p style="color:var(--text-muted); margin:0;">There are no dispatch activity records matching your current filter criteria.</p>
    </div>
  @else
    <div class="card" style="padding:0; overflow:hidden;">
      <div class="table-container" style="overflow-x:auto;">
        <table class="proper-dispatch-table" style="width:100%; border-collapse:collapse; margin:0;">
          <thead>
            <tr style="background:var(--table-header-bg, #FFF3D6); color:var(--table-header-text, #5A4300); border-bottom:2px solid var(--border-soft, #DDCFAF);">
              <th style="padding:12px 14px; text-align:center; font-size:0.8rem; font-weight:700; white-space:nowrap; border-right:1px solid rgba(0,0,0,0.06);"># ORDER</th>
              <th style="padding:12px 14px; text-align:left; font-size:0.8rem; font-weight:700; white-space:nowrap; border-right:1px solid rgba(0,0,0,0.06);">📅 DATE & TIME</th>
              <th style="padding:12px 14px; text-align:left; font-size:0.8rem; font-weight:700; min-width:180px; border-right:1px solid rgba(0,0,0,0.06);">🏢 CUSTOMER / COMPANY</th>
              <th style="padding:12px 14px; text-align:left; font-size:0.8rem; font-weight:700; min-width:180px;">📦 PRODUCT</th>
              <th style="padding:12px 14px; text-align:center; font-size:0.8rem; font-weight:700; white-space:nowrap;">🏷️ GRADE</th>
              <th style="padding:12px 14px; text-align:right; font-size:0.8rem; font-weight:700; white-space:nowrap;">⚖️ ORDER QTY</th>
              <th style="padding:12px 14px; text-align:right; font-size:0.8rem; font-weight:700; white-space:nowrap;">🚚 DISPATCHED</th>
              <th style="padding:12px 14px; text-align:right; font-size:0.8rem; font-weight:700; white-space:nowrap; border-right:1px solid rgba(0,0,0,0.06);">⏳ PENDING</th>
              <th style="padding:12px 14px; text-align:center; font-size:0.8rem; font-weight:700; white-space:nowrap; border-right:1px solid rgba(0,0,0,0.06);">📌 STATUS</th>
              <th style="padding:12px 14px; text-align:left; font-size:0.8rem; font-weight:700; min-width:200px;">📋 DISPATCH DETAILS</th>
            </tr>
          </thead>
          @php
              $groupedOrders = $pageData['orders']->getCollection()->groupBy(function($order) {
                  $d = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
                  return $d ? $d->format('Y-m-d') : 'Unknown';
              });
          @endphp
          <tbody>
            @foreach($groupedOrders as $dateKey => $dateOrders)
            <!-- Date-wise Grouping Header Banner -->
            <tr class="date-group-header" style="background: rgba(245, 158, 11, 0.12); border-top: 2px solid rgba(245, 158, 11, 0.4); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
              <td colspan="10" style="padding: 0.75rem 1.2rem; font-weight: 700; font-size: 0.95rem; color: var(--primary, #D88A00);">
                📅 {{ $dateKey !== 'Unknown' ? \Carbon\Carbon::parse($dateKey)->format('d M Y (l)') : 'Other Date' }}
                <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-left: 0.6rem; background: rgba(0,0,0,0.06); padding: 0.2rem 0.65rem; border-radius: 12px; display: inline-block;">
                  {{ count($dateOrders) }} {{ Str::plural('Order', count($dateOrders)) }}
                </span>
              </td>
            </tr>

            @foreach($dateOrders as $orderIndex => $order)
            @php
                $orderDate = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
                $items = $order->items;
                $itemCount = max(1, $items->count());
                
                // Status badge
                $badgeClass = 'badge-pending';
                $label = 'Pending';
                if (in_array($order->dispatch_status, ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED'])) {
                    $badgeClass = 'badge-done';
                    $label = 'Fully Dispatch';
                } elseif (in_array($order->dispatch_status, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL PENDING'])) {
                    $badgeClass = 'badge-warning';
                    $label = 'Partial';
                } elseif ($order->dispatch_status === 'PENDING' || !$order->dispatch_status) {
                    $badgeClass = 'badge-pending';
                    $label = 'Pending';
                }

                $isDoneOrder = in_array($order->dispatch_status, ['DONE', 'COMPLETED', 'FULLY_DISPATCHED', 'FULLY DISPATCHED']);
                $orderBg = ($orderIndex % 2 === 0) ? 'background:var(--table-row-bg, #ffffff);' : 'background:rgba(0,0,0,0.015);';
            @endphp

            @if($items->isEmpty())
              <!-- Empty Items Fallback -->
              <tr style="{{ $orderBg }} border-bottom: 2px solid var(--border-soft, #e2e8f0);">
                <td style="font-weight:700; color:var(--primary-light); vertical-align:middle; text-align:center; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.06);">
                  #{{ $order->id }}
                </td>
                <td style="font-size:0.85rem; white-space:nowrap; vertical-align:middle; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.06);">
                  {{ $orderDate ? $orderDate->format('d-m-Y, h:i A') : '—' }}
                </td>
                <td style="vertical-align:middle; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.06);">
                  <div style="font-weight:600;">{{ $order->company?->name ?? 'N/A' }}</div>
                  <div style="font-size:0.75rem; color:var(--text-muted);">By: {{ $order->creator?->name ?? 'System' }}</div>
                </td>
                <td colspan="5" style="text-align:center; color:var(--text-muted); font-style:italic; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.06);">
                  No items in this order
                </td>
                <td style="text-align:center; vertical-align:middle; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.06);">
                  <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                </td>
                <td style="font-size:0.85rem; vertical-align:middle; padding:12px 14px;">
                  @if($order->dispatchLog)
                    <div>🚚 {{ $order->transporter?->name ?? '—' }}</div>
                  @else
                    <span style="color:var(--text-muted);">Not dispatched yet</span>
                  @endif
                </td>
              </tr>
            @else
              <!-- Structured Proper Table Rows (Rowspan per Order) -->
              @foreach($items as $itemIdx => $item)
              @php
                  $isFirst = ($itemIdx === 0);
                  $isLast = ($itemIdx === ($items->count() - 1));
                  $rowBorder = $isLast ? 'border-bottom: 2px solid var(--border-soft, #cbd5e1);' : 'border-bottom: 1px solid rgba(0,0,0,0.05);';

                  $dispatched = ($item->dispatched_qty > 0) ? (float)$item->dispatched_qty : ($isDoneOrder ? (float)$item->quantity : 0);
                  $pending = max(0, (float)$item->quantity - $dispatched);
                  $unit = $item->product?->unit ?? 'kg';
              @endphp
              <tr style="{{ $orderBg }} {{ $rowBorder }}" class="dispatch-row">
                @if($isFirst)
                  <td rowspan="{{ $itemCount }}" style="font-weight:800; color:var(--primary-light, #8A5A00); vertical-align:middle; text-align:center; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.07); background:inherit;">
                    <div style="font-size:1.05rem;">#{{ $order->id }}</div>
                    <div style="font-size:0.7rem; color:var(--text-muted); font-weight:600; margin-top:2px;">{{ $items->count() }} {{ Str::plural('Item', $items->count()) }}</div>
                  </td>
                  <td rowspan="{{ $itemCount }}" style="font-size:0.83rem; white-space:nowrap; vertical-align:middle; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.07); background:inherit;">
                    <div style="font-weight:700; color:var(--text-main);">{{ $orderDate ? $orderDate->format('d-m-Y') : '—' }}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">{{ $orderDate ? $orderDate->format('h:i A') : '' }}</div>
                  </td>
                  <td rowspan="{{ $itemCount }}" style="vertical-align:middle; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.07); background:inherit;">
                    <div style="font-weight:700; font-size:0.92rem; color:var(--text-main);">{{ $order->company?->name ?? 'N/A' }}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Created by: {{ $order->creator?->name ?? 'System' }}</div>
                    @if($order->notes)
                      <div style="font-size:0.75rem; color:#b45309; margin-top:4px; font-style:italic; background:rgba(245,158,11,0.1); padding:2px 6px; border-radius:4px; display:inline-block; border:1px solid rgba(245,158,11,0.2);">
                        Note: {{ $order->notes }}
                      </div>
                    @endif
                  </td>
                @endif

                <!-- Item Details (Individual Proper Columns) -->
                <td style="vertical-align:middle; padding:10px 14px;">
                  <div style="font-weight:600; font-size:0.9rem; color:var(--text-main);">{{ $item->product?->name ?? 'Unknown Product' }}</div>
                  @if($item->product?->type)
                    <span style="font-size:0.68rem; background:rgba(0,0,0,0.06); padding:1px 5px; border-radius:3px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">
                      {{ ($item->product->type === 'FINISHED' || $item->product->type === 'FG') ? 'FG' : $item->product->type }}
                    </span>
                  @endif
                </td>
                <td style="vertical-align:middle; text-align:center; padding:10px 14px;">
                  @if($item->grade && !in_array(strtoupper(trim($item->grade)), ['NONE', 'N/A', 'NA', '']))
                    <span class="badge" style="background:rgba(216,138,0,0.12); color:var(--primary, #D88A00); font-weight:700; font-size:0.75rem; border:1px solid rgba(216,138,0,0.3); padding:3px 8px; border-radius:4px;">
                      {{ $item->grade }}
                    </span>
                  @else
                    <span style="color:var(--text-muted); font-size:0.85rem;">—</span>
                  @endif
                </td>
                <td style="vertical-align:middle; text-align:right; font-weight:700; font-size:0.9rem; padding:10px 14px; white-space:nowrap;">
                  {{ number_format($item->quantity, 2) }} <span style="font-size:0.75rem; font-weight:normal; color:var(--text-muted);">{{ $unit }}</span>
                </td>
                <td style="vertical-align:middle; text-align:right; font-size:0.9rem; padding:10px 14px; white-space:nowrap;">
                  @if($dispatched > 0)
                    <span style="color:#16a34a; font-weight:700;">{{ number_format($dispatched, 2) }}</span> <span style="font-size:0.75rem; color:#16a34a;">{{ $unit }}</span>
                  @else
                    <span style="color:var(--text-muted);">0.00 <span style="font-size:0.75rem;">{{ $unit }}</span></span>
                  @endif
                </td>
                <td style="vertical-align:middle; text-align:right; font-size:0.9rem; padding:10px 14px; white-space:nowrap; border-right:1px solid rgba(0,0,0,0.07);">
                  @if($pending > 0)
                    <span style="color:#dc2626; font-weight:700;">{{ number_format($pending, 2) }}</span> <span style="font-size:0.75rem; color:#dc2626;">{{ $unit }}</span>
                  @else
                    <span style="color:#16a34a; font-weight:700; font-size:0.8rem; background:rgba(22,163,74,0.12); padding:2px 8px; border-radius:4px; border:1px solid rgba(22,163,74,0.25);">✓ 0.00</span>
                  @endif
                </td>

                @if($isFirst)
                  <td rowspan="{{ $itemCount }}" style="vertical-align:middle; text-align:center; padding:12px 14px; border-right:1px solid rgba(0,0,0,0.07); background:inherit;">
                    <span class="badge {{ $badgeClass }}" style="font-size:0.75rem; padding:4px 10px; border-radius:6px; font-weight:700;">{{ $label }}</span>
                  </td>
                  <td rowspan="{{ $itemCount }}" style="vertical-align:middle; font-size:0.83rem; padding:12px 14px; background:inherit;">
                    @if($order->dispatchLog)
                      <div style="font-weight:700; color:var(--text-main);">🚚 {{ $order->transporter?->name ?? 'Transporter Assigned' }}</div>
                      <div style="font-size:0.75rem; color:var(--text-muted); margin-top:3px;">
                        Dispatched by: <strong style="color:var(--text-main);">{{ $order->dispatchLog->user?->name ?? '—' }}</strong>
                      </div>
                      @if($order->dispatchLog->lr_no)
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                          LR No: <strong style="color:var(--text-main);">{{ $order->dispatchLog->lr_no }}</strong>
                        </div>
                      @endif
                      @if($order->dispatchLog->driver_no)
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                          Driver: {{ $order->dispatchLog->driver_no }}
                        </div>
                      @endif
                      @if($order->dispatchLog->lr_image_path)
                        <a href="javascript:void(0)" onclick="app.viewImage('{{ asset($order->dispatchLog->lr_image_path) }}')" style="color:var(--primary-light, #8A5A00); font-size:0.75rem; display:inline-flex; align-items:center; gap:4px; margin-top:5px; text-decoration:none; font-weight:700; background:rgba(216,138,0,0.08); padding:2px 6px; border-radius:4px; border:1px solid rgba(216,138,0,0.2);">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                          View LR Copy
                        </a>
                      @endif
                    @else
                      <span style="color:var(--text-muted); font-size:0.8rem; font-style:italic;">Not dispatched yet</span>
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
      </div>

      <!-- Pagination -->
      <div style="padding:1.2rem; display:flex; justify-content:center; border-top:1px solid var(--border-soft, #e2e8f0);">
        {{ $pageData['orders']->links() }}
      </div>
    </div>
  @endif
</div>

<style>
.badge-warning {
  background: rgba(255, 193, 7, 0.2);
  color: #b45309;
  border: 1px solid rgba(255, 193, 7, 0.4);
}
.proper-dispatch-table tr.dispatch-row:hover td {
  background-color: rgba(245, 158, 11, 0.04) !important;
}
</style>
@endsection
