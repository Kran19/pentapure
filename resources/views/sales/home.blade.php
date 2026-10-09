@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<style>
.tab-btn-pill {
  padding: 7px 16px;
  font-size: 0.82rem;
  font-weight: 700;
  border-radius: 20px;
  border: 1px solid var(--border-soft, rgba(0,0,0,0.12));
  background: var(--card-bg, rgba(255,255,255,0.04));
  color: var(--text-main, #333);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  transition: all 0.2s ease;
  user-select: none;
}
.tab-btn-pill:hover {
  background: var(--bg-hover, rgba(0,0,0,0.06));
}
.tab-btn-pill.active {
  background: var(--primary, #D88A00) !important;
  color: #ffffff !important;
  border-color: var(--primary, #D88A00) !important;
  box-shadow: 0 2px 8px rgba(216, 138, 0, 0.3);
}
.tab-btn-pill .pill-count {
  font-size: 0.72rem;
  padding: 1px 7px;
  border-radius: 10px;
  background: rgba(0,0,0,0.08);
  color: inherit;
  font-weight: 800;
}
.tab-btn-pill.active .pill-count {
  background: rgba(255,255,255,0.25);
  color: #ffffff;
}
.sales-tab-pane {
  display: none;
}
.sales-tab-pane.active {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.empty-tab-state {
  padding: 2.2rem 1rem;
  text-align: center;
  color: var(--text-muted);
  background: rgba(0,0,0,0.01);
  border-radius: 8px;
  border: 1px dashed var(--glass-border, rgba(255,255,255,0.1));
}
</style>

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">Sales Dashboard</h2>
  <div style="display:flex; gap:8px;">
    <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" style="width:auto; padding:0.5rem 1rem; text-decoration:none;">+ Create New Order</a>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="dashboard-grid" style="margin-top:1rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history') }}" style="text-decoration:none;">
    <div style="color:var(--primary-light)">Total Orders</div>
    <div class="stat-value">{{ $pageData['stats']['totalOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?status=PENDING') }}" style="text-decoration:none; border-top:3px solid #3b82f6;">
    <div style="color:#3b82f6; font-weight:600;">Pending Orders</div>
    <div class="stat-value" style="color:var(--text-main, #111827);">{{ $pageData['stats']['pendingOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?status=PARTIAL') }}" style="text-decoration:none; border-top:3px solid #f59e0b;">
    <div style="color:#f59e0b; font-weight:600;">Partial Orders</div>
    <div class="stat-value" style="color:var(--text-main, #111827);">{{ $pageData['stats']['partialOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?status=DONE') }}" style="text-decoration:none; border-top:3px solid #16a34a;">
    <div style="color:#16a34a; font-weight:600;">Dispatched Orders</div>
    <div class="stat-value">{{ $pageData['stats']['dispatchedOrders'] ?? 0 }}</div>
  </a>
  <a class="stat-card clickable-card" href="{{ url(request()->segment(1) . '/history?due=today') }}" style="text-decoration:none; border-top:3px solid var(--primary, #D88A00);">
    <div style="color:var(--primary, #D88A00); font-weight:600;">📅 Due Today Orders</div>
    <div class="stat-value" style="color:var(--text-main, #111827);">{{ $pageData['stats']['dueTodayOrdersCount'] ?? count($pageData['dueTodayOrders'] ?? []) }}</div>
  </a>
</div>

@php
  $pendingList = collect($pageData['pendingOrdersList'] ?? []);
  $partialList = collect($pageData['partialOrdersList'] ?? []);
  $dueTodayList = collect($pageData['dueTodayOrders'] ?? []);
  $allOpenList = $pendingList->concat($partialList);

  $initialTab = request('tab', 'all');
  if (!in_array($initialTab, ['all', 'pending', 'partial', 'due_today'])) {
    $initialTab = 'all';
  }
@endphp

<!-- Active Orders Section with Tabs for Pending, Partial, and Due Today -->
<div class="card mt-2" style="border:1px solid var(--glass-border, rgba(255,255,255,0.08));">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.06));">
    <div style="display:flex; align-items:center; gap:8px;">
      <h3 style="margin:0; font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:8px;">
        <span>📦 Active Sales Orders</span>
      </h3>
      <span style="font-size:0.82rem; color:var(--text-muted);">(Pending &amp; Partial Orders)</span>
    </div>
    <div>
      <a class="btn btn-sm btn-secondary" href="{{ url(request()->segment(1) . '/history') }}" style="width:auto; text-decoration:none; font-size:0.82rem; padding:0.4rem 0.9rem;">
        View in History &rarr;
      </a>
    </div>
  </div>

  <!-- Interactive Filter Tabs -->
  <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:1.2rem;">
    <button type="button" class="tab-btn-pill {{ $initialTab === 'all' ? 'active' : '' }}" onclick="switchSalesTab('all')" id="btn-tab-all">
      <span>📋 All Open</span>
      <span class="pill-count">{{ $allOpenList->count() }}</span>
    </button>
    <button type="button" class="tab-btn-pill {{ $initialTab === 'pending' ? 'active' : '' }}" onclick="switchSalesTab('pending')" id="btn-tab-pending">
      <span>⏳ Pending</span>
      <span class="pill-count">{{ $pendingList->count() }}</span>
    </button>
    <button type="button" class="tab-btn-pill {{ $initialTab === 'partial' ? 'active' : '' }}" onclick="switchSalesTab('partial')" id="btn-tab-partial">
      <span>📦 Partial</span>
      <span class="pill-count">{{ $partialList->count() }}</span>
    </button>
    <button type="button" class="tab-btn-pill {{ $initialTab === 'due_today' ? 'active' : '' }}" onclick="switchSalesTab('due_today')" id="btn-tab-due_today">
      <span>📅 Due Today</span>
      <span class="pill-count">{{ $dueTodayList->count() }}</span>
    </button>
  </div>

  <!-- Tab Pane: All Open Orders (Pending + Partial) -->
  <div id="pane-sales-all" class="sales-tab-pane {{ $initialTab === 'all' ? 'active' : '' }}">
    @forelse($allOpenList as $o)
      @include('sales.partials.order_card', ['dOrder' => $o])
    @empty
      <div class="empty-tab-state">
        <div style="font-size:1.4rem; margin-bottom:4px;">✨</div>
        <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No open orders</div>
        <div style="font-size:0.82rem; margin-top:3px;">All sales orders are currently dispatched or closed!</div>
      </div>
    @endforelse
  </div>

  <!-- Tab Pane: Pending Orders -->
  <div id="pane-sales-pending" class="sales-tab-pane {{ $initialTab === 'pending' ? 'active' : '' }}">
    @forelse($pendingList as $o)
      @include('sales.partials.order_card', ['dOrder' => $o])
    @empty
      <div class="empty-tab-state">
        <div style="font-size:1.4rem; margin-bottom:4px;">✨</div>
        <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No pending orders</div>
        <div style="font-size:0.82rem; margin-top:3px;">There are no unassigned orders waiting for dispatch!</div>
      </div>
    @endforelse
  </div>

  <!-- Tab Pane: Partial Orders -->
  <div id="pane-sales-partial" class="sales-tab-pane {{ $initialTab === 'partial' ? 'active' : '' }}">
    @forelse($partialList as $o)
      @include('sales.partials.order_card', ['dOrder' => $o])
    @empty
      <div class="empty-tab-state">
        <div style="font-size:1.4rem; margin-bottom:4px;">📦</div>
        <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No partial orders</div>
        <div style="font-size:0.82rem; margin-top:3px;">There are no orders in partially dispatched state!</div>
      </div>
    @endforelse
  </div>

  <!-- Tab Pane: Due Today Orders -->
  <div id="pane-sales-due_today" class="sales-tab-pane {{ $initialTab === 'due_today' ? 'active' : '' }}">
    @forelse($dueTodayList as $o)
      @include('sales.partials.order_card', ['dOrder' => $o])
    @empty
      <div class="empty-tab-state">
        <div style="font-size:1.4rem; margin-bottom:4px;">✨</div>
        <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No orders due today</div>
        <div style="font-size:0.82rem; margin-top:3px;">All scheduled orders are up to date!</div>
      </div>
    @endforelse
  </div>
</div>

<div class="card mt-2">
  <div class="card-title">Quick Links</div>
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url(request()->segment(1) . '/action') }}">Create New Order</a>
    <a class="btn btn-secondary" style="text-align:center; text-decoration:none;" href="{{ url(request()->segment(1) . '/history') }}">View History</a>
  </div>
</div>

<script>
function switchSalesTab(tabName) {
  document.querySelectorAll('.tab-btn-pill').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.sales-tab-pane').forEach(pane => pane.classList.remove('active'));

  const activeBtn = document.getElementById('btn-tab-' + tabName);
  const activePane = document.getElementById('pane-sales-' + tabName);
  if (activeBtn) activeBtn.classList.add('active');
  if (activePane) activePane.classList.add('active');

  const url = new URL(window.location);
  url.searchParams.set('tab', tabName);
  window.history.replaceState({}, '', url);
}
</script>
@endsection
