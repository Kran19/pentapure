@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'this_month');
  $startDate = request('start', '');
  $endDate = request('end', '');

  $filtered = collect($pageData['productionLogs'] ?? []);

  if ($q) {
    $filtered = $filtered->filter(function($s) use ($q) {
      return str_contains(strtolower($s['outputName'] ?? ''), strtolower($q)) || 
             str_contains(strtolower($s['outputGrade'] ?? ''), strtolower($q)) ||
             str_contains(strtolower($s['notes'] ?? ''), strtolower($q));
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

@php $pdfUrl = route('history.pdf', ['panel' => 'finished']) . '?range=' . $dateRange . '&start=' . $startDate . '&end=' . $endDate . '&q=' . $q; @endphp
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">✅ FG Production Logs History</h2>
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
    <input type="text" name="q" placeholder="Search product..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
</form>

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $l)
    @php
      $inputList = collect($l['consumedInputs'] ?? [])->map(function($i) {
        return "<span style=\"color:red; font-weight:bold;\">-{$i['quantity']}kg</span> " . ($i['name'] ?? 'Material');
      })->join(', ');
    @endphp
    <div class="list-item" onclick="app.openProductionDrawer({{ $idx }})" style="cursor:pointer; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid rgba(255,255,255,0.05); transition:0.2s; display:block;">
      <div class="list-item-content">
        <div class="list-item-title" style="font-weight:600; font-size:1rem; color:var(--text-main);">
          Produced <span style="color:#2ecc71; font-weight:bold;">+{{ number_format($l['outputQty'], 2) }}kg</span> 
          <a href="{{ route('product.stock.history', ['productId' => $l['outputProductId'], 'stage' => 'FINISHED', 'grade' => $l['outputGrade'], 'from' => 'history']) }}" style="color: inherit; text-decoration: none; border-bottom: 1px dashed rgba(255,255,255,0.5);" onclick="event.stopPropagation()">
            {{ $l['outputName'] }}
          </a>

          @if($l['outputGrade'] && $l['outputGrade'] !== 'NONE')
            <span class="badge badge-info">{{ $l['outputGrade'] }}</span>
          @endif
          @if($l['notes'])
            <span style="font-weight:normal; font-size:0.8rem; color:var(--text-muted);">· {{ $l['notes'] }}</span>
          @endif
        </div>
        <div class="list-item-meta" style="margin-top:6px;">
          <div style="color:var(--secondary); font-size:0.8rem; margin-bottom:4px;">{{ \Carbon\Carbon::parse($l['date'])->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
          <div style="font-size:0.75rem; color:var(--text-muted); line-height:1.3;">Using: {!! $inputList !!}</div>
        </div>
      </div>
    </div>
  @empty
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No historical production logs found.
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

<h3 style="margin-top: 2rem; margin-bottom: 1rem;">🛒 Purchase Request History</h3>
<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Material</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse($pageData['purchaseOrders'] ?? [] as $po)
        <tr>
          <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
          <td>{{ $po->quantity }} kg</td>
          <td>
             <span class="badge {{ $po->status === 'DONE' ? 'badge-done' : 'badge-pending' }}">
                {{ $po->status === 'DONE' ? 'READ' : $po->status }}
             </span>
          </td>
          <td style="font-size:0.8rem;">{{ \Carbon\Carbon::parse($po->created_at)->format('d M Y') }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center text-muted">No purchase orders found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Populate the global _historyLogs so JS app.openProductionDrawer() can read it
    window._historyLogs = @json($paginatedArray);
  });
</script>
@endsection
