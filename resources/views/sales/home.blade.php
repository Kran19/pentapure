@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<style>
.clickable-card {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.clickable-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
  $todayDate = now()->timezone('Asia/Kolkata')->format('Y-m-d');

  $pendingList = collect($pageData['pendingOrdersList'] ?? [])->filter(function($o) use ($todayDate) {
    if (($o['status'] ?? '') === 'CANCELLED') return false;
    $rawDue = $o['rawDueDate'] ?? null;
    return $rawDue && \Carbon\Carbon::parse($rawDue, 'Asia/Kolkata')->format('Y-m-d') === $todayDate;
  })->values();

  $partialList = collect($pageData['partialOrdersList'] ?? [])->filter(function($o) use ($todayDate) {
    if (($o['status'] ?? '') === 'CANCELLED') return false;
    $rawDue = $o['rawDueDate'] ?? null;
    return $rawDue && \Carbon\Carbon::parse($rawDue, 'Asia/Kolkata')->format('Y-m-d') === $todayDate;
  })->values();

  $dueTodayList = collect($pageData['dueTodayOrders'] ?? [])->filter(function($o) use ($todayDate) {
    if (($o['status'] ?? '') === 'CANCELLED') return false;
    $rawDue = $o['rawDueDate'] ?? null;
    return $rawDue && \Carbon\Carbon::parse($rawDue, 'Asia/Kolkata')->format('Y-m-d') === $todayDate;
  })->values();

  $allOpenList = $pendingList->concat($partialList)->unique('id')->values();

@endphp

<!-- Active Orders Section -->
<div class="card mt-2" style="border:1px solid var(--glass-border, rgba(255,255,255,0.08));">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.06));">
    <div style="display:flex; align-items:center; gap:8px;">
      <h3 style="margin:0; font-size:1.15rem; font-weight:700; display:flex; align-items:center; gap:8px;">
        <span>📦 Orders Due Today</span>
      </h3>
      <span style="font-size:0.82rem; color:var(--text-muted);">(Showing orders due today: <strong>{{ \Carbon\Carbon::now('Asia/Kolkata')->format('d-m-Y') }}</strong>)</span>
    </div>
    <div>
      <a class="btn btn-sm btn-secondary" href="{{ url(request()->segment(1) . '/history') }}" style="width:auto; text-decoration:none; font-size:0.82rem; padding:0.4rem 0.9rem;">
        View in History &rarr;
      </a>
    </div>
  </div>

  <!-- Orders Due Today List -->
  <div style="display:flex; flex-direction:column; gap:12px;">
    @forelse($allOpenList as $o)
      @include('sales.partials.order_card', ['dOrder' => $o])
    @empty
      <div class="empty-tab-state">
        <div style="font-size:1.4rem; margin-bottom:4px;">✨</div>
        <div style="font-weight:600; font-size:0.95rem; color:var(--text-main);">No open orders due today</div>
        <div style="font-size:0.82rem; margin-top:3px;">All scheduled orders for today ({{ \Carbon\Carbon::now('Asia/Kolkata')->format('d-m-Y') }}) are up to date!</div>
        <div style="margin-top:8px;">
          <a href="{{ url(request()->segment(1) . '/history') }}" style="font-size:0.82rem; color:var(--primary, #D88A00); font-weight:600; text-decoration:none;">
            View other orders in Sales History &rarr;
          </a>
        </div>
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
function toggleSalesOrderDetails(id, headerEl) {
  const content = document.getElementById(id);
  if (!content) return;
  const isHidden = content.style.display === 'none' || !content.style.display;
  const card = headerEl ? headerEl.closest('.sales-order-card') : document.getElementById(id).closest('.sales-order-card');
  const chevron = card ? card.querySelector('.dropdown-chevron') : null;
  const label = card ? card.querySelector('.dropdown-label') : null;
  
  if (isHidden) {
    content.style.display = 'block';
    if (chevron) chevron.style.transform = 'rotate(180deg)';
    if (label) label.textContent = 'Hide Detail';
  } else {
    content.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
    if (label) label.textContent = 'View Detail';
  }
}
window.toggleSalesOrderDetails = toggleSalesOrderDetails;
</script>
@endsection
