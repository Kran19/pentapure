@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
@if(session('success'))
  <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:0.8rem 1.2rem; border-radius:8px; margin-bottom:1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
    <span>✓ {{ session('success') }}</span>
    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; font-size:1.1rem; cursor:pointer;">&times;</button>
  </div>
@endif
@if(session('error'))
  <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:0.8rem 1.2rem; border-radius:8px; margin-bottom:1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
    <span>⚠️ {{ session('error') }}</span>
    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#991b1b; font-size:1.1rem; cursor:pointer;">&times;</button>
  </div>
@endif
@php
  $q = request('q', '');
  $dateRange = request('range', 'all');
  $startDate = request('start', '');
  $endDate = request('end', '');
  $companyId = request('company_id', '');
  $statusFilter = strtoupper(trim((string)request('status', 'ALL')));
  if (!$statusFilter || $statusFilter === '') $statusFilter = 'ALL';

  $filtered = collect($pageData['dispatchLogs'] ?? []);

  // 1. Company Filter
  if ($companyId) {
    $filtered = $filtered->filter(function($d) use ($companyId) {
      return (string)($d['companyId'] ?? '') === (string)$companyId;
    });
  }

  // 2. Search Query Filter
  if ($q) {
    $filtered = $filtered->filter(function($d) use ($q) {
      $query = strtolower($q);
      $hasProduct = collect($d['items'] ?? [])->contains(function($item) use ($query) {
        return str_contains(strtolower($item['productName'] ?? ''), $query) ||
               str_contains(strtolower($item['rawProductName'] ?? ''), $query);
      });
      return str_contains(strtolower($d['companyName'] ?? ''), $query) ||
             str_contains(strtolower($d['salesPerson'] ?? ''), $query) ||
             str_contains(strtolower($d['transportName'] ?? ''), $query) ||
             str_contains(strtolower((string)$d['orderId']), $query) ||
             str_contains(strtolower((string)$d['id']), $query) ||
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
      $end = \Carbon\Carbon::today()->endOfDay();
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
  $baseLogs = $filtered;
  $statusCounts = [
    'ALL' => $baseLogs->count(),
    'PARTIAL' => $baseLogs->filter(function($d) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($d['dispatchStatus'] ?? $d['status'] ?? ''))));
      return in_array($st, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING']);
    })->count(),
    'FULLY_DISPATCH' => $baseLogs->filter(function($d) {
      $st = strtoupper(trim(str_replace('_', ' ', (string)($d['dispatchStatus'] ?? $d['status'] ?? ''))));
      return in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']);
    })->count(),
  ];

  // 4. Status Filter: when PENDING is requested, map to PARTIAL
  if ($statusFilter && $statusFilter !== 'ALL') {
    $filtered = $baseLogs->filter(function($d) use ($statusFilter) {
      $st = strtoupper(trim((string)($d['dispatchStatus'] ?? $d['status'] ?? '')));
      $st = str_replace('_', ' ', $st);
      $target = strtoupper(trim(str_replace('_', ' ', $statusFilter)));
      
      if (in_array($target, ['FULLY DISPATCHED', 'FULLY DISPATCH', 'DONE'])) {
        return in_array($st, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED']);
      }
      if ($target === 'PARTIAL' || $target === 'PARTIAL DISPATCH' || $target === 'PARTIAL PENDING' || $target === 'PENDING') {
        return in_array($st, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING']);
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
  $fmtQty = fn($val) => (floor($val) == $val ? number_format($val, 0) : number_format($val, 2)) . ' kg';
@endphp

@php
  $userSlug = in_array(request()->segment(1), ['admin', 'sub_admin', 'dispatch', 'sales', 'stock_manager'])
    ? request()->segment(1)
    : (session('auth_user')['login_slug'] ?? strtolower(session('auth_user')['role'] ?? 'dispatch'));
  $pdfUrl = route('history.pdf', ['user_slug' => $userSlug, 'panel' => 'dispatch']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&company_id=' . $companyId . '&status=' . ($statusFilter === 'ALL' ? '' : $statusFilter) . '&q=' . $q;
@endphp
<style>
/* Dispatch Item Badges */
.dispatch-item-badges {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.dispatch-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  box-sizing: border-box;
  white-space: nowrap;
  line-height: 1.3;
}

.dispatch-badge .badge-lbl {
  font-size: 0.72rem;
  font-weight: 700;
  opacity: 0.85;
}

.dispatch-badge.badge-order {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  color: #111827;
}
.dispatch-badge.badge-order strong {
  color: var(--primary, #D88A00);
  font-weight: 800;
}

.dispatch-badge.badge-prev {
  background: rgba(217, 119, 6, 0.1);
  border: 1px solid rgba(217, 119, 6, 0.3);
  color: #b45309;
}

.dispatch-badge.badge-disp {
  background: rgba(22, 163, 74, 0.12);
  border: 1px solid rgba(22, 163, 74, 0.3);
  color: #15803d;
}

.dispatch-badge.badge-rem {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #b91c1c;
}

@media (max-width: 992px) {
  .dispatch-item-row {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 8px !important;
  }
  .dispatch-item-name {
    width: 100% !important;
    min-width: 0 !important;
  }
  .dispatch-item-badges-wrap {
    width: 100% !important;
  }
  .dispatch-item-badges {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    width: 100% !important;
    gap: 6px !important;
  }
  .dispatch-badge {
    width: 100% !important;
    padding: 6px 8px !important;
  }
}

@media (max-width: 640px) {
  .dispatch-filters-grid {
    grid-template-columns: 1fr !important;
    gap: 8px !important;
  }
  .dispatch-custom-range-row {
    flex-direction: column !important;
    gap: 8px !important;
  }
  .disp-card-header {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
    padding: 1rem 0.85rem !important;
  }
  .disp-card-header-main {
    padding-right: 0 !important;
  }
  .disp-card-header-actions {
    justify-content: space-between !important;
    width: 100% !important;
    padding-top: 10px !important;
    border-top: 1px dashed #e5e7eb !important;
  }
  .disp-card-header-actions > div:first-child {
    flex-direction: row !important;
    flex-wrap: wrap !important;
    flex: 1 !important;
  }
  .disp-card-header-actions > div:first-child .btn {
    flex: 1 1 auto !important;
  }
  #lr-batch-bar {
    width: calc(100% - 24px) !important;
    padding: 10px 14px !important;
    border-radius: 16px !important;
    gap: 8px !important;
    justify-content: space-between !important;
    bottom: 12px !important;
  }
  #lr-batch-bar button {
    padding: 6px 10px !important;
    font-size: 0.75rem !important;
  }
}

@media (max-width: 480px) {
  .dispatch-item-badges {
    grid-template-columns: 1fr 1fr !important;
    gap: 5px !important;
  }
  .dispatch-badge {
    font-size: 0.72rem !important;
    padding: 5px 6px !important;
    flex-direction: column !important;
    gap: 2px !important;
    text-align: center !important;
  }
  .dispatch-badge .badge-lbl {
    font-size: 0.65rem !important;
  }
}

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
</style>
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">📦 Dispatch History</h2>
  <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <button type="button" class="btn btn-sm" style="width:auto; padding:0.5rem 1rem; background:#059669; color:#fff; font-weight:600; border:none; border-radius:6px; cursor:pointer;" onclick="app.toggleSelectAllLR()">
      ☑️ Select All LR
    </button>
    <button id="export-pdf-btn" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem;"
      onclick="app.exportHistoryPdf(this, '{{ $pdfUrl }}')">📄 Export PDF</button>
    @if(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN']))
      <button type="button" class="btn btn-sm" onclick="confirmClearDispatchHistory()" style="width:auto; padding:0.5rem 1rem; background:#dc2626; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer;" title="Permanently clear all dispatch logs and sales orders">
        🗑️ Clear All Dispatch History
      </button>
    @endif
  </div>
</div>

<form method="GET" action="" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:10px;">
  <input type="hidden" name="status" value="{{ $statusFilter }}">

  <!-- 2 Filter Boxes in 1 Line: Date Range & Company Name -->
  <div class="dispatch-filters-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:center;">
    
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
    <div class="dispatch-custom-range-row" style="display:flex; gap:10px; align-items:center;">
      <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()"
        style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()"
        style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    </div>
  @endif

  <!-- Search Input Bar -->
  <div class="form-group" style="margin-bottom:0;">
    <input type="text" name="q" placeholder="SEARCH CUSTOMER, SALESPERSON, TRANSPORTER, ORDER ID OR DISPATCH ID..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.65rem 0.9rem; font-size:0.9rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
  </div>
</form>

<!-- Status Tabs Filter Bar (ALL, PARTIAL, FULLY DISPATCHED) -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:1.2rem;">
  <div class="status-tabs-wrapper">
    <!-- All -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'ALL', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['', 'ALL']) ? 'active-all' : '' }}" title="Show All Dispatches">
      ALL <span class="tab-badge">{{ $statusCounts['ALL'] ?? 0 }}</span>
    </a>
    <!-- Partial -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'PARTIAL', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['PARTIAL', 'PARTIAL_PENDING', 'PARTIAL_DISPATCH', 'PENDING']) ? 'active-partial' : '' }}" title="Filter Partial Dispatches">
      PARTIAL <span class="tab-badge">{{ $statusCounts['PARTIAL'] ?? 0 }}</span>
    </a>
    <!-- Fully Dispatched -->
    <a href="{{ request()->fullUrlWithQuery(['status' => 'FULLY_DISPATCHED', 'page' => 1]) }}"
       class="status-tab-btn {{ in_array($statusFilter, ['FULLY_DISPATCH', 'FULLY_DISPATCHED', 'DONE']) ? 'active-done' : '' }}" title="Filter Fully Dispatched Orders">
      FULLY DISPATCHED <span class="tab-badge">{{ $statusCounts['FULLY_DISPATCH'] ?? 0 }}</span>
    </a>
  </div>

  <div style="font-size:0.85rem; color:var(--text-muted); font-weight:600;">
    Showing <strong>{{ $total }}</strong> {{ $total == 1 ? 'record' : 'records' }}
  </div>
</div>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $d)
    @php
      $lrUploaded = !empty($d['lrImage']);
      $lrStatus = $lrUploaded ? '<span class="badge badge-done" style="font-size:0.65rem;">LR UPLOADED</span>' : '<span class="badge" style="font-size:0.65rem; background:#dc2626 !important; color:#ffffff !important; font-weight:700; padding:3px 8px; border-radius:4px;">LR PENDING</span>';

      $rawSt = strtoupper(trim((string)($d['dispatchStatus'] ?? $d['status'] ?? 'PENDING')));
      if (in_array($rawSt, ['DONE', 'FULLY DISPATCHED', 'COMPLETED', 'CLOSED'])) {
        $statusBadge = '<span class="badge badge-done" style="font-size:0.65rem; background:#16a34a; color:#ffffff !important; padding:3px 8px; border-radius:4px; font-weight:700;">FULLY DISPATCHED</span>';
      } elseif (in_array($rawSt, ['PARTIAL', 'PARTIAL DISPATCH', 'PARTIAL PENDING'])) {
        $statusBadge = '<span class="badge" style="font-size:0.65rem; background:#f59e0b; color:#ffffff !important; padding:3px 8px; border-radius:4px; font-weight:700;">PARTIAL</span>';
      } else {
        $statusBadge = '<span class="badge badge-pending" style="font-size:0.65rem; background:#ef4444; color:#ffffff !important; padding:3px 8px; border-radius:4px; font-weight:700;">PENDING</span>';
      }
    @endphp
    <div class="card dispatch-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--border-soft, #e5e7eb); background:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.04); transition:all 0.2s ease;">
      <!-- Clickable Header Row -->
      <div onclick="toggleHistoryAccordion('disp-acc-{{ $d['id'] }}', this)" class="disp-card-header" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none; background:#ffffff;">
        <div class="disp-card-header-main" style="display:flex; align-items:center; flex:1; padding-right:15px;">
          <div onclick="event.stopPropagation()" style="display:inline-flex; align-items:center; margin-right:12px; flex-shrink:0;">
            @if($lrUploaded)
              <input type="checkbox" class="lr-select-check" data-id="{{ $d['id'] }}" data-order-id="{{ $d['orderId'] }}" data-has-lr="1" onclick="app.updateLRSelection();" style="width:18px; height:18px; cursor:pointer; accent-color:#059669;" title="Select to download LR copy">
            @else
              <input type="checkbox" disabled style="width:18px; height:18px; opacity:0.25; cursor:not-allowed;" title="LR copy not uploaded yet">
            @endif
          </div>
          <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; line-height:1.3;">
              <span style="font-weight:700; font-size:1.05rem; color:var(--text-main, #111827);">
                Order #{{ strtoupper((string)$d['orderId']) }}
              </span>
              <span class="badge" style="background:#0284c7; color:#ffffff !important; font-weight:700; font-size:0.75rem; padding:2px 8px; border-radius:6px; letter-spacing:0.3px;">
                DSP #{{ $d['id'] }}
              </span>
              <span style="color:#9ca3af; font-weight:600;">-</span>
              <span style="font-weight:600; font-size:1rem; color:var(--text-main, #111827);">
                {{ $d['companyName'] ?? 'N/A' }}
              </span>
            </div>
            <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
              {!! $statusBadge !!}
              <span>•</span>
              {!! $lrStatus !!}
              <span>•</span>
              <span>Dispatch ID: <strong style="color:var(--text-main, #111827);">#{{ $d['id'] }}</strong></span>
              <span>•</span>
              <span>Dispatch Date: <strong style="color:var(--text-main, #111827);">{{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d-m-Y, h:i A') }}</strong></span>
              <span>•</span>
              <span>Sales By: <strong style="color:var(--text-main, #111827);">{{ $d['salesPerson'] ?? 'N/A' }}</strong></span>
              <span>•</span>
              <span>Transporter: {{ $d['transportName'] ?? 'N/A' }}</span>
              @if(!empty($d['totalPrevDispatchedQty']) && $d['totalPrevDispatchedQty'] > 0)
                <span>•</span>
                <span>Prev. Dispatched: <strong style="color:#d97706;">{{ $fmtQty($d['totalPrevDispatchedQty']) }}</strong></span>
              @endif
            </div>
          </div>
        </div>
        <div class="disp-card-header-actions" style="display:flex; align-items:center; gap:10px; text-align:right; flex-wrap:nowrap;">
          <div style="display:flex; flex-direction:column; gap:5px; align-items:stretch;">
            <a href="{{ url($userSlug . '/pdf/' . $d['id']) }}" target="_blank" onclick="event.stopPropagation()" class="btn btn-sm" style="width:100%; padding:0.3rem 0.75rem; font-size:0.75rem; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:4px; font-weight:600; background:var(--primary, #D88A00); color:#000; white-space:nowrap;">
              📄 Download PDF
            </a>
            @if(empty($isReadOnly))
            <button type="button" class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); app.revertDispatch({{ $d['id'] }})" style="width:100%; padding:0.3rem 0.75rem; font-size:0.75rem; border-color:#ef4444 !important; color:#ef4444 !important; display:inline-flex; align-items:center; justify-content:center; gap:4px; white-space:nowrap;">
              ↩ Revert Dispatch
            </button>
            @endif
          </div>
          <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>
      </div>

      <!-- Expandable Details Dropdown / Collapsible -->
      <div id="disp-acc-{{ $d['id'] }}" class="disp-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid #f3f4f6; background:#ffffff;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatch ID</div>
            <div style="font-weight:700; color:#0284c7; font-size:1rem;">#{{ $d['id'] }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatch Date</div>
            <div style="font-size:0.85rem; font-weight:600; color:var(--text-main, #111827);">{{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d-m-Y, h:i:s A') }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Order</div>
            <div style="font-weight:700; color:var(--text-main, #111827);">#{{ strtoupper((string)$d['orderId']) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
            <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #111827);">{{ $d['companyName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Sales By</div>
            <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #111827);">{{ $d['salesPerson'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Transport</div>
            <div style="font-weight:600; font-size:0.9rem; color:var(--text-main, #111827);">{{ $d['transportName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatched By</div>
            <div style="font-weight:500; font-size:0.85rem; color:var(--text-main, #111827);">{{ $d['dispatchedBy'] ?? 'System' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Prev. Dispatched</div>
            <div style="font-weight:700; font-size:0.95rem; color:#d97706;">{{ $fmtQty($d['totalPrevDispatchedQty'] ?? 0) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">This Dispatch Qty</div>
            <div style="font-weight:700; font-size:0.95rem; color:#15803d;">{{ $fmtQty($d['totalCurrentDispatchedQty'] ?? 0) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Pending Qty</div>
            <div style="font-weight:700; font-size:0.95rem; color:#b91c1c;">{{ $fmtQty($d['totalRemainingQty'] ?? 0) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Order Value</div>
            <div style="font-weight:700; font-size:1.1rem; color:var(--primary, #D88A00);">₹{{ number_format($d['orderTotal'] ?? 0, 2) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">LR Status</div>
            <div>{!! $lrStatus !!}</div>
          </div>
        </div>

        @if(!empty($d['items']) && count($d['items']) > 0)
          <div style="margin-bottom:1rem; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00); box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="color:#6b7280; font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">{{ !empty($d['isOrderOnly']) ? 'Items Pending Dispatch' : 'Items Dispatched in this Round' }}</div>
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
                $tot = (float)($item['totalQty'] ?? 0);
                $prev = (float)($item['prevDispatchedQty'] ?? 0);
                $disp = (float)($item['dispatchedQty'] ?? 0);
                $rem = (float)($item['remainingQty'] ?? 0);
              @endphp
              <div class="dispatch-item-row" style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:0.88rem; flex-wrap:wrap; gap:12px;">
                <div class="dispatch-item-name" style="flex:1; min-width:200px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                  <span style="font-weight:600; color:#111827;">{{ $pName }}</span>
                  @if($gName)
                    <strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong>
                  @endif
                  <span style="color:#6b7280; font-size:0.78rem; font-weight:700;">({{ $tName }})</span>
                </div>
                <div class="dispatch-item-badges-wrap" style="display:flex; align-items:center;">
                  <div class="dispatch-item-badges">
                    <span class="dispatch-badge badge-order">
                      <span class="badge-lbl" style="color:#6b7280;">ORDER:</span>
                      <strong style="color:var(--primary, #D88A00);">{{ $fmtQty($tot) }}</strong>
                    </span>
                    <span class="dispatch-badge badge-prev" title="Previously dispatched before this round">
                      <span class="badge-lbl">PREV. DISPATCH:</span>
                      <strong>{{ $fmtQty($prev) }}</strong>
                    </span>
                    <span class="dispatch-badge badge-disp" title="Dispatched in this round">
                      <span class="badge-lbl">DISPATCHED:</span>
                      <strong>{{ $fmtQty($disp) }}</strong>
                    </span>
                    <span class="dispatch-badge badge-rem" title="Remaining pending quantity">
                      <span class="badge-lbl">PENDING:</span>
                      <strong>{{ $fmtQty($rem) }}</strong>
                    </span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        @if(!empty($d['isOrderOnly']))
          <div style="margin-bottom:1rem; padding:1.2rem; background:rgba(239,68,68,0.06); border:1px dashed rgba(239,68,68,0.3); border-radius:10px; text-align:center;">
            <div style="color:#ef4444; font-weight:700; font-size:0.88rem; margin-bottom:8px;">Dispatch &amp; LR Copy Pending (Order not yet dispatched)</div>
            @if(empty($isReadOnly))
              <a href="{{ url(request()->segment(1) . '/dispatch/action?order_id=' . $d['orderId']) }}" class="btn btn-sm" style="font-size:0.82rem; padding:0.5rem 1rem; background:#059669; color:#fff !important; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight:700; border-radius:6px;">
                📦 Dispatch Order Now
              </a>
            @endif
          </div>
        @elseif($lrUploaded)
          <div style="margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; flex-wrap:wrap; gap:6px;">
              <span style="color:var(--text-muted, #6b7280); font-size:0.75rem; text-transform:uppercase; font-weight:700;">
                📷 Particular LR Copy for Order #{{ strtoupper((string)$d['orderId']) }}
                @if(!empty($d['isOrderLrCopy']))
                  <span style="color:#0284c7; font-weight:600; font-size:0.7rem;">(From Order's Dispatch #{{ $d['orderLrCopies'][0]['logId'] ?? '' }})</span>
                @else
                  <span style="color:#15803d; font-weight:600; font-size:0.7rem;">(Dispatch #{{ $d['id'] }})</span>
                @endif
              </span>
              <a href="{{ url(request()->segment(1) . '/dispatch/download-lr/' . $d['id']) }}" download class="btn btn-sm" style="font-size:0.75rem; padding:0.25rem 0.65rem; background:#059669; color:#fff !important; text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600; border-radius:4px;">
                📥 Download LR Copy
              </a>
            </div>
            <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; padding:12px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
              <img src="{{ $d['lrImage'] }}" style="max-width:100%; width:auto; border-radius:6px; max-height:300px; object-fit:contain; cursor:pointer; background:#ffffff; display:inline-block;" onclick="app.viewImage(this.src)" title="Click to view full image for Order #{{ $d['orderId'] }}">
            </div>
            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
              <a href="{{ url(request()->segment(1) . '/dispatch/download-lr/' . $d['id']) }}" download class="btn btn-sm" style="font-size:0.78rem; padding:0.45rem 0.8rem; background:#059669; color:#fff !important; text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:600; border-radius:6px;">
                📥 Download LR
              </a>
              <button type="button" class="btn btn-sm btn-media-camera" style="font-size:0.78rem; padding:0.45rem 0.8rem;" onclick="document.getElementById('late-lr-cam-{{ $d['id'] }}').click()">📷 Camera</button>
              <button type="button" class="btn btn-sm btn-media-gallery" style="font-size:0.78rem; padding:0.45rem 0.8rem;" onclick="document.getElementById('late-lr-input-{{ $d['id'] }}').click()">📁 Change/Update This Order's LR</button>
            </div>
          </div>
        @else
          <div style="margin-bottom:1rem; padding:1.2rem; background:rgba(220,38,38,0.06); border:1px dashed rgba(220,38,38,0.3); border-radius:10px; text-align:center;">
            <div style="color:#ef4444; font-weight:700; font-size:0.88rem; margin-bottom:8px;">LR Copy Pending for Order #{{ strtoupper((string)$d['orderId']) }}</div>
            <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
              <button type="button" class="btn btn-sm btn-media-camera" style="font-size:0.82rem; padding:0.5rem 0.9rem;" onclick="document.getElementById('late-lr-cam-{{ $d['id'] }}').click()">📷 Camera</button>
              <button type="button" class="btn btn-sm btn-media-gallery" style="font-size:0.82rem; padding:0.5rem 0.9rem;" onclick="document.getElementById('late-lr-input-{{ $d['id'] }}').click()">📁 Upload This Order's LR Now</button>
            </div>
          </div>
        @endif

        @if(!empty($d['orderLrCopies']) && count($d['orderLrCopies']) > 1)
          <div style="margin-bottom:1rem; padding:1rem; background:#f0f9ff; border-radius:10px; border:1px solid #bae6fd;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
              <div style="font-size:0.82rem; font-weight:700; color:#0369a1; display:flex; align-items:center; gap:6px;">
                <span>📦 All LR Copies for Order #{{ strtoupper((string)$d['orderId']) }}</span>
                <span class="badge" style="background:#0284c7; color:#fff; font-size:0.7rem; padding:2px 8px; border-radius:10px;">{{ count($d['orderLrCopies']) }} Copies</span>
              </div>
              <a href="{{ url(request()->segment(1) . '/dispatch/download-multiple-lr?order_id=' . $d['orderId']) }}" download class="btn btn-sm" style="padding:0.35rem 0.85rem; font-size:0.75rem; background:#0284c7; color:#fff !important; text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-weight:700; border-radius:6px;">
                📥 Download All ({{ count($d['orderLrCopies']) }}) LRs (ZIP)
              </a>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:10px;">
              @foreach($d['orderLrCopies'] as $olr)
                <div style="background:#ffffff; border:1px solid {{ $olr['isCurrent'] ? '#0284c7' : '#e5e7eb' }}; border-radius:8px; padding:8px; display:flex; flex-direction:column; gap:6px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                  <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.72rem;">
                    <span style="font-weight:700; color:#111827;">Dispatch #{{ $olr['logId'] }}</span>
                    @if($olr['isCurrent'])
                      <span style="color:#0284c7; font-weight:700; font-size:0.65rem;">(CURRENT)</span>
                    @endif
                  </div>
                  <img src="{{ $olr['url'] }}" style="width:100%; height:90px; object-fit:contain; border-radius:6px; cursor:pointer; background:#ffffff; border:1px solid #f3f4f6;" onclick="app.viewImage(this.src)">
                  <div style="font-size:0.7rem; color:#6b7280; display:flex; justify-content:space-between; flex-wrap:wrap;">
                    <span>{{ $olr['date'] }}</span>
                    @if(!empty($olr['lrNo']))
                      <span style="font-weight:600; color:#111827;">LR: {{ $olr['lrNo'] }}</span>
                    @endif
                  </div>
                  <a href="{{ url(request()->segment(1) . '/dispatch/download-lr/' . $olr['logId']) }}" download class="btn btn-sm" style="padding:0.25rem; font-size:0.72rem; background:#059669; color:#fff !important; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:4px; font-weight:600; border-radius:4px; margin-top:2px;">
                    📥 Download
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endif
        @if(empty($d['isOrderOnly']))
          <input type="file" id="late-lr-cam-{{ $d['id'] }}" accept="image/*" capture="environment" style="display:none;" onchange="app.handleLateLRUpload(event, {{ $d['id'] }}, {{ $idx }})">
          <input type="file" id="late-lr-input-{{ $d['id'] }}" accept=".jpg,.jpeg,.png,.webp,image/*" style="display:none;" onchange="app.handleLateLRUpload(event, {{ $d['id'] }}, {{ $idx }})">
        @endif
      </div>
    </div>
  @empty
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No historical dispatch logs found.
    </div>
  @endforelse
</div>

@if($totalPages > 1)
  <div style="display:flex; justify-content:center; gap:8px; margin-top:1.5rem;">
    @if($page > 1)
      <a class="btn btn-sm btn-secondary" href="?range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&company_id={{ $companyId }}&status={{ $statusFilter }}&q={{ $q }}&page={{ $page - 1 }}" style="width:auto; text-decoration:none;">&laquo; Prev</a>
    @endif
    <span style="align-self:center; color:var(--text-muted);">Page {{ $page }} of {{ $totalPages }}</span>
    @if($page < $totalPages)
      <a class="btn btn-sm btn-secondary" href="?range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&company_id={{ $companyId }}&status={{ $statusFilter }}&q={{ $q }}&page={{ $page + 1 }}" style="width:auto; text-decoration:none;">Next &raquo;</a>
    @endif
  </div>
@endif

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Keep filter state
  });

  function toggleHistoryAccordion(contentId, headerEl) {
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

  function confirmClearDispatchHistory() {
    if (!confirm('Are you sure you want to permanently delete ALL sales orders and dispatch history? This will wipe all orders and dispatches from both Dispatch History and Sales History. Users, companies, and products will remain 100% intact.')) {
      return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url(request()->segment(1) . "/dispatch/history/clear") }}';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
  }
</script>
<!-- Floating Batch Download Bar for Multiple Selected LRs -->
<div id="lr-batch-bar" style="display:none; position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:9999; background:linear-gradient(135deg, #0f172a, #1e293b); color:#ffffff; padding:12px 24px; border-radius:50px; box-shadow:0 12px 35px rgba(0,0,0,0.55); align-items:center; gap:14px; border:1.5px solid rgba(255,255,255,0.15); backdrop-filter:blur(10px);">
  <div style="display:flex; align-items:center; gap:8px;">
    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981;"></span>
    <span id="lr-batch-count" style="font-weight:700; font-size:0.88rem; letter-spacing:0.3px;">0 Selected</span>
  </div>
  <div style="height:20px; width:1px; background:rgba(255,255,255,0.2);"></div>
  <button type="button" class="btn btn-sm" onclick="app.downloadSelectedLRs('zip')" style="background:#059669; color:#fff; font-weight:700; border-radius:30px; padding:6px 16px; border:none; display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:0.82rem;" title="Download all selected LR copies in a single ZIP file">
    📥 Download (ZIP)
  </button>
  <button type="button" class="btn btn-sm" onclick="app.downloadSelectedLRs('files')" style="background:#2563eb; color:#fff; font-weight:600; border-radius:30px; padding:6px 14px; border:none; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.82rem;" title="Download files individually">
    📥 Download Files (2-3)
  </button>
  <button type="button" onclick="app.clearLRSelection()" style="background:transparent; color:#94a3b8; border:none; cursor:pointer; font-size:0.85rem; padding:4px 8px; font-weight:600;" title="Clear Selection">
    ✕ Clear
  </button>
</div>
@endsection