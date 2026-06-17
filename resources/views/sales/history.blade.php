@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'this_month');
  $startDate = request('start', '');
  $endDate = request('end', '');

  // Retrieve and convert objects to arrays
  $orders = collect($pageData['orders'] ?? [])->map(function($o) {
    $o['_type'] = 'ORDER';
    return $o;
  });
  $companies = collect($pageData['companies'] ?? [])->map(function($c) {
    $c['_type'] = 'COMPANY';
    return $c;
  });
  $transports = collect($pageData['transportCompanies'] ?? [])->map(function($t) {
    $t['_type'] = 'TRANSPORT';
    return $t;
  });

  // Combine timelines
  $timeline = collect([])->concat($orders)->concat($companies)->concat($transports);

  // Search filter
  if ($q) {
    $timeline = $timeline->filter(function($item) use ($q) {
      $query = strtolower($q);
      if ($item['_type'] === 'ORDER') {
        return str_contains(strtolower($item['companyName'] ?? ''), $query) ||
               str_contains(strtolower($item['notes'] ?? ''), $query) ||
               str_contains(strtolower((string)$item['id']), $query);
      } else {
        return str_contains(strtolower($item['name'] ?? ''), $query) ||
               str_contains(strtolower($item['gst'] ?? ''), $query);
      }
    });
  }

  // Date filter
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
      $timeline = $timeline->filter(function($item) use ($start, $end) {
        $date = \Carbon\Carbon::parse($item['date']);
        return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
      });
    }
  }

  $timeline = $timeline->sortByDesc('date');

  $page = request('page', 1);
  $perPage = 15;
  $total = $timeline->count();
  $totalPages = ceil($total / $perPage);
  $paginated = $timeline->slice(($page - 1) * $perPage, $perPage);
  $paginatedArray = $paginated->values()->toArray();
@endphp

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">📈 Sales Activity Timeline</h2>
  <div style="display:flex; gap:8px;">
    <a class="btn btn-sm" href="{{ url('sales/action') }}" style="width:auto; padding:0.5rem 1rem; text-decoration:none;">+ Create New Order</a>
    @php $pdfUrl = route('history.pdf', ['panel' => 'sales']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&q=' . $q; @endphp
    <button id="export-pdf-btn" class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem;"
      onclick="app.exportHistoryPdf(this, '{{ $pdfUrl }}')">📄 Export PDF</button>
  </div>
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
    <input type="text" name="q" placeholder="Search customer, name, GST or Order ID..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
</form>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $item)
    @if($item['_type'] === 'ORDER')
      <div class="list-item" onclick="app.openSalesDrawer({{ $idx }})" style="cursor:pointer; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; transition:0.2s;">
        <div class="list-item-content">
          <div class="list-item-title" style="font-weight:600; font-size:1rem; color:var(--text-main);">
            #{{ strtoupper((string)$item['id']) }} — {{ $item['companyName'] }}
          </div>
          <div class="list-item-meta" style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);">
            <span class="badge badge-open" style="font-size:0.6rem;">ORDER</span> · 
            {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }} · 
            <span class="badge {{ $item['status']==='OPEN'?'badge-open':'badge-closed' }}" style="font-size:0.6rem;">{{ $item['status'] }}</span>
          </div>
        </div>
        <div class="list-item-right" style="text-align:right;">
          <div style="font-weight:bold; color:var(--secondary); font-size:1.1rem;">₹{{ number_format($item['total'], 2) }}</div>
        </div>
      </div>
    @elseif($item['_type'] === 'COMPANY')
      <div class="list-item" onclick="app.openSalesCompanyDrawer({{ $idx }})" style="cursor:pointer; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; transition:0.2s;">
        <div class="list-item-content">
          <div class="list-item-title" style="font-weight:600; font-size:1rem; color:var(--text-main);">
            {{ $item['name'] }}
          </div>
          <div class="list-item-meta" style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);">
            <span class="badge badge-pending" style="font-size:0.6rem;">COMPANY</span> · 
            GST: {{ $item['gst'] ?? 'N/A' }} · 
            {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}
          </div>
        </div>
      </div>
    @else
      <div class="list-item" onclick="app.openSalesTransportDrawer({{ $idx }})" style="cursor:pointer; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; transition:0.2s;">
        <div class="list-item-content">
          <div class="list-item-title" style="font-weight:600; font-size:1rem; color:var(--text-main);">
            {{ $item['name'] }}
          </div>
          <div class="list-item-meta" style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);">
            <span class="badge badge-done" style="font-size:0.6rem;">TRANSPORT</span> · 
            Contact: {{ $item['contact'] ?? 'N/A' }} · 
            {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}
          </div>
        </div>
      </div>
    @endif
  @empty
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No historical sales activity found.
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
    // Populate the global _historyLogs so JS app.openSalesDrawer() can read it
    window._historyLogs = @json($paginatedArray);
  });
</script>
@endsection
