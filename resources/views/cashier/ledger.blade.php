@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $dateRange = request('range', 'this_month');
  $startDate = request('start', '');
  $endDate = request('end', '');

  $filtered = collect($pageData['transactions'] ?? []);

  if ($q) {
    $filtered = $filtered->filter(function($t) use ($q) {
      $query = strtolower($q);
      $details = ($t['note'] ?? '') . ' ' . ($t['description'] ?? '') . ' ' . ($t['reference'] ?? '') . ' ' . ($t['category'] ?? '');
      return str_contains(strtolower($details), $query) || str_contains((string)$t['amount'], $query);
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

  $summary = $pageData['summary'] ?? ['totalIn' => 0, 'totalOut' => 0, 'balance' => 0];
@endphp

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">💰 Account Ledger</h2>
  <button class="btn btn-sm" style="width:auto; padding:0.5rem 1.1rem; display:flex; align-items:center; gap:0.4rem;" onclick="app.downloadCashierPdf()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export PDF
  </button>
</div>

<!-- Summary Cards -->
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:1.2rem;">
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0;">
    <div style="font-size:0.7rem; color:var(--text-muted); margin-bottom:4px;">INCOME</div>
    <div style="font-weight:bold; color:var(--secondary); font-size:1.1rem;">₹{{ number_format($summary['totalIn'], 2) }}</div>
  </div>
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0;">
    <div style="font-size:0.7rem; color:var(--text-muted); margin-bottom:4px;">EXPENSE</div>
    <div style="font-weight:bold; color:var(--danger); font-size:1.1rem;">₹{{ number_format($summary['totalOut'], 2) }}</div>
  </div>
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0; background:{{ $summary['balance'] >= 0 ? 'rgba(34,197,94,0.08)' : 'rgba(239,68,68,0.08)' }}">
    <div style="font-size:0.7rem; color:var(--text-muted); margin-bottom:4px;">BALANCE</div>
    <div style="font-weight:bold; color:{{ $summary['balance'] >= 0 ? 'var(--secondary)' : 'var(--danger)' }}; font-size:1.1rem;">₹{{ number_format($summary['balance'], 2) }}</div>
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
      <input type="date" name="start" value="{{ $startDate }}" onchange="this.form.submit()" style="width:auto; padding:0.4rem; border-radius:4px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      <input type="date" name="end" value="{{ $endDate }}" onchange="this.form.submit()" style="width:auto; padding:0.4rem; border-radius:4px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    @endif
  </div>

  <div class="form-group">
    <input type="text" name="q" placeholder="Search note, category or amount..." value="{{ $q }}" onchange="this.form.submit()" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
</form>

<!-- Transaction Table -->
<div class="card" style="padding:0; overflow:hidden;">
  <div class="table-container">
    <table style="font-size:0.85rem; width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:rgba(0,0,0,0.2);">
          <th style="padding:12px; text-align:left;">Date</th>
          <th style="padding:12px; text-align:left;">Details</th>
          <th style="padding:12px; text-align:left;">Category</th>
          <th style="padding:12px; text-align:right;">Amount</th>
          <th style="padding:12px; text-align:center;">Bills</th>
          <th style="padding:12px; text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($paginated as $t)
          <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
            <td style="padding:12px; font-size:0.75rem; white-space:nowrap;">
              {{ \Carbon\Carbon::parse($t['date'])->format('d/m/Y') }}<br>
              <span style="color:var(--text-muted);">{{ \Carbon\Carbon::parse($t['date'])->format('h:i A') }}</span>
            </td>
            <td style="padding:12px;">
              <div style="font-weight:600;">{{ $t['note'] ?: 'Cash ' . $t['type'] }}</div>
              @if($t['description'])
                <div style="font-size:0.72rem; color:var(--text-muted);">{{ $t['description'] }}</div>
              @endif
              @if($t['reference'])
                <div style="font-size:0.72rem; color:var(--text-muted);">Ref: {{ $t['reference'] }}</div>
              @endif
            </td>
            <td style="padding:12px;">
              <span style="font-size:0.75rem; background:rgba(0,0,0,0.2); padding:2px 8px; border-radius:10px; font-weight:bold; white-space:nowrap;">
                {{ strtoupper(str_replace('_', ' ', $t['category'])) }}
              </span>
              @if($t['site'])
                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">📍 {{ $t['site'] }}</div>
              @endif
            </td>
            <td style="padding:12px; font-weight:bold; color:{{ $t['type'] === 'IN' ? 'var(--secondary)' : 'var(--danger)' }}; text-align:right; white-space:nowrap;">
              {{ $t['type'] === 'IN' ? '+' : '-' }}₹{{ number_format($t['amount'], 2) }}
            </td>
            <td style="padding:12px; text-align:center; min-width:80px;">
              @if(!empty($t['bills']))
                <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:4px;">
                  @foreach($t['bills'] as $b)
                    <button onclick="app.viewBill({{ $b['id'] }}, '{{ $b['file_type'] }}')" title="View {{ $b['original_name'] }}"
                      style="background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.3); color:#60a5fa; border-radius:6px; padding:2px 6px; font-size:0.72rem; cursor:pointer; white-space:nowrap;">
                      {{ $b['file_type'] === 'pdf' ? '📄' : '🖼️' }} {{ strlen($b['original_name']) > 10 ? substr($b['original_name'], 0, 10) . '…' : $b['original_name'] }}
                    </button>
                  @endforeach
                </div>
              @else
                <span style="color:var(--text-muted); font-size:0.75rem;">No bills</span>
              @endif
            </td>
            <td style="padding:12px; text-align:center; min-width:60px;">
              <div style="display:flex; justify-content:center; gap:4px;">
                <button onclick="app.showBillUpload({{ $t['id'] }})" title="Attach/Manage Bills"
                  style="background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); color:#4ade80; border-radius:6px; padding:4px 8px; font-size:0.75rem; cursor:pointer;">
                  📎
                </button>
                <button onclick="app.editTransaction({{ $t['id'] }})" title="Edit"
                  style="background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.3); color:#60a5fa; border-radius:6px; padding:4px 8px; font-size:0.75rem; cursor:pointer;">
                  ✏️
                </button>
                <button onclick="app.deleteTransaction({{ $t['id'] }})" title="Delete"
                  style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#f87171; border-radius:6px; padding:4px 8px; font-size:0.75rem; cursor:pointer;">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted" style="padding:20px;">No transactions found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
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
    window.serverPageData = @json($pageData);
  });
</script>
@endsection
