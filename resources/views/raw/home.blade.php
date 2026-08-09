@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'stock');
@endphp
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">🌿 Raw Material Overview</h2>
  <a class="btn btn-sm btn-secondary" style="width:auto; padding:0.5rem 1rem; text-decoration:none;" href="{{ url('raw/po') }}">PO Received by System</a>
</div>

<div class="tabs" style="margin-bottom:1rem;">
  <a class="tab-btn {{ $tab === 'stock' ? 'active' : '' }}" href="?tab=stock" style="text-decoration:none;">Stock</a>
  <a class="tab-btn {{ $tab === 'inward' ? 'active' : '' }}" href="?tab=inward" style="text-decoration:none;">Inward</a>
  <a class="tab-btn {{ $tab === 'outward' ? 'active' : '' }}" href="?tab=outward" style="text-decoration:none;">Outward</a>
</div>

@include('partials.recent-pos', ['pageData' => $pageData])

@if($tab === 'stock')
  <div class="responsive-grid" style="margin-top:1rem;">
    @forelse($pageData['rawStock'] as $s)
      <div class="card" style="padding: 1rem; margin-bottom: 0;">
        <div class="flex-between">
          <div>
            <div style="font-weight:600; font-size:1.1rem;">
              <a href="{{ route('product.stock.history', ['productId' => $s['productId'] ?? $s['id'], 'stage' => 'RAW', 'grade' => $s['grade'], 'from' => 'home']) }}" style="color: inherit; text-decoration: none; border-bottom: 1px dashed rgba(255,255,255,0.5);">
                {{ $s['name'] }}
              </a>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:1.4rem; font-weight:bold; color:var(--primary-light);">{{ number_format($s['quantity'], 2) }} <span style="font-size:0.9rem; color:var(--text-muted);">{{ $s['unit'] }}</span></div>
          </div>
        </div>
      </div>
    @empty
      <div class="card" style="grid-column: 1/-1;"><p class="text-center text-muted">No raw stock available.</p></div>
    @endforelse
  </div>
@else
  @php
    $q = request('q', '');
    $dateRange = request('range', 'this_week');
    $startDate = request('start', '');
    $endDate = request('end', '');

    $filtered = collect($pageData['rawLedger']);
    if ($tab === 'inward') {
      $filtered = $filtered->filter(fn($s) => $s['quantity'] > 0);
    } else {
      $filtered = $filtered->filter(fn($s) => $s['quantity'] < 0);
    }

    if ($q) {
      $filtered = $filtered->filter(fn($s) => str_contains(strtolower($s['productName']), strtolower($q)));
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
    $perPage = 10;
    $total = $filtered->count();
    $totalPages = ceil($total / $perPage);
    $paginated = $filtered->slice(($page - 1) * $perPage, $perPage);
  @endphp

  <form method="GET" action="" style="margin-bottom:1rem; display:flex; flex-direction:column; gap:10px;">
    <input type="hidden" name="tab" value="{{ $tab }}">
    
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
        <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()" style="width:auto; padding:0.4rem; border-radius:4px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
        <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()" style="width:auto; padding:0.4rem; border-radius:4px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      @endif
    </div>

    <div class="form-group">
      <input type="text" name="q" placeholder="Search product..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>
  </form>

  <div class="table-container" style="overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch;">
    <table style="width: 100%; min-width: 800px; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="white-space: nowrap;">Product</th>
          <th style="white-space: nowrap;">Qty</th>
          <th style="white-space: nowrap;">Date</th>
          <th style="white-space: nowrap;">Notes</th>
        </tr>
      </thead>
      <tbody>
        @forelse($paginated as $s)
          <tr>
            <td style="white-space: nowrap;">{{ $s['productName'] }}</td>
            <td style="font-weight:bold; color:{{ $s['quantity'] > 0 ? 'var(--secondary)' : 'var(--danger)' }}; white-space: nowrap;">
              {{ $s['quantity'] > 0 ? '+' : '' }}{{ number_format($s['quantity'], 2) }} kg
            </td>
            <td style="font-size:0.8rem; white-space: nowrap;">{{ \Carbon\Carbon::parse($s['date'])->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
            <td style="font-size:0.9rem; min-width: 300px; max-width: 500px; overflow-wrap: break-word; white-space: normal; word-break: break-word; vertical-align: middle;">{{ $s['notes'] ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted">No records found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($totalPages > 1)
    <div style="display:flex; justify-content:center; gap:8px; margin-top:1rem;">
      @if($page > 1)
        <a class="btn btn-sm btn-secondary" href="?tab={{ $tab }}&range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&q={{ $q }}&page={{ $page - 1 }}" style="width:auto; text-decoration:none;">&laquo; Prev</a>
      @endif
      <span style="align-self:center; color:var(--text-muted);">Page {{ $page }} of {{ $totalPages }}</span>
      @if($page < $totalPages)
        <a class="btn btn-sm btn-secondary" href="?tab={{ $tab }}&range={{ $dateRange }}&start={{ $startDate }}&end={{ $endDate }}&q={{ $q }}&page={{ $page + 1 }}" style="width:auto; text-decoration:none;">Next &raquo;</a>
      @endif
    </div>
  @endif
@endif
@endsection
