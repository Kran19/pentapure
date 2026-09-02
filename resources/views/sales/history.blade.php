@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'all');
  $startDate = request('start', '');
  $endDate = request('end', '');
  $companyId = request('company_id', '');
  $statusFilter = request('status', '');

  $timeline = collect($pageData['orders'] ?? [])->map(fn($o) => array_merge($o, ['_type' => 'ORDER']));

  if ($companyId) {
    $timeline = $timeline->filter(function($item) use ($companyId) {
      return (string)($item['companyId'] ?? '') === (string)$companyId;
    });
  }

  if ($statusFilter) {
    $timeline = $timeline->filter(function($item) use ($statusFilter) {
      if ($statusFilter === 'CANCELLED') {
        return ($item['status'] ?? '') === 'CANCELLED';
      }
      $dStatus = strtoupper($item['dispatchStatus'] ?? 'PENDING');
      if ($statusFilter === 'PENDING') {
        return $dStatus === 'PENDING' || $dStatus === 'UNASSIGNED' || empty($dStatus);
      }
      if ($statusFilter === 'PARTIAL_PENDING') {
        return $dStatus === 'PARTIAL_PENDING' || $dStatus === 'PARTIAL PENDING';
      }
      if ($statusFilter === 'PARTIAL') {
        return $dStatus === 'PARTIAL' || $dStatus === 'PARTIAL_DISPATCH' || $dStatus === 'PARTIAL DISPATCH' || $dStatus === 'PARTIALLY DISPATCHED';
      }
      if ($statusFilter === 'DONE') {
        return $dStatus === 'DONE' || $dStatus === 'COMPLETED' || $dStatus === 'FULLY DISPATCHED' || $dStatus === 'DISPATCHED';
      }
      return $dStatus === $statusFilter;
    });
  }

  if ($q) {
    $timeline = $timeline->filter(function($item) use ($q) {
      $query = strtolower($q);
      return str_contains(strtolower($item['companyName'] ?? ''), $query) ||
             str_contains(strtolower((string)$item['id']), $query) ||
             str_contains(strtolower((string)$item['total']), $query);
    });
  }

  if ($dateRange === 'custom' && $startDate && $endDate) {
    $start = \Carbon\Carbon::parse($startDate)->startOfDay();
    $end = \Carbon\Carbon::parse($endDate)->endOfDay();
    $timeline = $timeline->filter(function($item) use ($start, $end) {
      $date = \Carbon\Carbon::parse($item['date']);
      return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
    });
  }

  $statusPriority = function($item) {
    $orderStatus = strtoupper(str_replace('_', ' ', $item['status'] ?? ''));
    $dispatchStatus = strtoupper(str_replace('_', ' ', $item['dispatchStatus'] ?? 'PENDING'));

    if ($orderStatus === 'CANCELLED') {
      return 5;
    }
    if ($dispatchStatus === 'PENDING' || $dispatchStatus === 'UNASSIGNED' || empty($dispatchStatus)) {
      return 1;
    }
    if ($dispatchStatus === 'PARTIAL PENDING') {
      return 2;
    }
    if ($dispatchStatus === 'PARTIAL' || $dispatchStatus === 'PARTIAL DISPATCH' || $dispatchStatus === 'PARTIALLY DISPATCHED') {
      return 3;
    }
    if ($dispatchStatus === 'DONE' || $dispatchStatus === 'COMPLETED' || $dispatchStatus === 'FULLY DISPATCHED' || $dispatchStatus === 'DISPATCHED') {
      return 4;
    }
    return 6;
  };

  $timeline = $timeline->sort(function($a, $b) use ($statusPriority) {
    $pA = $statusPriority($a);
    $pB = $statusPriority($b);
    if ($pA !== $pB) {
      return $pA <=> $pB;
    }
    return strtotime($b['date']) <=> strtotime($a['date']);
  })->values();

  $page = request('page', 1);
  $perPage = 15;
  $total = $timeline->count();
  $totalPages = ceil($total / $perPage);
  $paginated = $timeline->slice(($page - 1) * $perPage, $perPage);
  $paginatedArray = $paginated->values()->toArray();
@endphp

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">📈 Sales Orders History</h2>
  <div style="display:flex; gap:8px;">
    <a class="btn btn-sm" href="{{ url(request()->segment(1) . '/action') }}" style="width:auto; padding:0.5rem 1rem; text-decoration:none;">+ Create New Order</a>
    @php $pdfUrl = route('history.pdf', ['panel' => 'sales']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&company_id=' . $companyId . '&status=' . $statusFilter . '&q=' . $q; @endphp
    <button id="export-pdf-btn" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem;"
      onclick="app.exportHistoryPdf(this, '{{ $pdfUrl }}')">📄 Export PDF</button>
  </div>
</div>

<form method="GET" action="" style="margin-bottom:1.2rem; display:flex; flex-direction:column; gap:10px;">
  <!-- 3 Filter Boxes in 1 Line -->
  <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; align-items:center;">
    
    <!-- 1st: Date Range Filter -->
    <div>
      <select name="range" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
        <option value="all" {{ $dateRange==='all'?'selected':'' }}>ALL TIME</option>
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

    <!-- 3rd: Status Filter -->
    <div>
      <select name="status" onchange="this.form.submit()" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
        <option value="">ALL STATUS</option>
        <option value="PENDING" {{ $statusFilter === 'PENDING' ? 'selected' : '' }}>PENDING</option>
        <option value="PARTIAL_PENDING" {{ $statusFilter === 'PARTIAL_PENDING' ? 'selected' : '' }}>PARTIAL PENDING</option>
        <option value="PARTIAL" {{ $statusFilter === 'PARTIAL' ? 'selected' : '' }}>PARTIAL DISPATCH</option>
        <option value="DONE" {{ $statusFilter === 'DONE' ? 'selected' : '' }}>FULLY DISPATCHED</option>
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
    <input type="text" name="q" placeholder="SEARCH CUSTOMER, ORDER ID OR AMOUNT..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.65rem 0.9rem; font-size:0.9rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
  </div>
</form>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $item)
    @php
      $canCancel = ($item['status'] ?? '') !== 'CANCELLED' && ($item['status'] ?? '') !== 'CLOSED' && ($item['dispatchStatus'] ?? '') !== 'DONE' && ($item['dispatchStatus'] ?? '') !== 'PARTIAL';
      $canEdit = ($item['status'] ?? '') === 'OPEN' && (($item['dispatchStatus'] ?? '') === 'PENDING' || ($item['dispatchStatus'] ?? '') === 'UNASSIGNED' || empty($item['dispatchStatus']));
    @endphp
    <div class="card sales-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:var(--card-bg, rgba(255,255,255,0.03)); transition:all 0.2s ease;">
      <!-- Header -->
      <div onclick="toggleHistoryAccordion('sales-acc-{{ $item['id'] }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
        <div style="flex:1; padding-right:15px;">
          <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
            #{{ strtoupper((string)$item['id']) }} — {{ $item['companyName'] ?? 'N/A' }}
          </div>
          <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            <span class="badge badge-open" style="font-size:0.6rem;">ORDER</span>
            <span>•</span>
            <span>{{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}</span>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:12px; text-align:right;">
          <div style="font-weight:bold; color:var(--primary, #D88A00); font-size:1.15rem; white-space:nowrap;">₹{{ number_format($item['total'], 2) }}</div>
          @if($canCancel)
            <button type="button" class="btn btn-sm" onclick="event.stopPropagation(); app.cancelSalesOrder({{ $item['id'] }})" style="background:var(--danger, #ef4444); color:#fff; padding:0.35rem 0.8rem; font-weight:bold; font-size:0.75rem; border-radius:6px; width:auto;">🚫 Cancel</button>
          @endif
          <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>
      </div>

      <!-- Expanded Details -->
      <div id="sales-acc-{{ $item['id'] }}" class="sales-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $item['companyName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Transport</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $item['transporter']['name'] ?? ($item['transportName'] ?? 'N/A') }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatch Status</div>
            <div>
              @php
                $oStatus = strtoupper(str_replace('_', ' ', $item['status'] ?? ''));
                $ds = strtoupper(str_replace('_', ' ', $item['dispatchStatus'] ?? 'PENDING'));
                if ($oStatus === 'CANCELLED') {
                  $statusText = 'CANCELLED';
                  $badgeClass = 'badge-danger';
                  $customBadgeStyle = 'background:#dc2626; color:#fff;';
                } elseif ($ds === 'DONE' || $ds === 'COMPLETED' || $ds === 'FULLY DISPATCHED' || $ds === 'DISPATCHED') {
                  $statusText = 'FULLY DISPATCHED';
                  $badgeClass = 'badge-done';
                  $customBadgeStyle = 'background:#16a34a; color:#fff;';
                } elseif ($ds === 'PARTIAL' || $ds === 'PARTIAL DISPATCH' || $ds === 'PARTIALLY DISPATCHED') {
                  $statusText = 'PARTIAL DISPATCH';
                  $badgeClass = 'badge-pending';
                  $customBadgeStyle = 'background:#f59e0b; color:#fff;';
                } elseif ($ds === 'PARTIAL PENDING') {
                  $statusText = 'PARTIAL PENDING';
                  $badgeClass = 'badge-pending';
                  $customBadgeStyle = 'background:#f59e0b; color:#fff;';
                } else {
                  $statusText = 'PENDING';
                  $badgeClass = 'badge-pending';
                  $customBadgeStyle = 'background:#3b82f6; color:#fff;';
                }
              @endphp
              <span class="badge {{ $badgeClass }}" style="{{ $customBadgeStyle }} font-size:0.75rem; padding:3px 8px; border-radius:12px; font-weight:700;">
                {{ $statusText }}
              </span>
            </div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Date & Time</div>
            <div style="font-size:0.85rem; font-weight:500;">{{ \Carbon\Carbon::parse($item['date'])->format('d M Y, h:i A') }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Total Amount</div>
            <div style="font-weight:700; font-size:1.15rem; color:var(--primary, #D88A00);">₹{{ number_format($item['total'] ?? 0, 2) }}</div>
          </div>
        </div>

        @if(!empty($item['notes']))
          <div style="margin-bottom:1rem; padding:0.8rem 1rem; background:rgba(0,0,0,0.03); border-radius:8px; border:1px solid var(--border-soft, rgba(0,0,0,0.08)); width:100%; box-sizing:border-box; word-break:break-word; overflow-wrap:anywhere;">
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:4px;">Notes / Instructions</div>
            <div style="font-size:0.88rem; color:var(--text-main); word-break:break-word; overflow-wrap:anywhere; white-space:pre-wrap; line-height:1.5;">{{ $item['notes'] }}</div>
          </div>
        @endif

        <div class="table-container" style="margin-bottom:1rem; overflow-x:auto;">
          <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.08)); text-align:left;">
                <th style="padding:6px;">Product</th>
                <th style="padding:6px;">Grade</th>
                <th style="padding:6px;">Qty</th>
                <th style="padding:6px; text-align:right;">Price</th>
              </tr>
            </thead>
            <tbody>
              @forelse($item['items'] ?? [] as $prod)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                  <td style="padding:6px; font-weight:600;">{{ $prod['productName'] ?? 'Unknown' }}</td>
                  <td style="padding:6px;">{{ $prod['grade'] ?: '—' }}</td>
                  <td style="padding:6px;">{{ $prod['quantity'] }} kg</td>
                  <td style="padding:6px; text-align:right; font-weight:600;">₹{{ number_format($prod['price'] ?? 0, 2) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" style="text-align:center; padding:8px; color:var(--text-muted);">No products</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:0.8rem;">
          @if($canEdit)
            <a class="btn btn-sm" href="/sales/action?edit={{ $item['id'] }}" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px; background:var(--warning, #FFA500); color:#000; font-weight:600;">
              ✏️ Edit Order
            </a>
          @endif
          @if($canCancel)
            <button class="btn btn-sm" onclick="app.cancelSalesOrder({{ $item['id'] }})" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; display:inline-flex; align-items:center; gap:5px;">
              🚫 Cancel Order
            </button>
          @endif
          <a class="btn btn-sm btn-secondary" href="/order/pdf/{{ $item['id'] }}" target="_blank" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
            📄 Download PDF
          </a>
        </div>
      </div>
    </div>
  @empty
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No sales orders found for this period.
    </div>
  @endforelse
</div>

@if($totalPages > 1)
  <div style="display:flex; justify-content:center; gap:8px; margin-top:1.5rem;">
    @if($page > 1)
      <a class="btn btn-sm btn-secondary" href="?range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&q={{ $q }}&page={{ $page - 1 }}" style="width:auto; text-decoration:none;">&laquo; Prev</a>
    @endif
    <span style="align-self:center; color:var(--text-muted);">Page {{ $page }} of {{ $totalPages }}</span>
    @if($page < $totalPages)
      <a class="btn btn-sm btn-secondary" href="?range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&q={{ $q }}&page={{ $page + 1 }}" style="width:auto; text-decoration:none;">Next &raquo;</a>
    @endif
  </div>
@endif

<script>
  document.addEventListener('DOMContentLoaded', () => {
    window._historyLogs = @json($paginatedArray);
    window.serverPageData = @json($pageData);
  });

  function toggleHistoryAccordion(id, headerEl) {
    const content = document.getElementById(id);
    if (!content) return;
    const chevron = headerEl.querySelector('.acc-chevron');
    const isHidden = content.style.display === 'none' || !content.style.display;
    
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