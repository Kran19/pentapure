@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

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
      <option value="ready_dispatch" {{ request('dispatch_filter') === 'ready_dispatch' ? 'selected' : '' }}>Ready to Dispatch</option>
      <option value="ready_partial" {{ request('dispatch_filter') === 'ready_partial' || request('dispatch_filter') === 'partial_dispatch' || request('dispatch_filter') === 'partial_pending' ? 'selected' : '' }}>Ready to Partial Dispatch</option>
      <option value="not_ready" {{ request('dispatch_filter') === 'not_ready' ? 'selected' : '' }}>Not Ready</option>
      <option value="done" {{ request('dispatch_filter') === 'done' ? 'selected' : '' }}>Fully Dispatched (Completed)</option>
    </select>
  </form>
</div>

@php
  $dispatchFilter = request('dispatch_filter', 'all');
  $pendingOrdersList = collect($pageData['pendingOrders'] ?? []);
  $completedOrdersList = collect($pageData['completedOrders'] ?? []);

  if ($dispatchFilter === 'done') {
    $pendingOrdersList = collect([]);
  } elseif ($dispatchFilter === 'ready_dispatch') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'READY_DISPATCH');
    $completedOrdersList = collect([]);
  } elseif ($dispatchFilter === 'ready_partial' || $dispatchFilter === 'partial_dispatch' || $dispatchFilter === 'partial_pending') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'READY_PARTIAL');
    $completedOrdersList = collect([]);
  } elseif ($dispatchFilter === 'not_ready') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'NOT_READY');
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
        
        $readiness = $o['readiness'] ?? 'NOT_READY';
        $rawSt = strtoupper(trim((string)($o['dispatchStatus'] ?? 'PENDING')));

        if ($readiness === 'READY_DISPATCH') {
          $readinessLabel = 'READY TO DISPATCH';
          $readinessBg = '#16a34a';
          $readinessFg = '#ffffff';
          $btnText = 'DISPATCH';
          $progressColor = '#16a34a';
        } elseif ($readiness === 'READY_PARTIAL') {
          $readinessLabel = 'READY TO PARTIAL DISPATCH';
          $readinessBg = '#f59e0b';
          $readinessFg = '#ffffff';
          $btnText = 'PARTIAL DISPATCH';
          $progressColor = '#f59e0b';
        } else {
          $readinessLabel = 'NOT READY';
          $readinessBg = '#dc2626';
          $readinessFg = '#ffffff';
          $btnText = 'NOT READY';
          $progressColor = '#dc2626';
        }

        // Status badge next to Order # ID
        if (in_array($rawSt, ['PARTIAL_PENDING', 'PARTIAL PENDING', 'PARTIAL', 'PARTIAL_DISPATCH', 'PARTIAL DISPATCH']) || ($dispatchedQty > 0 && $dispatchedQty < $totalQty)) {
          $statusBadgeLabel = 'PARTIAL';
          $statusBadgeBg = '#f59e0b';
          $statusBadgeFg = '#ffffff';
        } else {
          $statusBadgeLabel = 'PENDING';
          $statusBadgeBg = '#ef4444';
          $statusBadgeFg = '#ffffff';
        }
      @endphp
      <div class="card" style="border-left: 4px solid {{ $progressColor }}; background:rgba(255,255,255,0.02); transition: transform 0.2s; margin-bottom: 0; padding:0; overflow:hidden; border-radius:12px;">
        <!-- Clickable Header Row -->
        <div onclick="toggleHomeAccordion('home-acc-{{ $o['id'] }}', this)" style="cursor:pointer; padding:1.1rem; user-select:none;">
          <div class="flex-between mb-1" style="align-items:center;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
              <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
              <span class="badge" style="font-size:0.65rem; background:{{ $statusBadgeBg }}; color:{{ $statusBadgeFg }}; padding:3px 8px; border-radius:4px; font-weight:700;">{{ $statusBadgeLabel }}</span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
              <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" onclick="event.stopPropagation(); localStorage.setItem('auto_dispatch_id', '{{ $o['id'] }}');" style="width:auto; text-decoration:none; background:{{ $readinessBg }}; color:{{ $readinessFg }}; font-weight:700; padding:4px 12px; border-radius:6px; font-size:0.8rem;">
                {{ $readinessLabel }}
              </a>
              <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
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

        <!-- Expandable Products List Drawer -->
        <div id="home-acc-{{ $o['id'] }}" class="home-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
          @if(!empty($o['items']) && count($o['items']) > 0)
            <div style="background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00);">
              <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Order Items & Dispatch Status</div>
              @foreach($o['items'] as $item)
                @php
                  $pName = preg_replace('/\s+(PURE|PREMIUM|COMMERCIAL|NONE|\b[A-Za-z0-9_-]+\b)\s*\((fg|raw|semi)\)$/i', '', $item['productName'] ?? 'Unknown');
                  $pName = preg_replace('/\s*\((fg|raw|semi)\)$/i', '', $pName);
                  $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? $item['grade'] : '';
                  $tName = ($item['productType'] === 'FINISHED') ? 'FG' : ($item['productType'] ? strtoupper($item['productType']) : 'N/A');
                  $tot = $item['quantity'] ?? 0;
                  $disp = $item['dispatchedQty'] ?? 0;
                  $rem = $item['remainingQty'] ?? 0;
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem; flex-wrap:wrap; gap:8px;">
                  <span>{{ $pName }} @if($gName)<strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong> @endif({{ $tName }})</span>
                  <span style="font-size:0.8rem; color:var(--text-muted);">
                    Total: <strong style="color:var(--text-main, #fff);">{{ number_format($tot, 2) }} kg</strong> | 
                    Dispatched: <strong style="color:#16a34a;">{{ number_format($disp, 2) }} kg</strong> | 
                    Pending: <strong style="color:#ef4444;">{{ number_format($rem, 2) }} kg</strong>
                  </span>
                </div>
              @endforeach
            </div>
          @else
            <div style="font-size:0.8rem; color:var(--text-muted);">No items details found for this order.</div>
          @endif
        </div>
      </div>
    @empty
      <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
        No pending dispatches found.
      </div>
    @endforelse
  @else
    @forelse($completedOrdersList as $o)
      <div class="card" style="border-left: 4px solid var(--secondary); background:rgba(255,255,255,0.02); margin-bottom: 0; padding:0; overflow:hidden; border-radius:12px;">
        <div onclick="toggleHomeAccordion('home-acc-comp-{{ $o['id'] }}', this)" style="cursor:pointer; padding:1.1rem; user-select:none;">
          <div class="flex-between mb-1" style="align-items:center;">
            <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
            <div style="display:flex; align-items:center; gap:10px;">
              <span class="badge badge-done" style="font-size:0.7rem; padding:4px 8px;">COMPLETED</span>
              <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
          </div>
          <div style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">
            <strong>Customer:</strong> {{ $o['companyName'] ?? 'N/A' }} <br>
            <strong>Transport:</strong> {{ $o['transporterName'] ?? 'N/A' }} <br>
            <strong>Date Closed:</strong> {{ \Carbon\Carbon::parse($o['date'])->format('d M Y, h:i A') }} <br>
            @if($o['notes'])
              <div style="margin-top:8px; font-style:italic;">Notes: {{ $o['notes'] }}</div>
            @endif
          </div>
        </div>

        <div id="home-acc-comp-{{ $o['id'] }}" class="home-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
          @if(!empty($o['items']) && count($o['items']) > 0)
            <div style="background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--secondary, #16a34a);">
              <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Order Items Summary</div>
              @foreach($o['items'] as $item)
                @php
                  $pName = preg_replace('/\s+(PURE|PREMIUM|COMMERCIAL|NONE|\b[A-Za-z0-9_-]+\b)\s*\((fg|raw|semi)\)$/i', '', $item['productName'] ?? 'Unknown');
                  $pName = preg_replace('/\s*\((fg|raw|semi)\)$/i', '', $pName);
                  $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? $item['grade'] : '';
                  $tName = ($item['productType'] === 'FINISHED') ? 'FG' : ($item['productType'] ? strtoupper($item['productType']) : 'N/A');
                  $tot = $item['quantity'] ?? 0;
                  $disp = $item['dispatchedQty'] ?? 0;
                  $rem = $item['remainingQty'] ?? 0;
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem; flex-wrap:wrap; gap:8px;">
                  <span>{{ $pName }} @if($gName)<strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong> @endif({{ $tName }})</span>
                  <span style="font-size:0.8rem; color:var(--text-muted);">
                    Total: <strong style="color:var(--text-main, #fff);">{{ number_format($tot, 2) }} kg</strong> | 
                    Dispatched: <strong style="color:#16a34a;">{{ number_format($disp, 2) }} kg</strong> | 
                    Pending: <strong style="color:#ef4444;">{{ number_format($rem, 2) }} kg</strong>
                  </span>
                </div>
              @endforeach
            </div>
          @else
            <div style="font-size:0.8rem; color:var(--text-muted);">No items details found for this order.</div>
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

<script>
  function toggleHomeAccordion(contentId, headerEl) {
    const content = document.getElementById(contentId);
    if (!content) return;
    const chevron = headerEl.querySelector('.acc-chevron');
    const isHidden = content.style.display === 'none' || content.style.display === '';
    
    if (isHidden) {
      content.style.display = 'block';
      if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
      content.style.display = 'none';
      if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
  }
</script>
@endsection

