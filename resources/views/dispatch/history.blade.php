@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'this_month');
  $startDate = request('start', '');
  $endDate = request('end', '');

  $filtered = collect($pageData['dispatchLogs'] ?? []);

  if ($q) {
    $filtered = $filtered->filter(function($d) use ($q) {
      $query = strtolower($q);
      return str_contains(strtolower($d['companyName'] ?? ''), $query) ||
             str_contains(strtolower($d['transportName'] ?? ''), $query) ||
             str_contains(strtolower((string)$d['orderId']), $query);
    });
  }

  if ($dateRange && $dateRange !== 'all') {
    $now = \Carbon\Carbon::now();
    $start = null;
    $end = \Carbon\Carbon::now();

    if ($dateRange === 'today') {
      $start = \Carbon\Carbon::today();
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

  $filtered = $filtered->sortByDesc('date');

  $page = request('page', 1);
  $perPage = 15;
  $total = $filtered->count();
  $totalPages = ceil($total / $perPage);
  $paginated = $filtered->slice(($page - 1) * $perPage, $perPage);
  $paginatedArray = $paginated->values()->toArray();
@endphp

@php $pdfUrl = route('history.pdf', ['panel' => 'dispatch']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&q=' . $q; @endphp
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">📦 Dispatch Logs History</h2>
  <button id="export-pdf-btn" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem;"
    onclick="app.exportHistoryPdf(this, '{{ $pdfUrl }}')">📄 Export PDF</button>
</div>

<form method="GET" action="" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:10px;">
  <div class="filter-bar" style="flex-wrap:wrap; gap:8px; padding: 0.5rem; background:rgba(0,0,0,0.2); border-radius:8px; display:flex;">
    <select name="range" onchange="this.form.submit()" style="width:auto; flex:1; padding:0.4rem; border-radius:4px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      <option value="today" {{ $dateRange==='today'?'selected':'' }}>Today</option>
      <option value="this_week" {{ $dateRange==='this_week'?'selected':'' }}>This Week</option>
      <option value="last_week" {{ $dateRange==='last_week'?'selected':'' }}>Last Week</option>
      <option value="this_month" {{ $dateRange==='this_month'?'selected':'' }}>This Month</option>
      <option value="last_month" {{ $dateRange==='last_month'?'selected':'' }}>Last Month</option>
      <option value="custom" {{ $dateRange==='custom'?'selected':'' }}>Custom Range</option>
      <option value="all" {{ $dateRange==='all'?'selected':'' }}>All Time</option>
    </select>
    @if($dateRange === 'custom')
      <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()"
        style="width:auto; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid rgba(255,255,255,0.2); background:#1f2937; color:#fff; color-scheme:dark;">
      <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()"
        style="width:auto; padding:0.4rem 0.6rem; border-radius:4px; border:1px solid rgba(255,255,255,0.2); background:#1f2937; color:#fff; color-scheme:dark;">
    @endif
  </div>

  <div class="form-group">
    <input type="text" name="q" placeholder="Search customer, transporter or order ID..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
</form>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $d)
    @php
      $lrUploaded = !empty($d['lrImage']);
      $lrStatus = $lrUploaded ? '<span class="badge badge-done" style="font-size:0.65rem;">LR UPLOADED</span>' : '<span class="badge badge-pending" style="font-size:0.65rem;">LR PENDING</span>';
    @endphp
    <div class="card dispatch-history-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:var(--card-bg, rgba(255,255,255,0.03)); transition:all 0.2s ease;">
      <!-- Clickable Header Row -->
      <div onclick="toggleHistoryAccordion('disp-acc-{{ $d['id'] }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
        <div style="flex:1; padding-right:15px;">
          <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
            Order #{{ strtoupper((string)$d['orderId']) }} — {{ $d['companyName'] ?? 'N/A' }}
          </div>
          <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            {!! $lrStatus !!}
            <span>•</span>
            <span>Transporter: {{ $d['transportName'] ?? 'N/A' }}</span>
            <span>•</span>
            <span>{{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</span>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; text-align:right;">
          <button type="button" class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); app.revertDispatch({{ $d['id'] }})" style="width:auto; padding:0.35rem 0.8rem; font-size:0.75rem; border-color:#ef4444 !important; color:#ef4444 !important;">↩️ Revert Dispatch</button>
          <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>
      </div>

      <!-- Expandable Details Dropdown / Collapsible -->
      <div id="disp-acc-{{ $d['id'] }}" class="disp-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Order</div>
            <div style="font-weight:700;">#{{ strtoupper((string)$d['orderId']) }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Date & Time</div>
            <div style="font-size:0.85rem; font-weight:500;">{{ \Carbon\Carbon::parse($d['date'])->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Company</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $d['companyName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Transport</div>
            <div style="font-weight:600; font-size:0.9rem;">{{ $d['transportName'] ?? 'N/A' }}</div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Dispatched By</div>
            <div style="font-weight:500; font-size:0.85rem;">{{ $d['dispatchedBy'] ?? 'System' }}</div>
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
          <div style="margin-bottom:1rem; background:rgba(0,0,0,0.15); border-radius:8px; padding:12px; border-left:3px solid var(--primary, #D88A00);">
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:8px; font-weight:bold;">Items Dispatched in this Round</div>
                        @foreach($d['items'] as $item)
              @php
                $pName = preg_replace('/\s+(PURE|PREMIUM|COMMERCIAL|NONE|\b[A-Za-z0-9_-]+\b)\s*\((fg|raw|semi)\)$/i', '', $item['productName'] ?? 'Unknown');
                $pName = preg_replace('/\s*\((fg|raw|semi)\)$/i', '', $pName);
                $gName = ($item['grade'] && $item['grade'] !== 'NONE' && $item['grade'] !== 'N/A') ? $item['grade'] : '';
                $tName = ($item['productType'] === 'FINISHED') ? 'FG' : ($item['productType'] ? strtoupper($item['productType']) : 'N/A');
              @endphp
              <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.88rem;">
                <span>{{ $pName }} @if($gName)<strong style="font-weight:800; color:var(--primary, #D88A00);">{{ $gName }}</strong> @endif({{ $tName }})</span>
                <span style="font-weight:bold; color:var(--primary, #D88A00);">{{ $item['quantity'] }} kg</span>
              </div>
            @endforeach
          </div>
        @endif

        @if($lrUploaded)
          <div style="margin-bottom:1rem;">
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:0.5rem;">LR Copy</div>
            <img src="{{ $d['lrImage'] }}" style="width:100%; border-radius:10px; max-height:200px; object-fit:contain; cursor:pointer; background:rgba(0,0,0,0.2);" onclick="app.viewImage(this.src)">
            <div style="margin-top:8px;">
              <button class="btn btn-sm btn-secondary" style="width:auto; font-size:0.75rem;" onclick="document.getElementById('late-lr-input-{{ $d['id'] }}').click()">Update LR Copy</button>
            </div>
          </div>
        @else
          <div style="margin-bottom:1rem; padding:1.2rem; background:rgba(255,165,0,0.05); border:1px dashed rgba(255,165,0,0.3); border-radius:10px; text-align:center;">
            <div style="color:var(--warning, #FFA500); font-weight:600; font-size:0.88rem; margin-bottom:8px;">LR Copy Pending</div>
            <button class="btn btn-sm btn-secondary" style="width:auto; font-size:0.8rem;" onclick="document.getElementById('late-lr-input-{{ $d['id'] }}').click()">Upload LR Now</button>
          </div>
        @endif
        <input type="file" id="late-lr-input-{{ $d['id'] }}" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="app.handleLateLRUpload(event, {{ $d['id'] }}, {{ $idx }})">

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:0.8rem;">
          <a href="{{ url(request()->segment(1) . '/pdf/' . $d['id']) }}" target="_blank" class="btn btn-sm" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
            📄 Download Dispatch PDF
          </a>
          <button class="btn btn-sm" onclick="app.revertDispatch({{ $d['id'] }})" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; display:inline-flex; align-items:center; gap:5px;">
            ↩️ Revert Dispatch
          </button>
        </div>
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