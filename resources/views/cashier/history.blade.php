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
      return str_contains(strtolower($t['note'] ?? ''), $query) ||
             str_contains(strtolower($t['category'] ?? ''), $query) ||
             str_contains(strtolower($t['description'] ?? ''), $query) ||
             str_contains(strtolower((string)$t['amount']), $query);
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

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">💰 Transactions History</h2>
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

<div style="display:flex; flex-direction:column; gap:10px;">
  @forelse($paginated as $idx => $t)
    <div class="card transaction-card" style="margin-bottom:0; padding:0; overflow:hidden; border-radius:12px; border:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:var(--card-bg, rgba(255,255,255,0.03)); transition:all 0.2s ease;">
      <!-- Clickable Header Row -->
      <div onclick="toggleTransactionAccordion('tx-acc-{{ $t['id'] }}', this)" style="cursor:pointer; padding:1.1rem; display:flex; justify-content:space-between; align-items:center; user-select:none;">
        <div style="flex:1; padding-right:15px;">
          <div style="font-weight:600; font-size:1rem; color:var(--text-main); line-height:1.3;">
            {{ $t['note'] ?: 'Transaction' }}
          </div>
          <div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
            @if($t['category'])
              <span style="text-transform:uppercase; font-weight:600; background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:6px;">{{ str_replace('_', ' ', $t['category']) }}</span>
              <span>•</span>
            @endif
            <span>{{ \Carbon\Carbon::parse($t['date'])->format('d M Y, h:i A') }}</span>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:12px; text-align:right;">
          <div style="font-weight:bold; color:{{ $t['type']==='IN' ? '#16a34a' : '#ef4444' }}; font-size:1.15rem; white-space:nowrap;">
            {{ $t['type']==='IN' ? '+' : '-' }}₹{{ number_format($t['amount'], 2) }}
          </div>
          <div class="acc-chevron" style="transition:transform 0.25s ease; color:var(--text-muted); display:flex; align-items:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>
      </div>

      <!-- Expandable Details Dropdown / Collapsible -->
      <div id="tx-acc-{{ $t['id'] }}" class="tx-accordion-content" style="display:none; padding:1.2rem; border-top:1px solid var(--glass-border, rgba(255,255,255,0.06)); background:rgba(0,0,0,0.02);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:1rem; margin-bottom:1rem;">
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Type</div>
            <div style="font-weight:700; color:{{ $t['type']==='IN' ? '#16a34a' : '#ef4444' }};">
              <span style="display:inline-block; padding:2px 8px; border-radius:6px; background:{{ $t['type']==='IN' ? 'rgba(22,163,74,0.1)' : 'rgba(239,68,68,0.1)' }}; font-size:0.85rem;">
                {{ $t['type'] === 'IN' ? 'IN' : 'OUT' }}
              </span>
            </div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Amount</div>
            <div style="font-weight:700; font-size:1.15rem; color:{{ $t['type']==='IN' ? '#16a34a' : '#ef4444' }};">
              {{ $t['type']==='IN' ? '+' : '-' }}₹{{ number_format($t['amount'], 2) }}
            </div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Category</div>
            <div style="font-weight:600; font-size:0.9rem; text-transform:uppercase;">
              {{ str_replace('_', ' ', $t['category'] ?? 'GENERAL') }}
            </div>
          </div>
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Date & Time</div>
            <div style="font-size:0.85rem; font-weight:500;">
              {{ \Carbon\Carbon::parse($t['date'])->format('d M Y, h:i:s A') }}
            </div>
          </div>
          @if(!empty($t['reference']))
          <div>
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Reference</div>
            <div style="font-size:0.85rem; font-weight:500;">{{ $t['reference'] }}</div>
          </div>
          @endif
        </div>

        @if(!empty($t['note']) || !empty($t['description']))
          <div style="margin-bottom:1rem; padding:0.8rem; background:rgba(0,0,0,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:3px;">Note</div>
            <div style="font-size:0.88rem; color:var(--text-main); line-height:1.4;">
              {{ $t['note'] ?: '—' }}
              @if(!empty($t['description']) && $t['description'] !== $t['note'])
                <div style="margin-top:4px; font-size:0.82rem; color:var(--text-muted);">{{ $t['description'] }}</div>
              @endif
            </div>
          </div>
        @endif

        @if(!empty($t['bills']) && count($t['bills']) > 0)
          <div style="margin-bottom:1rem;">
            <div style="color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; font-weight:600; margin-bottom:6px;">Attached Bills</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
              @foreach($t['bills'] as $b)
                <button onclick="app.viewBill({{ $b['id'] }}, '{{ $b['file_type'] }}')" title="View {{ $b['original_name'] }}"
                  style="background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.25); color:#2563eb; border-radius:6px; padding:4px 10px; font-size:0.8rem; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                  <span>{{ $b['file_type'] === 'pdf' ? '📄' : '🖼️' }}</span>
                  <span>{{ $b['original_name'] }}</span>
                </button>
              @endforeach
            </div>
          </div>
        @endif

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:0.8rem;">
          <button class="btn btn-sm" onclick="app.editTransaction({{ $t['id'] }})" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Edit Transaction
          </button>
          <button class="btn btn-sm btn-secondary" onclick="app.showBillUpload({{ $t['id'] }})" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; display:inline-flex; align-items:center; gap:5px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
            Manage Bills
          </button>
          <button class="btn btn-sm" onclick="app.deleteTransaction({{ $t['id'] }})" style="width:auto; padding:0.45rem 1rem; font-size:0.82rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; display:inline-flex; align-items:center; gap:5px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            Delete
          </button>
        </div>
      </div>
    </div>
  @empty
    <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
      No historical transactions found.
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
    window.serverPageData = @json($pageData);
  });

  function toggleTransactionAccordion(id, headerEl) {
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