@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'all');
  $startDate = request('start', '');
  $endDate = request('end', '');
  $companyId = request('company_id', '');
  $statusFilter = strtoupper(trim((string)request('status', 'ALL')));
  if (!$statusFilter || $statusFilter === '') $statusFilter = 'ALL';

  $filtered = collect($pageData['orders'] ?? []);

  // 1. Company Filter
  if ($companyId) {
    $filtered = $filtered->filter(function($o) use ($companyId) {
      return (string)($o['companyId'] ?? '') === (string)$companyId;
    });
  }

  // 2. Search Query Filter
  if ($q) {
    $filtered = $filtered->filter(function($o) use ($q) {
      $query = strtolower($q);
      $hasProduct = collect($o['items'] ?? [])->contains(function($item) use ($query) {
        return str_contains(strtolower($item['productName'] ?? ''), $query) ||
               str_contains(strtolower($item['rawProductName'] ?? ''), $query);
      });
      return str_contains(strtolower($o['companyName'] ?? ''), $query) ||
             str_contains(strtolower($o['salesPerson'] ?? ''), $query) ||
             str_contains(strtolower($o['transportName'] ?? ''), $query) ||
             str_contains(strtolower((string)$o['id']), $query) ||
             str_contains(strtolower((string)($o['orderId'] ?? '')), $query) ||
             $hasProduct;
    });
  }

  // 3. Date Range Filter
  if ($dateRange && $dateRange !== 'all') {
    $now = \Carbon\Carbon::now();
    $start = null;
    $end = \Carbon\Carbon::now();

    if ($dateRange === 'today') {
      $start = \Carbon\Carbon::today();
    } elseif ($dateRange === 'yesterday') {
      $start = \Carbon\Carbon::yesterday()->startOfDay();
      $end = \Carbon\Carbon::yesterday()->endOfDay();
    } elseif ($dateRange === 'this_week') {
      $start = \Carbon\Carbon::now()->startOfWeek();
    } elseif ($dateRange === 'last_week') {
      $start = \Carbon\Carbon::now()->subWeek()->startOfWeek();
      $end = \Carbon\Carbon::now()->subWeek()->endOfWeek();
    } elseif ($dateRange === 'this_month') {
      $start = \Carbon\Carbon::now()->startOfMonth();
    } elseif ($dateRange === 'last_month') {
      $start = \Carbon\Carbon::now()->subMonth()->startOfMonth();
      $end = \Carbon\Carbon::now()->subMonth()->endOfMonth();
    } elseif ($dateRange === 'custom' && $startDate && $endDate) {
      $start = \Carbon\Carbon::parse($startDate)->startOfDay();
      $end = \Carbon\Carbon::parse($endDate)->endOfDay();
    }

    if ($start) {
      $filtered = $filtered->filter(function($item) use ($start, $end) {
        $date = \Carbon\Carbon::parse($item['date']);
        return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
      });
    }
  }

  // Base list before applying status filter to calculate counts for tabs
  $baseOrders = $filtered;
  $statusCounts = [
    'ALL' => $baseOrders->count(),
    'PENDING' => $baseOrders->filter(function($o) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($o['dispatchStatus'] ?? $o['status'] ?? ''))));
      return in_array($st, ['PENDING', 'OPEN', 'UNASSIGNED']) && (float)($o['dispatchedQty'] ?? 0) == 0;
    })->count(),
    'PARTIAL' => $baseOrders->filter(function($o) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($o['dispatchStatus'] ?? $o['status'] ?? ''))));
      $isPart = in_array($st, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING']);
      $qtyPart = ((float)($o['dispatchedQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) > 0);
      $notDone = !in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']) && !((float)($o['totalQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) <= 0);
      return $notDone && ($isPart || $qtyPart);
    })->count(),
    'FULLY_DISPATCH' => $baseOrders->filter(function($o) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($o['dispatchStatus'] ?? $o['status'] ?? ''))));
      $isDone = in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']);
      $qtyDone = ((float)($o['totalQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) <= 0);
      return $isDone || $qtyDone;
    })->count(),
  ];

  // 4. Status Filter
  if ($statusFilter && $statusFilter !== 'ALL') {
    $filtered = $baseOrders->filter(function($o) use ($statusFilter) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($o['dispatchStatus'] ?? $o['status'] ?? ''))));
      $target = strtoupper(trim(str_replace('_', ' ', $statusFilter)));
      
      if (in_array($target, ['FULLY DISPATCHED', 'FULLY DISPATCH', 'DONE'])) {
        return in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']) ||
               ((float)($o['totalQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) <= 0);
      }
      if ($target === 'PARTIAL') {
        $notDone = !in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']) && !((float)($o['totalQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) <= 0);
        return $notDone && (in_array($st, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING']) || ((float)($o['dispatchedQty'] ?? 0) > 0 && (float)($o['remainingQty'] ?? 0) > 0));
      }
      if ($target === 'PENDING') {
        return in_array($st, ['PENDING', 'OPEN', 'UNASSIGNED']) && (float)($o['dispatchedQty'] ?? 0) == 0;
      }
      return str_contains($st, $target);
    });
  }

  // 5. Step-by-Step Priority Sorting: PENDING (1) -> PARTIAL PENDING (2) -> PARTIAL DISPATCH (3) -> FULLY DISPATCHED (4)
  $filtered = $filtered->sortBy(function($d) {
    $st = strtoupper(trim(str_replace('_', ' ', (string)($d['dispatchStatus'] ?? $d['status'] ?? ''))));
    $priority = 99;
    if (in_array($st, ['PENDING', 'OPEN', 'UNASSIGNED'])) {
      $priority = 1;
    } elseif ($st === 'PARTIAL PENDING') {
      $priority = 2;
    } elseif (in_array($st, ['PARTIAL', 'PARTIAL DISPATCH'])) {
      $priority = 3;
    } elseif (in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED'])) {
      $priority = 4;
    } elseif ($st === 'CANCELLED') {
      $priority = 5;
    }
    return sprintf('%02d_%012d', $priority, 999999999999 - strtotime($d['date']));
  });

  $page = request('page', 1);
  $perPage = 15;
  $total = $filtered->count();
  $totalPages = ceil($total / $perPage);
  $paginated = $filtered->slice(($page - 1) * $perPage, $perPage);
  $paginatedArray = $paginated->values()->toArray();
@endphp

@php
  $userSlug = in_array(request()->segment(1), ['admin', 'sub_admin', 'dispatch', 'sales', 'stock_manager'])
    ? request()->segment(1)
    : (session('auth_user')['login_slug'] ?? strtolower(session('auth_user')['role'] ?? 'dispatch'));
  $pdfUrl = route('history.pdf', ['user_slug' => $userSlug, 'panel' => 'dispatch']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&company_id=' . $companyId . '&status=' . $statusFilter . '&q=' . $q;
@endphp
<style>
.status-tabs-wrapper {
  display: inline-flex;
  align-items: center;
  background: #ffffff;
  padding: 4px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  gap: 4px;
  flex-wrap: wrap;
}
.status-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.45rem 0.85rem;
  border-radius: 7px;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  color: #475569;
  transition: all 0.2s ease;
  white-space: nowrap;
  text-transform: uppercase;
}
.status-tab-btn:hover:not(.active-all):not(.active-pending):not(.active-partial):not(.active-done) {
  color: #111827;
  background: #f1f5f9;
}
.status-tab-btn .tab-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
}

/* Active Tab Styles */
.status-tab-btn.active-all {
  background: var(--primary, #D88A00);
  color: #000000 !important;
  font-weight: 800;
}
.status-tab-btn.active-all .tab-badge {
  background: rgba(0, 0, 0, 0.2);
  border-color: transparent;
  color: #000000;
}

.status-tab-btn.active-pending {
  background: #ef4444;
  color: #ffffff !important;
  font-weight: 800;
}
.status-tab-btn.active-pending .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
  color: #ffffff;
}

.status-tab-btn.active-partial {
  background: #f59e0b;
  color: #ffffff !important;
  font-weight: 800;
}
.status-tab-btn.active-partial .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
  color: #ffffff;
}

.status-tab-btn.active-done {
  background: #16a34a;
  color: #ffffff !important;
  font-weight: 800;
}
.status-tab-btn.active-done .tab-badge {
  background: rgba(0, 0, 0, 0.22);
  border-color: transparent;
  color: #ffffff;
}

@media (max-width: 720px) {
  .dispatch-item-badges {
    grid-template-columns: repeat(3, 1fr) !important;
    width: 100% !important;
  }
  .status-tabs-wrapper {
    width: 100%;
    justify-content: center;
  }
}

.highlight-due-date {
  color: #dc2626 !important;
  font-weight: 800 !important;
  background: #fef2f2;
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid #fecaca;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.3px;
}
.highlight-due-date * {
  color: #dc2626 !important;
}
.dark-mode .highlight-due-date {
  color: #f87171 !important;
  background: rgba(220, 38, 38, 0.18);
  border-color: rgba(248, 113, 113, 0.45);
}
.dark-mode .highlight-due-date * {
  color: #f87171 !important;
}
</style>
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">📋 Order Report</h2>
  <button id="export-pdf-btn" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem;"
    onclick="app.exportHistoryPdf(this, '{{ $pdfUrl }}')">📄 Export PDF</button>
</div>

<form method="GET" action="" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:10px;">
  <input type="hidden" name="status" value="{{ $statusFilter }}">

  <!-- 2 Filter Boxes in 1 Line: Date Range & Company Name -->
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:center;">
    
    <!-- 1st: Date Range Filter -->
    <div>
      <select name="range" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
        <option value="all" {{ $dateRange==='all'?'selected':'' }}>UP TO DATE</option>
        <option value="custom" {{ $dateRange==='custom'?'selected':'' }}>CUSTOM RANGE</option>
      </select>
    </div>

    <!-- 2nd: Company Name Filter -->
    <div>
      <select name="company_id" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
        <option value="">ALL COMPANIES</option>
        @foreach($pageData['companies'] ?? [] as $comp)
          <option value="{{ $comp['id'] }}" {{ (string)$companyId === (string)$comp['id'] ? 'selected' : '' }}>
            {{ strtoupper($comp['name']) }}
          </option>
        @endforeach
      </select>
    </div>
  </div>

  @if($dateRange === 'custom')
    <div style="display:flex; gap:10px; align-items:center;">
      <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()"
        style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()"
        style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    </div>
  @endif

  <!-- Search Input Bar -->
  <div class="form-group" style="margin-bottom:0;">
    <input type="text" name="q" placeholder="SEARCH CUSTOMER, SALESPERSON, TRANSPORTER OR ORDER ID..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.65rem 0.9rem; font-size:0.9rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
  </div>
</form>

<!-- Status Tabs Filter Bar (matching screenshot: ALL, PENDING, PARTIAL, FULLY DISPATCHED) -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:1.2rem;">
  <div class="status-tabs-wrapper">
    <!-- All -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'ALL', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['', 'ALL']) ? 'active-all' : '' }}" title="Show All Orders">
      ALL <span class="tab-badge">{{ $statusCounts['ALL'] ?? 0 }}</span>
    </a>
    <!-- Pending -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'PENDING', 'page' => 1]) }}"
       class="status-tab-btn {{ $statusFilter === 'PENDING' ? 'active-pending' : '' }}" title="Filter Pending Orders">
      PENDING <span class="tab-badge">{{ $statusCounts['PENDING'] ?? 0 }}</span>
    </a>
    <!-- Partial -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'PARTIAL', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH']) ? 'active-partial' : '' }}" title="Filter Partial Orders">
      PARTIAL <span class="tab-badge">{{ $statusCounts['PARTIAL'] ?? 0 }}</span>
    </a>
    <!-- Fully Dispatched -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'FULLY_DISPATCHED', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['FULLY_DISPATCH', 'FULLY_DISPATCHED', 'DONE']) ? 'active-done' : '' }}" title="Filter Fully Dispatched Orders">
      FULLY DISPATCHED <span class="tab-badge">{{ $statusCounts['FULLY_DISPATCH'] ?? 0 }}</span>
    </a>
  </div>

  <div style="font-size:0.85rem; color:var(--text-muted); font-weight:600;">
    Showing <strong>{{ $total }}</strong> {{ $total == 1 ? 'order' : 'orders' }}
  </div>
</div>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $d)
    @php
      $rawSt = strtoupper(trim((string)($d['dispatchStatus'] ?? $d['status'] ?? 'PENDING')));
      $statusBadge = '';
      if (in_array($rawSt, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED'])) {
        $statusBadge = '<span class="badge badge-done" style="font-size:0.65rem; background:#16a34a; color:#ffffff !important; padding:2px 6px; border-radius:4px; font-weight:700;">FULLY DISPATCHED</span>';
      } elseif (in_array($rawSt, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING'])) {
        $statusBadge = '<span class="badge" style="font-size:0.65rem; background:#f59e0b; color:#ffffff !important; padding:2px 6px; border-radius:4px; font-weight:700;">PARTIAL</span>';
      } else {
        $statusBadge = '<span class="badge badge-pending" style="font-size:0.65rem; background:#ef4444; color:#ffffff !important; padding:2px 6px; border-radius:4px; font-weight:700;">PENDING</span>';
      }
    @endphp
    <div class="card dispatch-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--border-soft, #e5e7eb); background:var(--bg-card, #ffffff); box-shadow:0 1px 3px rgba(0,0,0,0.04); transition:all 0.2s ease;">
      <!-- Clickable Header Row -->
      <div onclick="toggleReportAccordion('rep-acc-{{ $d['id'] }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
        <div style="flex:1; padding-right:15px;">
          <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
            Order #{{ strtoupper((string)($d['orderId'] ?? $d['id'])) }} - {{ $d['companyName'] ?? 'N/A' }}
          </div>
          <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            {!! $statusBadge !!}
            <span>•</span>
            <span>Sales By: <strong style="color:var(--text-main, #111827);">{{ $d['salesPerson'] ?? 'N/A' }}</strong></span>
            <span>•</span>
            <span>Transporter: {{ $d['transportName'] ?? 'N/A' }}</span>
            <span>•</span>
            <span>Ordered: {{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') }}</span>
            <span>•</span>
            <span class="highlight-due-date">DUE DATE: <strong>{{ !empty($d['dueDate']) && $d['dueDate'] !== 'N/A' ? strtoupper($d['dueDate']) : 'N/A' }}</strong></span>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; text-align:right; flex-wrap:nowrap;">
          <div style="display:flex; flex-direction:column; gap:5px; align-items:flex-end;">
            <div style="font-weight:700; font-size:1.1rem; color:var(--primary, #D88A00);">₹{{ number_format($d['orderTotal'] ?? 0, 2) }}</div>
            <a href="{{ url(request()->segment(1) . '/sales/order/pdf/' . ($d['orderId'] ?? $d['id'])) }}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm" style="width:auto; padding:0.3rem 0.75rem; font-size:0.75rem; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-weight:600; background:var(--primary, #D88A00); color:#000; white-space:nowrap;">
              📄 Order PDF
            </a>
          </div>
          <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>
      </div>

      <!-- Expandable Details Dropdown / Collapsible -->
      <div id="rep-acc-{{ $d['id'] }}" class="rep-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid #f3f4f6; background:#ffffff;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Order ID</div>
            <div style="font-weight:700;">#{{ strtoupper((string)($d['orderId'] ?? $d['id'])) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Date & Time</div>
            <div style="font-size:0.85rem; font-weight:500;">{{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d-m-Y, h:i:s A') }}</div>
          </div>
          <div style="background:rgba(220, 38, 38, 0.08); padding:8px 12px; border-radius:8px; border:1px solid rgba(220, 38, 38, 0.25);">
            <div style="color:#dc2626 !important; font-size:0.72rem; text-transform:uppercase; font-weight:800; letter-spacing:0.3px;">Due Date</div>
            <div style="font-size:1.05rem; font-weight:800; color:#dc2626 !important; margin-top:2px;">{{ !empty($d['dueDate']) && $d['dueDate'] !== '—' && $d['dueDate'] !== 'N/A' ? strtoupper($d['dueDate']) : '—' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $d['companyName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales By</div>
            <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #111827);">{{ $d['salesPerson'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Transport</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $d['transportName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Total Order Qty</div>
            <div style="font-weight:700; font-size:0.95rem;">{{ number_format($d['totalQty'] ?? 0, 3) }} kg</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatched Qty</div>
            <div style="font-weight:700; font-size:0.95rem; color:#16a34a;">{{ number_format($d['dispatchedQty'] ?? 0, 3) }} kg</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Remaining Qty</div>
            <div style="font-weight:700; font-size:0.95rem; color:#ef4444;">{{ number_format($d['remainingQty'] ?? 0, 3) }} kg</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Amount</div>
            <div style="font-weight:700; font-size:0.95rem; color:var(--primary, #D88A00);">₹{{ number_format($d['orderTotal'] ?? 0, 2) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales Note</div>
            <div style="font-weight:600; font-size:0.88rem; color:{{ !empty(trim((string)($d['notes'] ?? ''))) ? 'var(--primary, #D88A00)' : 'var(--text-muted)' }};">
              {{ !empty(trim((string)($d['notes'] ?? ''))) ? $d['notes'] : '—' }}
            </div>
          </div>
        </div>

        <!-- Sales Note / Special Instructions -->
        @if(!empty(trim((string)($d['notes'] ?? ''))))
          <div style="margin-bottom:1rem; padding:10px 14px; background:rgba(216,138,0,0.08); border-left:3px solid var(--primary, #D88A00); border-radius:6px; display:flex; gap:10px; align-items:flex-start;">
            <span style="font-size:1.1rem; line-height:1;">📝</span>
            <div style="flex:1;">
              <div style="color:var(--primary, #D88A00); font-size:0.75rem; text-transform:uppercase; font-weight:700; margin-bottom:3px; letter-spacing:0.3px;">Sales Note / Instructions</div>
              <div style="font-size:0.88rem; color:var(--text-main, #111827); line-height:1.45; word-break:break-word; white-space:pre-wrap;">{{ $d['notes'] }}</div>
            </div>
          </div>
        @endif

        @if(!empty($d['items']) && count($d['items']) > 0)
          <div style="margin-bottom:1rem; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00); box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="color:#6b7280; font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Ordered Items List</div>
            @foreach($d['items'] as $item)
              @php
                $rawName = $item['rawProductName'] ?? $item['productName'] ?? 'Unknown';
                $pName = trim(preg_replace('/\s*\((FG|SEMI|RAW|FINISHED)\)$/i', '', $rawName));
                $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? trim($item['grade']) : '';
                if ($gName) {
                  $pName = trim(preg_replace('/\s+' . preg_quote($gName, '/') . '$/i', '', $pName));
                }
                $rawType = strtoupper((string)($item['productType'] ?? 'FINISHED'));
                $tName = ($rawType === 'FINISHED' || $rawType === 'FG') ? 'FG' : ($rawType === 'SEMI' ? 'SEMI' : ($rawType === 'RAW' ? 'RAW' : $rawType));
                $tot = (float)($item['quantity'] ?? 0);
                $disp = (float)($item['dispatchedQty'] ?? 0);
                $rem = (float)($item['remainingQty'] ?? 0);
                $fmtQty = fn($val) => (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' kg';
              @endphp
              <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                <div style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                  <span style="font-weight:600; color:#111827;">{{ $pName }}</span>
                  @if($gName)
                    <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                  @endif
                  <span style="color:#6b7280; font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                </div>
                <div class="dispatch-item-badges" style="display:grid; grid-template-columns:135px 145px 145px; gap:8px; align-items:center; flex-shrink:0;">
                  <span style="background:#f9fafb; padding:4px 8px; border-radius:6px; border:1px solid #e5e7eb; font-weight:600; color:#111827; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                    <span style="color:#6b7280; font-size:0.72rem; font-weight:700;">ORDER:</span>
                    <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                  </span>
                  <span style="background:rgba(22,163,74,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.3); font-weight:700; color:#15803d; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
                    <span style="font-size:0.72rem;">DISPATCHED:</span>
                    <strong>{{ $fmtQty($disp) }}</strong>
                  </span>
                  <span style="background:rgba(239,68,68,0.12); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.3); font-weight:700; color:#b91c1c; width:100%; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-size:0.78rem; white-space:nowrap;">
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
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No orders found matching the filter criteria.
    </div>
  @endforelse
</div>

@if($totalPages > 1)
  <div style="display:flex; justify-content:center; gap:8px; margin-top:1.5rem;">
    @if($page > 1)
      <a class="btn btn-sm btn-secondary" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" style="width:auto; text-decoration:none;">&laquo; Prev</a>
    @endif
    <span style="align-self:center; color:var(--text-muted);">Page {{ $page }} of {{ $totalPages }}</span>
    @if($page < $totalPages)
      <a class="btn btn-sm btn-secondary" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" style="width:auto; text-decoration:none;">Next &raquo;</a>
    @endif
  </div>
@endif

<script>
  function toggleReportAccordion(contentId, headerEl) {
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
