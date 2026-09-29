@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🚚 Dispatch Order Activity</h2>
    @php
      $pdfRoute = Route::has(request()->segment(1) . '.dispatch.pdf') 
        ? route(request()->segment(1) . '.dispatch.pdf') 
        : (Route::has('admin.dispatch.pdf') ? route('admin.dispatch.pdf') : url(request()->segment(1) . '/dispatch-activity/pdf'));
      $activityRoute = Route::has(request()->segment(1) . '.dispatch.activity') 
        ? route(request()->segment(1) . '.dispatch.activity') 
        : (Route::has('admin.dispatch.activity') ? route('admin.dispatch.activity') : url(request()->segment(1) . '/dispatch-activity'));
    @endphp
    <button type="button" class="btn" onclick="window.downloadPdfAsync('{{ $pdfRoute }}', {{ json_encode(request()->all()) }}, this)" style="width:auto; padding:0.6rem 1.2rem; background:var(--secondary); cursor:pointer;">
      📥 Download PDF Report
    </button>
  </div>

  <!-- Filters -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <form method="GET" action="{{ $activityRoute }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
      <div style="flex:1; min-width:200px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">Status</label>
        <select name="status" class="form-control" style="width:100%;" onchange="this.form.submit()">
          <option value="" {{ in_array(request('status'), ['', 'ALL']) ? 'selected' : '' }}>All</option>
          <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
          <option value="PARTIAL" {{ in_array(request('status'), ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH']) ? 'selected' : '' }}>Partial</option>
          <option value="FULLY_DISPATCH" {{ in_array(request('status'), ['FULLY_DISPATCH', 'FULLY_DISPATCHED']) ? 'selected' : '' }}>Fully Dispatch</option>
        </select>
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">📅 From Date</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" style="width:100%;">
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">📅 To Date</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" style="width:100%;">
      </div>
      <div style="display:flex; gap:0.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.2rem;">🔍 Filter</button>
        <a href="{{ route(request()->segment(1) . '.dispatch.activity') }}" class="btn" style="width:auto; padding:0.6rem 1.2rem; background:var(--glass-bg); color:var(--text);">🔄 Reset</a>
      </div>
    </form>
  </div>

  @if($pageData['orders']->isEmpty())
    <div class="card" style="padding:3rem; text-align:center;">
      <p style="color:var(--text-muted); margin:0;">No orders found matching your criteria.</p>
    </div>
  @else
    <div class="card" style="padding:1.2rem;">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Date</th>
              <th>Customer / Company</th>
              <th>Items & Details</th>
              <th>Status</th>
              <th>Dispatch Details</th>
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
            <tr class="date-group-header" style="background: rgba(245, 158, 11, 0.12); border-top: 2px solid rgba(245, 158, 11, 0.4); border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
              <td colspan="6" style="padding: 0.65rem 1rem; font-weight: 700; color: var(--primary);">
                📅 {{ $dateKey !== 'Unknown' ? \Carbon\Carbon::parse($dateKey)->format('d M Y (l)') : 'Other Date' }}
                <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-left: 0.5rem; background: rgba(0,0,0,0.06); padding: 0.15rem 0.55rem; border-radius: 12px; display: inline-block;">
                  {{ count($dateOrders) }} {{ Str::plural('Order', count($dateOrders)) }}
                </span>
              </td>
            </tr>
            @foreach($dateOrders as $order)
            @php
                $orderDate = $order->date ? \Carbon\Carbon::parse($order->date) : $order->created_at;
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
            @endphp
            <tr>
              <td style="font-weight:bold; color:var(--primary-light);">#{{ $order->id }}</td>
              <td style="font-size:0.85rem; white-space:nowrap;">{{ $orderDate->format('d-m-Y, h:i A') }}</td>
              <td>
                <div style="font-weight:600;">{{ $order->company?->name ?? 'N/A' }}</div>
                <div style="font-size:0.75rem; color:var(--text-muted);">By: {{ $order->creator?->name ?? 'System' }}</div>
              </td>
              <td>
                <div style="min-width:280px; max-width:450px; white-space:normal; word-break:break-word;">
                  @foreach($order->items as $item)
                    <div style="font-size:0.85rem; margin-bottom:4px; padding-bottom:4px; border-bottom:1px solid rgba(255,255,255,0.05);">
                      <div style="margin-bottom:2px; white-space:normal;">
                        • {{ $item->product ? $item->product->formatName($item->grade) : 'Unknown' }}: 
                        <span style="font-weight:600;">{{ $item->quantity }} {{ $item->product?->unit }}</span>
                      </div>
                      @if($order->dispatch_status === 'PARTIAL' || (in_array($order->dispatch_status, ['DONE', 'FULLY_DISPATCHED']) && $order->dispatch_logs_count > 1))
                        <div style="font-size:0.75rem; color:var(--text-muted); padding-left:12px; white-space:normal;">
                          Dispatched: <span style="color:var(--secondary); font-weight:600;">{{ $item->dispatched_qty ?? 0 }} {{ $item->product?->unit }}</span>
                          &nbsp;|&nbsp;
                          Pending: <span style="color:var(--danger); font-weight:600;">{{ max(0, $item->quantity - ($item->dispatched_qty ?? 0)) }} {{ $item->product?->unit }}</span>
                        </div>
                      @endif

                      @if($order->dispatch_status === 'PENDING_PARTIAL' && $item->quantity > ($item->dispatched_qty ?? 0))
                        <div style="font-size:0.75rem; color:var(--text-muted); padding-left:12px; white-space:normal;">
                          🚧 Pending Partial Qty: <span style="color:var(--danger); font-weight:600;">{{ max(0, $item->quantity - ($item->dispatched_qty ?? 0)) }} {{ $item->product?->unit }}</span>
                        </div>
                      @endif

                    </div>
                  @endforeach
                  @if($order->notes)
                    <div style="font-size:0.75rem; color:var(--text-muted); font-style:italic; margin-top:4px; white-space:normal;">
                      Note: {{ $order->notes }}
                    </div>
                  @endif
                </div>
              </td>
              <td>
                <span class="badge {{ $badgeClass }}">{{ $label }}</span>
              </td>
              <td style="font-size:0.85rem;">
                @if($order->dispatchLog)
                  <div>🚚 Transporter: {{ $order->transporter?->name ?? '—' }}</div>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    Dispatched by: {{ $order->dispatchLog->user?->name }}
                  </div>
                  @if($order->dispatchLog->lr_image_path)
                    <a href="javascript:void(0)" onclick="app.viewImage('{{ asset($order->dispatchLog->lr_image_path) }}')" style="color:var(--primary-light); font-size:0.75rem; display:flex; align-items:center; gap:4px;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      View LR Copy
                    </a>
                  @endif
                @else
                  <span style="color:var(--text-muted);">Not dispatched yet</span>
                @endif
              </td>
            </tr>
            @endforeach
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="margin-top:1.5rem; display:flex; justify-content:center;">
        {{ $pageData['orders']->links() }}
      </div>
    </div>
  @endif
</div>

<style>
.badge-warning {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  border: 1px solid rgba(255, 193, 7, 0.3);
}
</style>
@endsection
