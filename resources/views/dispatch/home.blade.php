@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
@php
  $mainTab = request('main_tab', 'orders'); // 'orders' or 'stock'
  $orderTab = request('tab', 'pending');     // 'pending' or 'completed'
  $stockSubTab = request('stock_stage', 'all'); // 'all', 'raw', 'semi', 'finished', 'packaging'
  $dispatchFilter = request('dispatch_filter', 'all');

  $pendingOrdersList = collect($pageData['pendingOrders'] ?? []);
  $completedOrdersList = collect($pageData['completedOrders'] ?? []);

  if ($dispatchFilter === 'done') {
    $pendingOrdersList = collect([]);
  } elseif ($dispatchFilter === 'ready_dispatch') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'READY_DISPATCH');
    $completedOrdersList = collect([]);
  } elseif (in_array($dispatchFilter, ['ready_partial', 'partial_dispatch', 'partial_pending'])) {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'READY_PARTIAL');
    $completedOrdersList = collect([]);
  } elseif ($dispatchFilter === 'not_ready') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => ($o['readiness'] ?? '') === 'NOT_READY');
    $completedOrdersList = collect([]);
  } elseif ($dispatchFilter === 'pending') {
    $pendingOrdersList = $pendingOrdersList->filter(fn($o) => (float)$o['dispatchedQty'] == 0);
    $completedOrdersList = collect([]);
  }

  // Stock items list with stage
  $rawList = collect($pageData['rawStock'] ?? [])->map(fn($s) => (object)array_merge((array)$s, ['stage' => 'RAW']));
  $semiList = collect($pageData['semiStock'] ?? [])->map(fn($s) => (object)array_merge((array)$s, ['stage' => 'SEMI']));
  $finishedList = collect($pageData['finishedStock'] ?? [])->map(fn($s) => (object)array_merge((array)$s, ['stage' => 'FINISHED']));
  $packagingList = collect($pageData['packagingStock'] ?? [])->map(fn($s) => (object)array_merge((array)$s, ['stage' => 'PACKAGING']));
  $allStockList = $rawList->concat($semiList)->concat($finishedList)->concat($packagingList);
  $totalStockItemsCount = $allStockList->count();
@endphp

<style>
  .main-nav-tab {
    padding: 0.65rem 1.4rem;
    font-size: 0.95rem;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }
  .main-nav-tab.active {
    background: var(--primary, #D88A00) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(216, 138, 0, 0.35);
  }
  .main-nav-tab:not(.active) {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted, #9ca3af);
    border: 1px solid var(--glass-border, rgba(255, 255, 255, 0.08));
  }
  .main-nav-tab:not(.active):hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-main, #ffffff);
  }

  .stock-subtab-btn {
    padding: 0.45rem 1rem;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 20px;
    text-decoration: none;
    border: 1px solid var(--glass-border, rgba(255, 255, 255, 0.1));
    background: rgba(255, 255, 255, 0.03);
    color: var(--text-muted, #9ca3af);
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .stock-subtab-btn.active {
    background: var(--primary, #D88A00) !important;
    color: #ffffff !important;
    border-color: var(--primary, #D88A00) !important;
    font-weight: 700;
  }
  .stock-subtab-btn:hover:not(.active) {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-main, #ffffff);
  }

  .stock-grid-card {
    background: var(--card-bg, rgba(255, 255, 255, 0.03));
    border: 1px solid var(--glass-border, rgba(255, 255, 255, 0.08));
    border-radius: 10px;
    padding: 0.9rem 1rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.15s ease, border-color 0.15s ease;
  }
  .stock-grid-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.2);
  }

  @media (max-width: 720px) {
    .dispatch-item-badges {
      grid-template-columns: repeat(3, 1fr) !important;
      width: 100% !important;
    }
  }
</style>

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Dispatches Dashboard</h2>
  <div>
    <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" style="width:auto; padding:0.5rem 1rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
      <span>🚀</span> <span>Create Dispatch</span>
    </a>
  </div>
</div>

<!-- Main Tabs Navigation -->
<div class="tabs main-tabs" style="display:flex; gap:10px; margin-top:1rem; margin-bottom:1.5rem; border-bottom:1px solid var(--glass-border, rgba(255, 255, 255, 0.08)); padding-bottom:12px; flex-wrap:wrap;">
  <button type="button" id="tab-btn-orders" class="main-nav-tab {{ $mainTab === 'orders' ? 'active' : '' }}" onclick="switchMainTab('orders')">
    <span>📦 Order Details</span>
    <span class="badge" style="background:rgba(0,0,0,0.25); color:#fff; font-size:0.75rem; padding:2px 8px; border-radius:10px;">{{ count($pageData['pendingOrders'] ?? []) }}</span>
  </button>
  <button type="button" id="tab-btn-stock" class="main-nav-tab {{ $mainTab === 'stock' ? 'active' : '' }}" onclick="switchMainTab('stock')">
    <span>📊 Stock Quantities</span>
    <span class="badge" style="background:rgba(0,0,0,0.25); color:#fff; font-size:0.75rem; padding:2px 8px; border-radius:10px;">{{ $totalStockItemsCount }}</span>
  </button>
</div>

<!-- ========================================== -->
<!-- 1. ORDER DETAILS TAB CONTENT               -->
<!-- ========================================== -->
<div id="content-main-orders" style="{{ $mainTab === 'orders' ? 'display:block;' : 'display:none;' }}">
  <!-- Orders Sub-filters (Pending / Completed and Scenarios) -->
  <div class="tabs" style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div style="display:flex; gap:8px;">
      <a class="tab-btn {{ $orderTab==='pending'?'active':'' }}" href="?main_tab=orders&tab=pending&dispatch_filter={{ $dispatchFilter }}" style="text-decoration:none;">
        Pending Orders ({{ $pendingOrdersList->count() }})
      </a>
      <a class="tab-btn {{ $orderTab==='completed'?'active':'' }}" href="?main_tab=orders&tab=completed&dispatch_filter={{ $dispatchFilter }}" style="text-decoration:none;">
        Completed Orders ({{ $completedOrdersList->count() }})
      </a>
    </div>

    @if($orderTab === 'pending')
      <form method="GET" action="" style="display:flex; align-items:center; gap:8px; margin:0;">
        <input type="hidden" name="main_tab" value="orders">
        <input type="hidden" name="tab" value="pending">
        <select name="dispatch_filter" onchange="this.form.submit()" style="padding:0.4rem 0.8rem; font-size:0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.2); background:#1f2937; color:#fff; cursor:pointer;">
          <option value="all" {{ $dispatchFilter === 'all' ? 'selected' : '' }}>All Scenarios</option>
          <option value="ready_dispatch" {{ $dispatchFilter === 'ready_dispatch' ? 'selected' : '' }}>Ready to Dispatch</option>
          <option value="ready_partial" {{ in_array($dispatchFilter, ['ready_partial', 'partial_dispatch', 'partial_pending']) ? 'selected' : '' }}>Ready to Partial Dispatch</option>
          <option value="not_ready" {{ $dispatchFilter === 'not_ready' ? 'selected' : '' }}>Not Ready</option>
          <option value="done" {{ $dispatchFilter === 'done' ? 'selected' : '' }}>Fully Dispatched (Completed)</option>
        </select>
      </form>
    @endif
  </div>

  <!-- Orders List -->
  <div style="display:flex; flex-direction:column; gap:12px;">
    @if($orderTab === 'pending')
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
            $progressColor = '#16a34a';
          } elseif ($readiness === 'READY_PARTIAL') {
            $readinessLabel = 'READY TO PARTIAL DISPATCH';
            $readinessBg = '#f59e0b';
            $readinessFg = '#ffffff';
            $progressColor = '#f59e0b';
          } else {
            $readinessLabel = 'NOT READY';
            $readinessBg = '#dc2626';
            $readinessFg = '#ffffff';
            $progressColor = '#dc2626';
          }

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
        <div class="card" style="border-left: 4px solid {{ $progressColor }}; background:rgba(255,255,255,0.02); transition: transform 0.2s; margin-bottom: 0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06));">
          <!-- Clickable Header Row -->
          <div onclick="toggleHomeAccordion('home-acc-{{ $o['id'] }}', this)" style="cursor:pointer; padding:1.1rem; user-select:none;">
            <div class="flex-between mb-1" style="align-items:flex-start; gap:10px;">
              <div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                  <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
                  <span class="badge" style="font-size:0.65rem; background:{{ $statusBadgeBg }}; color:{{ $statusBadgeFg }}; padding:3px 8px; border-radius:4px; font-weight:700;">{{ $statusBadgeLabel }}</span>
                </div>
                
                <!-- Expected Delivery Date & Sales Person Highlights -->
                <div style="font-size:0.83rem; color:var(--text-muted); margin-top:4px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                  <span><strong>Sales By:</strong> <span style="color:var(--primary-light, #F4B400); font-weight:600;">{{ $o['salesPerson'] ?? 'N/A' }}</span></span>
                  <span>•</span>
                  <span><strong>Expected Delivery Date:</strong> <strong style="color:var(--text-main, #ffffff); font-size:0.88rem;">{{ $o['dueDate'] ?? 'Not Specified' }}</strong></span>
                </div>
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
              <strong>Customer:</strong> {{ $o['companyName'] ?? 'N/A' }} <br>
              <strong>Transport:</strong> {{ $o['transporterName'] ?? 'N/A' }}
              @if(!empty($o['notes']))
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
            <!-- Key Details Bar -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin-bottom:12px; padding:10px; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Expected Delivery Date</div>
                <div style="font-size:0.95rem; font-weight:700; color:var(--text-main, #fff);">{{ $o['dueDate'] ?? 'Not Specified' }}</div>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Sales By</div>
                <div style="font-size:0.95rem; font-weight:600; color:var(--primary-light, #F4B400);">{{ $o['salesPerson'] ?? 'N/A' }}</div>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Order Date</div>
                <div style="font-size:0.95rem; font-weight:500;">{{ \Carbon\Carbon::parse($o['date'])->format('d-m-Y, h:i A') }}</div>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Total Quantity</div>
                <div style="font-size:0.95rem; font-weight:700; color:var(--primary, #D88A00);">{{ number_format($totalQty, 2) }} kg</div>
              </div>
            </div>

            @if(!empty($o['items']) && count($o['items']) > 0)
              <div style="background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00);">
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Order Items & Dispatch Status</div>
                @foreach($o['items'] as $item)
                  @php
                    $rawName = $item['rawProductName'] ?? $item['productName'] ?? 'Unknown';
                    $pName = trim(preg_replace('/\s*\((FG|SEMI|RAW|FINISHED|PM|PKG)\)$/i', '', $rawName));
                    $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? trim($item['grade']) : '';
                    if ($gName) {
                      $pName = trim(preg_replace('/\s+' . preg_quote($gName, '/') . '$/i', '', $pName));
                    }
                    $rawType = strtoupper((string)($item['productType'] ?? 'FINISHED'));
                    $tName = ($rawType === 'FINISHED' || $rawType === 'FG') ? 'FG' : ($rawType === 'SEMI' ? 'SEMI' : ($rawType === 'RAW' ? 'RAW' : ($rawType === 'PACKAGING' ? 'PM' : $rawType)));
                    $tot = (float)($item['quantity'] ?? 0);
                    $disp = (float)($item['dispatchedQty'] ?? 0);
                    $rem = (float)($item['remainingQty'] ?? 0);
                    $fmtQty = fn($val) => (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' kg';
                  @endphp
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                    <div style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                      <span style="font-weight:600; color:var(--text-main, #fff);">{{ $pName }}</span>
                      @if($gName)
                        <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                      @endif
                      <span style="color:var(--text-muted, #9ca3af); font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                    </div>
                    <div class="dispatch-item-badges" style="display:grid; grid-template-columns:135px 145px 145px; gap:8px; align-items:center; flex-shrink:0;">
                      <span style="background:rgba(255,255,255,0.08); padding:4px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.15); font-weight:600; color:var(--text-main, #fff); width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="color:var(--text-muted, #9ca3af); font-size:0.72rem; font-weight:700;">ORDER:</span>
                        <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                      </span>
                      <span style="background:rgba(22,163,74,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.3); font-weight:700; color:#16a34a; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">DISPATCHED:</span>
                        <strong>{{ $fmtQty($disp) }}</strong>
                      </span>
                      <span style="background:rgba(239,68,68,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.3); font-weight:700; color:#ef4444; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">PENDING:</span>
                        <strong>{{ $fmtQty($rem) }}</strong>
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div style="font-size:0.8rem; color:var(--text-muted);">No items details found for this order.</div>
            @endif

            <div style="display:flex; gap:10px; margin-top:12px; justify-content:flex-end;">
              <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" onclick="localStorage.setItem('auto_dispatch_id', '{{ $o['id'] }}');" style="width:auto; padding:0.4rem 1rem; font-size:0.8rem; text-decoration:none;">
                📦 Open in Dispatch Action &rarr;
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="card" style="padding:2.5rem; text-align:center; color:var(--text-muted); border-radius:12px;">
          <div style="font-size:1.8rem; margin-bottom:6px;">✨</div>
          <div style="font-weight:600; font-size:1rem; color:var(--text-main);">No pending dispatches found.</div>
          <div style="font-size:0.82rem; margin-top:4px;">All scheduled orders are up to date!</div>
        </div>
      @endforelse
    @else
      @forelse($completedOrdersList as $o)
        <div class="card" style="border-left: 4px solid var(--secondary, #16a34a); background:rgba(255,255,255,0.02); margin-bottom: 0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06));">
          <div onclick="toggleHomeAccordion('home-acc-comp-{{ $o['id'] }}', this)" style="cursor:pointer; padding:1.1rem; user-select:none;">
            <div class="flex-between mb-1" style="align-items:flex-start; gap:10px;">
              <div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                  <span style="font-weight:bold; font-size:1.1rem; color:#fff;">Order #{{ strtoupper((string)$o['id']) }}</span>
                  <span class="badge badge-done" style="font-size:0.7rem; padding:4px 8px; background:#16a34a; color:#fff; font-weight:700;">COMPLETED</span>
                </div>
                <!-- Expected Delivery Date & Sales Person Highlights -->
                <div style="font-size:0.83rem; color:var(--text-muted); margin-top:4px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                  <span><strong>Sales By:</strong> <span style="color:var(--primary-light, #F4B400); font-weight:600;">{{ $o['salesPerson'] ?? 'N/A' }}</span></span>
                  <span>•</span>
                  <span><strong>Expected Delivery Date:</strong> <strong style="color:var(--text-main, #ffffff); font-size:0.88rem;">{{ $o['dueDate'] ?? 'Not Specified' }}</strong></span>
                </div>
              </div>
              <div style="display:flex; align-items:center; gap:10px;">
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
              <strong>Date Closed:</strong> {{ \Carbon\Carbon::parse($o['date'])->format('d-m-Y, h:i A') }} <br>
              @if(!empty($o['notes']))
                <div style="margin-top:8px; font-style:italic;">Notes: {{ $o['notes'] }}</div>
              @endif
            </div>
          </div>

          <div id="home-acc-comp-{{ $o['id'] }}" class="home-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px; margin-bottom:12px; padding:10px; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Expected Delivery Date</div>
                <div style="font-size:0.95rem; font-weight:700; color:var(--text-main, #fff);">{{ $o['dueDate'] ?? 'Not Specified' }}</div>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Sales By</div>
                <div style="font-size:0.95rem; font-weight:600; color:var(--primary-light, #F4B400);">{{ $o['salesPerson'] ?? 'N/A' }}</div>
              </div>
              <div>
                <div style="font-size:0.72rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Order Date</div>
                <div style="font-size:0.95rem; font-weight:500;">{{ \Carbon\Carbon::parse($o['date'])->format('d-m-Y, h:i A') }}</div>
              </div>
            </div>

            @if(!empty($o['items']) && count($o['items']) > 0)
              <div style="background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--secondary, #16a34a);">
                <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Order Items Summary</div>
                @foreach($o['items'] as $item)
                  @php
                    $rawName = $item['rawProductName'] ?? $item['productName'] ?? 'Unknown';
                    $pName = trim(preg_replace('/\s*\((FG|SEMI|RAW|FINISHED|PM|PKG)\)$/i', '', $rawName));
                    $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? trim($item['grade']) : '';
                    if ($gName) {
                      $pName = trim(preg_replace('/\s+' . preg_quote($gName, '/') . '$/i', '', $pName));
                    }
                    $rawType = strtoupper((string)($item['productType'] ?? 'FINISHED'));
                    $tName = ($rawType === 'FINISHED' || $rawType === 'FG') ? 'FG' : ($rawType === 'SEMI' ? 'SEMI' : ($rawType === 'RAW' ? 'RAW' : ($rawType === 'PACKAGING' ? 'PM' : $rawType)));
                    $tot = (float)($item['quantity'] ?? 0);
                    $disp = (float)($item['dispatchedQty'] ?? 0);
                    $rem = (float)($item['remainingQty'] ?? 0);
                    $fmtQty = fn($val) => (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' kg';
                  @endphp
                  <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                    <div style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                      <span style="font-weight:600; color:var(--text-main, #fff);">{{ $pName }}</span>
                      @if($gName)
                        <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                      @endif
                      <span style="color:var(--text-muted, #9ca3af); font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                    </div>
                    <div class="dispatch-item-badges" style="display:grid; grid-template-columns:135px 145px 145px; gap:8px; align-items:center; flex-shrink:0;">
                      <span style="background:rgba(255,255,255,0.08); padding:4px 8px; border-radius:6px; border:1px solid rgba(255,255,255,0.15); font-weight:600; color:var(--text-main, #fff); width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="color:var(--text-muted, #9ca3af); font-size:0.72rem; font-weight:700;">ORDER:</span>
                        <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                      </span>
                      <span style="background:rgba(22,163,74,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.3); font-weight:700; color:#16a34a; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">DISPATCHED:</span>
                        <strong>{{ $fmtQty($disp) }}</strong>
                      </span>
                      <span style="background:rgba(239,68,68,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.3); font-weight:700; color:#ef4444; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                        <span style="font-size:0.72rem;">PENDING:</span>
                        <strong>{{ $fmtQty($rem) }}</strong>
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @empty
        <div class="card" style="padding:2.5rem; text-align:center; color:var(--text-muted); border-radius:12px;">
          No completed dispatches found.
        </div>
      @endforelse
    @endif
  </div>
</div>

<!-- ========================================== -->
<!-- 2. STOCK QUANTITIES TAB CONTENT            -->
<!-- ========================================== -->
<div id="content-main-stock" style="{{ $mainTab === 'stock' ? 'display:block;' : 'display:none;' }}">
  <!-- Stock Sub-Tabs & Instant Search Filter -->
  <div class="card mb-2" style="background:rgba(255,255,255,0.02); border:1px solid var(--glass-border, rgba(255,255,255,0.06)); padding:1rem; border-radius:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
      <!-- Sub-tabs: All, Raw, Semi, Finished, Packaging -->
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <button type="button" class="stock-subtab-btn {{ $stockSubTab === 'all' ? 'active' : '' }}" onclick="filterStockStage('ALL', this)">
          <span>🌐 All</span>
          <span style="opacity:0.85;">({{ $totalStockItemsCount }})</span>
        </button>
        <button type="button" class="stock-subtab-btn {{ $stockSubTab === 'raw' ? 'active' : '' }}" onclick="filterStockStage('RAW', this)">
          <span>🌿 Raw Material</span>
          <span style="opacity:0.85;">({{ $rawList->count() }})</span>
        </button>
        <button type="button" class="stock-subtab-btn {{ $stockSubTab === 'semi' ? 'active' : '' }}" onclick="filterStockStage('SEMI', this)">
          <span>⚗️ Semi-Finished</span>
          <span style="opacity:0.85;">({{ $semiList->count() }})</span>
        </button>
        <button type="button" class="stock-subtab-btn {{ $stockSubTab === 'finished' ? 'active' : '' }}" onclick="filterStockStage('FINISHED', this)">
          <span>✅ FG (Finished)</span>
          <span style="opacity:0.85;">({{ $finishedList->count() }})</span>
        </button>
        <button type="button" class="stock-subtab-btn {{ $stockSubTab === 'packaging' ? 'active' : '' }}" onclick="filterStockStage('PACKAGING', this)">
          <span>📦 PM (Packaging)</span>
          <span style="opacity:0.85;">({{ $packagingList->count() }})</span>
        </button>
      </div>

      <!-- Live Search Box -->
      <div style="min-width:240px; flex:1; max-width:380px;">
        <input type="text" id="dispatch-stock-search" placeholder="🔍 Search product or grade..." oninput="filterStockList()" style="width:100%; padding:0.5rem 0.9rem; font-size:0.85rem; border-radius:8px; border:1px solid var(--border-soft, rgba(255,255,255,0.15)); background:var(--input-bg, rgba(255,255,255,0.05)); color:var(--text-main, #ffffff);">
      </div>
    </div>
  </div>

  <!-- Stock Items Grid -->
  <div id="dispatch-stock-container" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px;">
    @forelse($allStockList as $s)
      @php
        $stageBadgeColor = match($s->stage) {
          'RAW' => '#f59e0b',
          'SEMI' => '#3b82f6',
          'FINISHED' => '#10b981',
          'PACKAGING' => '#0284c7',
          default => '#8b5cf6',
        };
        $stageLabel = match($s->stage) {
          'RAW' => 'RAW',
          'SEMI' => 'SEMI',
          'FINISHED' => 'FG',
          'PACKAGING' => 'PM',
          default => $s->stage,
        };
        $hasGrade = !empty($s->grade) && !in_array(strtoupper(trim($s->grade)), ['NONE', 'N/A', 'NA', 'N / A']);
      @endphp
      <div class="stock-grid-card stock-item-card" data-stage="{{ $s->stage }}" data-name="{{ strtolower($s->name . ' ' . ($s->grade ?? '') . ' ' . $stageLabel) }}">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:6px;">
            <span class="badge" style="background:{{ $stageBadgeColor }}; color:#ffffff; font-size:0.7rem; padding:2px 8px; border-radius:6px; font-weight:700;">
              {{ $stageLabel }}
            </span>
            @if($hasGrade)
              <span class="badge" style="background:rgba(255,255,255,0.08); color:var(--primary-light, #F4B400); font-size:0.72rem; border:1px solid rgba(255,255,255,0.12); padding:2px 6px; border-radius:4px; font-weight:700;">
                {{ $s->grade }}
              </span>
            @endif
          </div>
          <div style="font-weight:700; font-size:0.95rem; color:var(--text-main, #ffffff); line-height:1.3; margin-bottom:8px;">
            {{ $s->name }}
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:flex-end; padding-top:8px; border-top:1px solid rgba(255,255,255,0.06); margin-top:6px;">
          <span style="font-size:0.75rem; color:var(--text-muted);">Available Qty:</span>
          <div style="text-align:right;">
            <span style="font-size:1.15rem; font-weight:800; color:var(--primary, #D88A00);">
              {{ number_format((float)$s->quantity, 2) }}
            </span>
            <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600; margin-left:2px;">
              {{ $s->unit ?? 'kg' }}
            </span>
          </div>
        </div>
      </div>
    @empty
      <div style="grid-column:1/-1; padding:3rem; text-align:center; color:var(--text-muted); background:rgba(255,255,255,0.02); border-radius:12px;">
        No stock quantities available.
      </div>
    @endforelse
  </div>

  <div id="dispatch-stock-empty" style="display:none; padding:3rem; text-align:center; color:var(--text-muted); background:rgba(255,255,255,0.02); border-radius:12px; margin-top:10px;">
    No stock items match your search or filter.
  </div>
</div>

<script>
  let activeStockStage = 'ALL';

  function switchMainTab(tab) {
    const ordersBtn = document.getElementById('tab-btn-orders');
    const stockBtn = document.getElementById('tab-btn-stock');
    const ordersContent = document.getElementById('content-main-orders');
    const stockContent = document.getElementById('content-main-stock');

    if (tab === 'stock') {
      ordersBtn.classList.remove('active');
      stockBtn.classList.add('active');
      ordersContent.style.display = 'none';
      stockContent.style.display = 'block';
    } else {
      stockBtn.classList.remove('active');
      ordersBtn.classList.add('active');
      stockContent.style.display = 'none';
      ordersContent.style.display = 'block';
    }

    // Update query param in browser history without reload
    const url = new URL(window.location);
    url.searchParams.set('main_tab', tab);
    window.history.replaceState({}, '', url);
  }

  function filterStockStage(stage, btnEl) {
    activeStockStage = stage;
    document.querySelectorAll('.stock-subtab-btn').forEach(btn => btn.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    filterStockList();
  }

  function filterStockList() {
    const searchInput = document.getElementById('dispatch-stock-search');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const cards = document.querySelectorAll('.stock-item-card');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardStage = card.getAttribute('data-stage') || '';
      const cardName = card.getAttribute('data-name') || '';

      const matchesStage = (activeStockStage === 'ALL' || cardStage === activeStockStage);
      const matchesSearch = (!query || cardName.includes(query));

      if (matchesStage && matchesSearch) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    const emptyBox = document.getElementById('dispatch-stock-empty');
    if (emptyBox) {
      emptyBox.style.display = visibleCount === 0 ? 'block' : 'none';
    }
  }

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
