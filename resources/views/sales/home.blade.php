@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Sales Dashboard</h2>
  <div style="display:flex; gap:8px;">
    <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" style="width:auto; padding:0.5rem 1rem; text-decoration:none;">+ Create New Order</a>
  </div>
</div>

<div class="dashboard-grid" style="margin-top:1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history') }}" style="text-decoration:none;">
    <div style="color:var(--primary-light)">Total Orders</div>
    <div class="stat-value">{{ $pageData['stats']['totalOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?status=PENDING') }}" style="text-decoration:none;">
    <div style="color:var(--warning)">Pending Orders</div>
    <div class="stat-value">{{ $pageData['stats']['pendingOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?status=DONE') }}" style="text-decoration:none;">
    <div style="color:var(--secondary)">Dispatched Orders</div>
    <div class="stat-value">{{ $pageData['stats']['dispatchedOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?due=today') }}" style="text-decoration:none; border-top:3px solid var(--primary, #D88A00);">
    <div style="color:var(--primary, #D88A00); font-weight:600;">📅 Due Today Orders</div>
    <div class="stat-value" style="color:var(--text-main, #111827);">{{ $pageData['stats']['dueTodayOrdersCount'] ?? count($pageData['dueTodayOrders'] ?? []) }}</div>
  </a>
</div>

<!-- Due Today Orders Section -->
<div class="card mt-2" style="border:1px solid var(--glass-border, rgba(255,255,255,0.08));">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.06));">
    <div style="display:flex; align-items:center; gap:8px;">
      <h3 style="margin:0; font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:8px;">
        <span>📅 Orders Due Today</span>
        <span class="badge" style="background:var(--primary, #D88A00); color:#fff; font-size:0.8rem; padding:3px 10px; border-radius:12px; font-weight:700;">
          {{ count($pageData['dueTodayOrders'] ?? []) }}
        </span>
      </h3>
      <span style="font-size:0.82rem; color:var(--text-muted);">({{ now()->format('d M, Y') }})</span>
    </div>
    <div>
      <a class="btn btn-sm btn-secondary" href="{{ url(request()->segment(1) . '/history?due=today') }}" style="width:auto; text-decoration:none; font-size:0.82rem; padding:0.4rem 0.9rem;">
        View in History &rarr;
      </a>
    </div>
  </div>

  @if(!empty($pageData['dueTodayOrders']) && count($pageData['dueTodayOrders']) > 0)
    <div style="display:flex; flex-direction:column; gap:12px;">
      @foreach($pageData['dueTodayOrders'] as $dOrder)
        @php
          $oStatus = strtoupper(str_replace('_', ' ', $dOrder['status'] ?? ''));
          $ds = strtoupper(str_replace('_', ' ', $dOrder['dispatchStatus'] ?? 'PENDING'));
          if ($oStatus === 'CANCELLED') {
            $statusText = 'CANCELLED';
            $badgeStyle = 'background:#dc2626; color:#fff;';
          } elseif ($ds === 'DONE' || $ds === 'COMPLETED' || $ds === 'FULLY DISPATCHED' || $ds === 'DISPATCHED') {
            $statusText = 'FULLY DISPATCHED';
            $badgeStyle = 'background:#16a34a; color:#fff;';
          } elseif (in_array($ds, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIALLY DISPATCHED', 'PARTIAL PENDING', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH'])) {
            $statusText = 'PARTIAL';
            $badgeStyle = 'background:#f59e0b; color:#fff;';
          } else {
            $statusText = 'PENDING';
            $badgeStyle = 'background:#3b82f6; color:#fff;';
          }
          $canEdit = ($dOrder['status'] ?? '') === 'OPEN' && ($ds === 'PENDING' || $ds === 'UNASSIGNED' || empty($dOrder['dispatchStatus']));
        @endphp
        <div style="padding:1rem; border-radius:10px; border:1px solid var(--glass-border, rgba(255,255,255,0.08)); background:var(--card-bg, rgba(255,255,255,0.02)); transition:background 0.2s ease;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
              <div style="font-weight:700; font-size:1.05rem; color:var(--text-main); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span>#{{ strtoupper((string)$dOrder['id']) }}</span>
                <span>—</span>
                <span>{{ $dOrder['companyName'] ?? 'N/A' }}</span>
              </div>
              <div style="margin-top:6px; font-size:0.82rem; color:var(--text-muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span>Ordered: <strong>{{ \Carbon\Carbon::parse($dOrder['date'])->format('d-m-Y') }}</strong></span>
                <span>•</span>
                <span>DUE: <strong style="color:var(--text-main, #111827); font-weight:700;">{{ $dOrder['dueDate'] ?? 'TODAY' }}</strong></span>
                @if(!empty($dOrder['transportName']))
                  <span>•</span>
                  <span>Transport: <strong>{{ $dOrder['transportName'] }}</strong></span>
                @endif
              </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px; text-align:right;">
              <span class="badge" style="{{ $badgeStyle }} font-size:0.75rem; padding:4px 10px; border-radius:10px; font-weight:700;">
                {{ $statusText }}
              </span>
              <div style="font-weight:800; color:var(--primary, #D88A00); font-size:1.15rem; white-space:nowrap;">
                ₹{{ number_format($dOrder['total'] ?? 0, 2) }}
              </div>
            </div>
          </div>

          <!-- Product items breakdown -->
          @if(!empty($dOrder['products']) && count($dOrder['products']) > 0)
            <div style="margin-top:0.85rem; padding-top:0.75rem; border-top:1px dashed var(--glass-border, rgba(255,255,255,0.08));">
              <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:600; margin-bottom:6px;">Items to Dispatch:</div>
              <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach($dOrder['products'] as $prod)
                  <span style="font-size:0.82rem; padding:4px 10px; background:rgba(0,0,0,0.03); border:1px solid var(--border-soft, rgba(0,0,0,0.08)); border-radius:6px; color:var(--text-main);">
                    <strong>{{ $prod['productName'] ?? 'Item' }}</strong>
                    @if(!empty($prod['grade'])) ({{ $prod['grade'] }}) @endif
                    — <span style="font-weight:700; color:var(--primary, #D88A00);">{{ $prod['quantity'] ?? 0 }} kg</span>
                  </span>
                @endforeach
              </div>
            </div>
          @endif

          @if(!empty($dOrder['notes']))
            <div style="margin-top:0.6rem; font-size:0.82rem; color:var(--text-muted); font-style:italic;">
              Note: {{ $dOrder['notes'] }}
            </div>
          @endif

          <div style="display:flex; gap:8px; margin-top:0.85rem; justify-content:flex-end; flex-wrap:wrap;">
            @if($canEdit)
              <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action?edit=' . $dOrder['id']) }}" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; background:var(--warning, #FFA500); color:#000; font-weight:600; text-decoration:none; border-radius:6px;">
                ✏️ Edit Order
              </a>
            @endif
            <a class="btn btn-sm btn-secondary" href="{{ url((request()->segment(1) === 'sales' ? 'sales' : request()->segment(1) . '/sales') . '/order/pdf/' . $dOrder['id']) }}" target="_blank" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; text-decoration:none; border-radius:6px;">
              📄 Order PDF
            </a>
            <a class="btn btn-sm btn-secondary" href="{{ url(request()->segment(1) . '/history?q=' . $dOrder['id']) }}" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; text-decoration:none; border-radius:6px;">
              🔍 History Details
            </a>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div style="padding:2rem 1rem; text-align:center; color:var(--text-muted); background:rgba(0,0,0,0.01); border-radius:8px; border:1px dashed var(--glass-border, rgba(255,255,255,0.1));">
      <div style="font-size:1.4rem; margin-bottom:4px;">✨</div>
      <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No orders due today</div>
      <div style="font-size:0.82rem; margin-top:3px;">All scheduled orders are up to date!</div>
    </div>
  @endif
</div>

<div class="card mt-2">
  <div class="card-title">Quick Links</div>
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url(request()->segment(1) . '/action') }}">Create New Order</a>
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url(request()->segment(1) . '/history') }}">View History</a>
  </div>
</div>
@endsection
