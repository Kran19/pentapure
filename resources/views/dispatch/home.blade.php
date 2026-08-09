@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'pending');
@endphp

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Dispatches</h2>
</div>

<!-- Raw Stock -->
<div class="card mb-1" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:0.8rem;">
  <div style="font-size:0.75rem; color:var(--primary-light); margin-bottom:8px; font-weight:600; display:flex; align-items:center; gap:6px;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--secondary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
    Raw Stock
  </div>
  <div style="font-size:0.7rem; color:var(--text-muted); font-weight:normal; margin-bottom:5px; text-align:right; display:flex; justify-content:flex-end; gap:5px;">
    <button type="button" onclick="document.getElementById('raw-stock-scroll').scrollBy({left:-200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&larr;</button>
    <button type="button" onclick="document.getElementById('raw-stock-scroll').scrollBy({left:200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&rarr;</button>
  </div>
  <div id="raw-stock-scroll" style="display:flex; overflow-x:auto; gap:10px; padding-bottom:5px; scrollbar-width:none; -ms-overflow-style:none;">
    <style>#raw-stock-scroll::-webkit-scrollbar { display: none; }</style>
    @forelse($pageData['rawStock'] as $s)
      <div style="flex:0 0 150px; background:rgba(255,255,255,0.04); padding:8px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:0.7rem; font-weight:700; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->name }} <small class="text-muted">({{ $s->grade }})</small></div>
        <div style="font-size:0.85rem; font-weight:800; color:var(--secondary);">{{ number_format($s->quantity, 2) }} <span style="font-size:0.6rem; font-weight:400; color:var(--text-muted);">{{ $s->unit }}</span></div>
      </div>
    @empty
      <div style="font-size:0.7rem; color:var(--text-muted);">No raw stock available</div>
    @endforelse
  </div>
</div>

<!-- Semi Stock -->
<div class="card mb-1" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:0.8rem;">
  <div style="font-size:0.75rem; color:var(--primary-light); margin-bottom:8px; font-weight:600; display:flex; align-items:center; gap:6px;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--secondary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
    Semi-Finished Stock
  </div>
  <div style="font-size:0.7rem; color:var(--text-muted); font-weight:normal; margin-bottom:5px; text-align:right; display:flex; justify-content:flex-end; gap:5px;">
    <button type="button" onclick="document.getElementById('semi-stock-scroll').scrollBy({left:-200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&larr;</button>
    <button type="button" onclick="document.getElementById('semi-stock-scroll').scrollBy({left:200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&rarr;</button>
  </div>
  <div id="semi-stock-scroll" style="display:flex; overflow-x:auto; gap:10px; padding-bottom:5px; scrollbar-width:none; -ms-overflow-style:none;">
    <style>#semi-stock-scroll::-webkit-scrollbar { display: none; }</style>
    @forelse($pageData['semiStock'] as $s)
      <div style="flex:0 0 150px; background:rgba(255,255,255,0.04); padding:8px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:0.7rem; font-weight:700; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->name }} <small class="text-muted">({{ $s->grade }})</small></div>
        <div style="font-size:0.85rem; font-weight:800; color:var(--secondary);">{{ number_format($s->quantity, 2) }} <span style="font-size:0.6rem; font-weight:400; color:var(--text-muted);">{{ $s->unit }}</span></div>
      </div>
    @empty
      <div style="font-size:0.7rem; color:var(--text-muted);">No semi stock available</div>
    @endforelse
  </div>
</div>

<!-- FG Stock -->
<div class="card mb-2" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:0.8rem;">
  <div style="font-size:0.75rem; color:var(--primary-light); margin-bottom:8px; font-weight:600; display:flex; align-items:center; gap:6px;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--secondary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
    FG Stock
  </div>
  <div style="font-size:0.7rem; color:var(--text-muted); font-weight:normal; margin-bottom:5px; text-align:right; display:flex; justify-content:flex-end; gap:5px;">
    <button type="button" onclick="document.getElementById('finished-stock-scroll').scrollBy({left:-200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&larr;</button>
    <button type="button" onclick="document.getElementById('finished-stock-scroll').scrollBy({left:200, behavior:'smooth'})" style="border:1px solid #444; background:transparent; color:#ccc; border-radius:4px; cursor:pointer; padding:2px 8px;">&rarr;</button>
  </div>
  <div id="finished-stock-scroll" style="display:flex; overflow-x:auto; gap:10px; padding-bottom:5px; scrollbar-width:none; -ms-overflow-style:none;">
    <style>#finished-stock-scroll::-webkit-scrollbar { display: none; }</style>
    @forelse($pageData['finishedStock'] as $s)
      <div style="flex:0 0 150px; background:rgba(255,255,255,0.04); padding:8px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:0.7rem; font-weight:700; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->name }} <small class="text-muted">({{ $s->grade }})</small></div>
        <div style="font-size:0.85rem; font-weight:800; color:var(--secondary);">{{ number_format($s->quantity, 2) }} <span style="font-size:0.6rem; font-weight:400; color:var(--text-muted);">{{ $s->unit }}</span></div>
      </div>
    @empty
      <div style="font-size:0.7rem; color:var(--text-muted);">No finished stock available</div>
    @endforelse
  </div>
</div>

<div class="tabs" style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
  <div style="display:flex; gap:8px;">
    <a class="tab-btn {{ $tab==='pending'?'active':'' }}" href="?tab=pending&dispatch_filter={{ request('dispatch_filter', 'all') }}" style="text-decoration:none;">Pending</a>
    <a class="tab-btn {{ $tab==='completed'?'active':'' }}" href="?tab=completed&dispatch_filter={{ request('dispatch_filter', 'all') }}" style="text-decoration:none;">Completed</a>
  </div>
  <form method="GET" action="" style="display:flex; align-items:center; gap:8px; margin:0;">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <select name="dispatch_filter" onchange="this.form.submit()" style="padding:0.4rem 0.8rem; font-size:0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.2); background:#1f2937; color:#fff; cursor:pointer;">
      <option value="all" {{ request('dispatch_filter') === 'all' || !request('dispatch_filter') ? 'selected' : '' }}>All Scenarios</option>
      <option value="done" {{ request('dispatch_filter') === 'done' ? 'selected' : '' }}>Fully Dispatched (DONE)</option>
      <option value="partial_dispatch" {{ request('dispatch_filter') === 'partial_dispatch' ? 'selected' : '' }}>Partial Dispatched</option>
      <option value="partial_pending" {{ request('dispatch_filter') === 'partial_pending' ? 'selected' : '' }}>Partial Pending</option>
      <option value="pending" {{ request('dispatch_filter') === 'pending' ? 'selected' : '' }}>Fully Pending</option>
    </select>
  </form>
</div>

@php
  $dispatchFilter = request('dispatch_filter', 'all');
  $pendingOrdersList = collect($pageData['pendingOrders'] ?? []);
  $completedOrdersList = collect($pageData['completedOrders'] ?? []);

  if ($dispatchFilter === 'done') {
    $pendingOrdersList = collect([]);
  } elseif ($dispatchFilter === 'partial_dispatch' || $dispatchFilter === 'partial_pending') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => (float)$o['dispatchedQty'] > 0 && (float)$o['dispatchedQty'] < (float)$o['totalQty']);
    $completedOrdersList = collect([]);
  } elseif ($dispatchFilter === 'pending') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => (float)$o['dispatchedQty'] == 0);
    $completedOrdersList = collect([]);
  }
@endphp

<div style="display:flex; flex-direction:column; gap:12px;">
  @if($tab === 'pending')
    @forelse($pendingOrdersList as $o)
      @php
        $totalQty = (float)$o['totalQty'];
        $dispatchedQty = (float)$o['dispatchedQty'];
        $pct = $totalQty > 0 ? round(($dispatchedQty / $totalQty) * 100) : 0;
        $progressColor = $pct === 0 ? 'var(--warning)' : 'var(--secondary)';
        $isReadySection = true; // By default assume ready, though JS does checking
      @endphp
      <div class="card" style="border-left: 4px solid {{ $progressColor }}; background:rgba(255,255,255,0.02); transition: transform 0.2s; margin-bottom: 0;">
        <div class="flex-between mb-1">
          <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
          <a class="btn btn-sm" href="{{ url('dispatch/action') }}" onclick="localStorage.setItem('auto_dispatch_id', '{{ $o['id'] }}');" style="width:auto; text-decoration:none; background:{{ $progressColor }};">
            {{ $pct === 0 ? 'Dispatch' : 'Partial Dispatch' }}
          </a>
        </div>
        <div style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">
          <strong>Customer:</strong> {{ $o['companyName'] }} <br>
          <strong>Transport:</strong> {{ $o['transporterName'] }}
          @if($o['notes'])
            <div style="margin-top:8px; padding:8px 12px; background:rgba(244,180,0,0.06); border-left:3px solid var(--primary-light); border-radius:4px; font-size:0.8rem; color:#fff; word-break:break-word;">
              <strong>Notes:</strong> {{ $o['notes'] }}
            </div>
          @endif
        </div>

        <div style="margin-top:12px;">
          <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:4px;">
            <span style="color:var(--text-muted);">Dispatch Progress</span>
            <span style="color:{{ $progressColor }}; font-weight:bold;">{{ number_format($dispatchedQty, 2) }}/{{ number_format($totalQty, 2) }} kg ({{ $pct }}%)</span>
          </div>
          <div style="background:rgba(255,255,255,0.1); border-radius:6px; height:6px; overflow:hidden;">
            <div style="background:{{ $progressColor }}; height:100%; width:{{ $pct }}%; border-radius:6px; transition:width 0.3s;"></div>
          </div>
        </div>
      </div>
    @empty
      <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
        No pending dispatches found.
      </div>
    @endforelse
  @else
    @forelse($completedOrdersList as $o)
      <div class="card" style="border-left: 4px solid var(--secondary); background:rgba(255,255,255,0.02); margin-bottom: 0;">
        <div class="flex-between mb-1">
          <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
          <span class="badge badge-done" style="font-size:0.7rem; padding:4px 8px;">COMPLETED</span>
        </div>
        <div style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">
          <strong>Date Closed:</strong> {{ \Carbon\Carbon::parse($o['date'])->format('d M Y, h:i A') }} <br>
          @if($o['notes'])
            <div style="margin-top:8px; font-style:italic;">Notes: {{ $o['notes'] }}</div>
          @endif
        </div>
      </div>
    @empty
      <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
        No completed dispatches found.
      </div>
    @endforelse
  @endif
</div>
@endsection
