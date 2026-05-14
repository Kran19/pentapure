@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🚚 Dispatch Order Activity</h2>
    <a href="{{ route('admin.dispatch.pdf', request()->all()) }}" class="btn" style="width:auto; padding:0.6rem 1.2rem; background:var(--secondary);">
      📥 Download PDF Report
    </a>
  </div>

  <!-- Filters -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <form method="GET" action="{{ route('admin.dispatch.activity') }}" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
      <div style="flex:1; min-width:200px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">Status</label>
        <select name="status" class="form-control" style="width:100%;" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
          <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
          <option value="DONE" {{ request('status') === 'DONE' ? 'selected' : '' }}>Dispatched (Done)</option>
        </select>
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">From Date</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" style="width:100%;">
      </div>
      <div style="flex:1; min-width:150px;">
        <label style="display:block; font-size:0.85rem; margin-bottom:0.4rem; color:var(--text-muted);">To Date</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" style="width:100%;">
      </div>
      <div style="display:flex; gap:0.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.2rem;">🔍 Filter</button>
        <a href="{{ route('admin.dispatch.activity') }}" class="btn" style="width:auto; padding:0.6rem 1.2rem; background:var(--glass-bg); color:var(--text);">🔄 Reset</a>
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
          <tbody>
            @foreach($pageData['orders'] as $order)
            <tr>
              <td style="font-weight:bold; color:var(--primary-light);">#{{ $order->id }}</td>
              <td style="font-size:0.85rem; white-space:nowrap;">{{ $order->created_at->format('d M Y, h:i A') }}</td>
              <td>
                <div style="font-weight:600;">{{ $order->company?->name ?? 'N/A' }}</div>
                <div style="font-size:0.75rem; color:var(--text-muted);">By: {{ $order->creator?->name ?? 'System' }}</div>
              </td>
              <td>
                <div style="max-width:300px;">
                  @foreach($order->items as $item)
                    <div style="font-size:0.85rem; margin-bottom:2px;">
                      • {{ $item->product?->name }} ({{ $item->grade }}): 
                      <span style="font-weight:600;">{{ $item->quantity }} {{ $item->product?->unit }}</span>
                    </div>
                  @endforeach
                  @if($order->notes)
                    <div style="font-size:0.75rem; color:var(--text-muted); font-style:italic; margin-top:4px;">
                      Note: {{ $order->notes }}
                    </div>
                  @endif
                </div>
              </td>
              <td>
                <span class="badge {{ $order->dispatch_status === 'DONE' ? 'badge-done' : ($order->dispatch_status === 'PARTIAL' ? 'badge-warning' : 'badge-pending') }}">
                  {{ $order->dispatch_status }}
                </span>
              </td>
              <td style="font-size:0.85rem;">
                @if($order->dispatchLog)
                  <div>🚚 Transporter: {{ $order->transporter?->name ?? '—' }}</div>
                  <div style="font-size:0.75rem; color:var(--text-muted);">
                    Dispatched by: {{ $order->dispatchLog->user?->name }}
                  </div>
                  @if($order->dispatchLog->lr_image_path)
                    <a href="{{ asset('storage/' . $order->dispatchLog->lr_image_path) }}" target="_blank" style="color:var(--primary-light); font-size:0.75rem;">
                      📄 View LR Copy
                    </a>
                  @endif
                @else
                  <span style="color:var(--text-muted);">Not dispatched yet</span>
                @endif
              </td>
            </tr>
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
