@extends('layouts.app')

@section('content')
@php
  $q = request('q', '');
  $category = request('category', '');
  $specificDate = request('specific_date', '');
  $dateRange = request('range', 'this_month');
  $startDate = request('start', '');
  $endDate = request('end', '');
  $activeTab = request('tab', 'personal'); // personal, team, daily
  $teamMember = request('team_member', 'all'); // 'all' or specific user ID / name

  $sourceData = $activeTab === 'team' ? ($pageData['teamTransactions'] ?? []) : ($pageData['transactions'] ?? []);
  $filtered = collect($sourceData);

  if ($activeTab === 'team' && $teamMember && $teamMember !== 'all') {
    $filtered = $filtered->filter(function($t) use ($teamMember) {
      return (string)($t['user_id'] ?? '') === (string)$teamMember 
          || strtolower($t['cashier_name'] ?? '') === strtolower($teamMember);
    });
  }

  if ($category) {
    $filtered = $filtered->filter(function($t) use ($category) {
      return strtolower(str_replace(' ', '_', $t['category'] ?? '')) === $category;
    });
  }

  if ($specificDate) {
    $filtered = $filtered->filter(function($t) use ($specificDate) {
      return str_starts_with($t['date'], $specificDate);
    });
  }

  if ($q) {
    $filtered = $filtered->filter(function($t) use ($q) {
      $query = strtolower($q);
      $dateStr = \Carbon\Carbon::parse($t['date'])->format('d-m-Y');
      $details = ($t['note'] ?? '') . ' ' . ($t['description'] ?? '') . ' ' . ($t['reference'] ?? '') . ' ' . ($t['category'] ?? '') . ' ' . ($t['cashier_name'] ?? '') . ' ' . $dateStr;
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
    } elseif ($dateRange === 'custom') {
      if ($startDate) $start = \Carbon\Carbon::parse($startDate)->startOfDay();
      if ($endDate) $end = \Carbon\Carbon::parse($endDate)->endOfDay();
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
  $perPage = 50;
  $total = $filtered->count();
  $totalPages = ceil($total / $perPage);
  $paginated = $filtered->slice(($page - 1) * $perPage, $perPage);
  $paginatedArray = $paginated->values()->toArray();

  $summary = [
    'totalIn'  => $filtered->where('type', 'IN')->sum('amount'),
    'totalOut' => $filtered->where('type', 'OUT')->sum('amount'),
    'balance'  => $filtered->where('type', 'IN')->sum('amount') - $filtered->where('type', 'OUT')->sum('amount'),
  ];
@endphp

<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <div style="display:flex; align-items:center; gap:15px;">
    <h2 style="margin:0;">💰 Account Ledger</h2>
  </div>
  <button class="btn btn-sm" style="width:auto; padding:0.5rem 1.1rem; display:flex; align-items:center; gap:0.4rem; font-weight:600;" onclick="app.downloadCashierPdf()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export PDF
  </button>
</div>

<!-- Summary Cards -->
<div id="ledger-summary-cards" style="display:{{ $activeTab === 'daily' ? 'none' : 'grid' }}; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:1.2rem;">
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0;">
    <div style="color:var(--text-muted); font-size:0.75rem; margin-bottom:4px; text-transform:uppercase;">Total IN</div>
    <div id="kpi-total-in" style="color:#16a34a; font-weight:700; font-size:1.1rem;">₹{{ number_format($summary['totalIn'], 2) }}</div>
  </div>
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0;">
    <div style="color:var(--text-muted); font-size:0.75rem; margin-bottom:4px; text-transform:uppercase;">Total OUT</div>
    <div id="kpi-total-out" style="color:#dc2626; font-weight:700; font-size:1.1rem;">₹{{ number_format($summary['totalOut'], 2) }}</div>
  </div>
  <div class="card" style="padding:12px; text-align:center; margin-bottom:0;">
    <div style="color:var(--text-muted); font-size:0.75rem; margin-bottom:4px; text-transform:uppercase;">Balance</div>
    <div id="kpi-balance" style="color:{{ $summary['balance'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight:700; font-size:1.1rem;">
      ₹{{ number_format($summary['balance'], 2) }}
    </div>
  </div>
</div>

<!-- Filters Form -->
<form id="ledger-filter-form" method="GET" action="{{ url()->current() }}" onsubmit="event.preventDefault(); applyLedgerFilters();" style="margin-bottom:1.2rem;">
  <div class="form-group" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:1rem;">
    
    <!-- Ledger Type (Personal / Team / Day Wise) -->
    <select name="tab" id="ledger-tab-select" onchange="onLedgerTabChange(this.value)" style="width:auto; flex:1; min-width:160px; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
      <option value="personal" {{ $activeTab==='personal'?'selected':'' }}>PERSONAL LEDGER</option>
      <option value="team" {{ $activeTab==='team'?'selected':'' }}>TEAM LEDGER</option>
      <option value="daily" {{ $activeTab==='daily'?'selected':'' }}>DAY WISE BALANCE</option>
    </select>

    <!-- Team Member Selector (Shown when tab === 'team') -->
    <select id="team-member-select" name="team_member" onchange="applyLedgerFilters()" style="display:{{ $activeTab === 'team' ? 'block' : 'none' }}; width:auto; flex:1; min-width:160px; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600;">
      <option value="all" {{ $teamMember === 'all' ? 'selected' : '' }}>ALL TEAM MEMBERS</option>
      @foreach($pageData['teamMembers'] ?? [] as $tm)
        <option value="{{ $tm['id'] }}" {{ ((string)$teamMember === (string)$tm['id'] || strtolower($teamMember) === strtolower($tm['name'])) ? 'selected' : '' }}>
          {{ strtoupper($tm['name']) }}
        </option>
      @endforeach
    </select>

    <!-- Category Filter -->
    <select name="category" id="ledger-category-select" onchange="applyLedgerFilters()" style="width:auto; flex:1; min-width:150px; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      <option value="">ALL CATEGORIES</option>
      @foreach($pageData['categories'] ?? [] as $c)
        <option value="{{ $c['value'] }}" {{ $category===$c['value']?'selected':'' }}>{{ strtoupper($c['label']) }}</option>
      @endforeach
    </select>

    <!-- Specific Date -->
    <input type="date" name="specific_date" id="ledger-specific-date" value="{{ request('specific_date') }}" onchange="applyLedgerFilters()" style="width:auto; flex:1; min-width:140px; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);" title="Search by specific date">

    <!-- Date Range -->
    <select name="range" id="ledger-range-select" onchange="toggleCustomDates(this.value); applyLedgerFilters();" style="width:auto; flex:1; min-width:140px; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      <option value="today" {{ $dateRange==='today'?'selected':'' }}>Today</option>
      <option value="this_week" {{ $dateRange==='this_week'?'selected':'' }}>This Week</option>
      <option value="last_week" {{ $dateRange==='last_week'?'selected':'' }}>Last Week</option>
      <option value="this_month" {{ $dateRange==='this_month'?'selected':'' }}>This Month</option>
      <option value="last_month" {{ $dateRange==='last_month'?'selected':'' }}>Last Month</option>
      <option value="custom" {{ $dateRange==='custom'?'selected':'' }}>Custom Range</option>
      <option value="all" {{ $dateRange==='all'?'selected':'' }}>All Time</option>
    </select>
    
    <div id="custom-dates-container" style="display:{{ $dateRange === 'custom' ? 'flex' : 'none' }}; gap:8px;">
      <input type="date" name="start" id="ledger-start-date" value="{{ $startDate ?? '' }}" onchange="applyLedgerFilters()" style="width:auto; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      <input type="date" name="end" id="ledger-end-date" value="{{ $endDate ?? '' }}" onchange="applyLedgerFilters()" style="width:auto; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    </div>
  </div>

  <!-- Filter buttons -->
  <div id="table-filter-external-container" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <div style="display:flex; gap:10px;">
      <button type="button" onclick="applyLedgerFilters()" class="btn btn-sm" style="padding:0.4rem 1rem; font-weight:600;">Apply</button>
      <button type="button" onclick="resetLedgerFilters()" class="btn btn-secondary btn-sm" style="padding:0.4rem 1rem; font-weight:600;">Reset</button>
    </div>
  </div>

  <div id="ledger-search-container" class="form-group" style="display:{{ $activeTab === 'daily' ? 'none' : 'flex' }}; gap:10px; margin-bottom:1rem;">
    <input type="text" name="q" id="ledger-search-input" placeholder="Search details or amount..." value="{{ $q }}" oninput="applyLedgerFilters()" style="flex:1; padding:0.65rem 0.9rem; font-size:0.9rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    <div id="team-visibility-btn-wrapper" style="display:{{ $activeTab === 'team' ? 'block' : 'none' }};">
      <button type="button" class="btn btn-sm" style="background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.3); padding:0.65rem 12px; border-radius:8px; display:flex; align-items:center; gap:6px; white-space:nowrap; height:100%;" onclick="showVisibilityInfo()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <span>Visibility Info</span>
      </button>
    </div>
  </div>
</form>

<!-- Transaction Table -->
<div class="card" style="padding:0; overflow:hidden;">
  <div style="overflow-x:auto;">
    <!-- Standard Transaction Table -->
    <table id="ledger-tx-table" style="display:{{ $activeTab === 'daily' ? 'none' : 'table' }}; font-size:0.85rem; width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:rgba(0,0,0,0.05); border-bottom:1px solid var(--border-soft, #DDCFAF);">
          <th style="padding:12px; text-align:left;">Date</th>
          <th style="padding:12px; text-align:left;">Details</th>
          <th style="padding:12px; text-align:left;">Category</th>
          <th style="padding:12px; text-align:right;">Amount</th>
          <th style="padding:12px; text-align:center;">Bills</th>
          <th style="padding:12px; text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody id="ledger-tx-tbody">
        @forelse($paginated as $t)
          <tr style="border-bottom:1px solid var(--border-soft, #DDCFAF);">
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
              @if($activeTab === 'team' && isset($t['cashier_name']))
                <div style="font-size:0.75rem; color:var(--primary-dark, #b45309); font-weight:700; margin-top:4px;">👤 {{ $t['cashier_name'] }}</div>
              @endif
            </td>
            <td style="padding:12px;">
              <span style="font-size:0.75rem; background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:10px; font-weight:bold; white-space:nowrap;">
                {{ strtoupper(str_replace('_', ' ', $t['category'])) }}
              </span>
              @if($t['site'])
                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">📍 {{ $t['site'] }}</div>
              @endif
            </td>
            <td style="padding:12px; font-weight:bold; color:{{ $t['type'] === 'IN' ? '#16a34a' : '#dc2626' }}; text-align:right; white-space:nowrap;">
              {{ $t['type'] === 'IN' ? '+' : '-' }}₹{{ number_format($t['amount'], 2) }}
            </td>
            <td style="padding:12px; text-align:center; min-width:80px;">
              @if(!empty($t['bills']))
                <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:4px;">
                  @foreach($t['bills'] as $b)
                    <button onclick="app.viewBill({{ $b['id'] }}, '{{ $b['file_type'] }}')" title="View {{ $b['original_name'] }}" style="background:none; border:none; cursor:pointer; color:var(--primary); padding:2px;">
                      📎
                    </button>
                  @endforeach
                </div>
              @else
                <span style="color:var(--text-muted); font-size:0.75rem;">No Bills</span>
              @endif
            </td>
            <td style="padding:12px; text-align:center;">
              <div style="display:flex; justify-content:center; gap:6px;">
                <button class="btn btn-sm" onclick="app.uploadBillPrompt({{ $t['id'] }})" title="Attach Bill" style="padding:4px 8px; font-size:0.75rem;">📎</button>
                <button class="btn btn-sm btn-secondary" onclick="app.editTransaction({{ $t['id'] }})" title="Edit" style="padding:4px 8px; font-size:0.75rem;">✏️</button>
                <button class="btn btn-sm btn-danger" onclick="app.deleteTransaction({{ $t['id'] }})" title="Delete" style="padding:4px 8px; font-size:0.75rem;">🗑️</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="padding:2.5rem; text-align:center; color:var(--text-muted);">
              No transactions found matching criteria.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <!-- Daily Summary Table -->
    <table id="ledger-daily-table" style="display:{{ $activeTab === 'daily' ? 'table' : 'none' }}; font-size:0.85rem; width:100%; border-collapse:collapse;">
      <thead>
        <tr style="background:rgba(0,0,0,0.05); border-bottom:1px solid var(--border-soft, #DDCFAF);">
          <th style="padding:12px; text-align:left;">Date</th>
          <th style="padding:12px; text-align:right;">Total IN</th>
          <th style="padding:12px; text-align:right;">Total OUT</th>
          <th style="padding:12px; text-align:right;">Net Balance</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pageData['dailyData'] ?? [] as $d)
          <tr style="border-bottom:1px solid var(--border-soft, #DDCFAF);">
            <td style="padding:12px; font-weight:600;">{{ \Carbon\Carbon::parse($d['date'])->format('d M Y') }}</td>
            <td style="padding:12px; text-align:right; color:#16a34a; font-weight:600;">+₹{{ number_format($d['in'], 2) }}</td>
            <td style="padding:12px; text-align:right; color:#dc2626; font-weight:600;">-₹{{ number_format($d['out'], 2) }}</td>
            <td style="padding:12px; text-align:right; font-weight:bold; color:{{ $d['balance'] >= 0 ? '#16a34a' : '#dc2626' }}">
              {{ $d['balance'] >= 0 ? '+' : '' }}₹{{ number_format($d['balance'], 2) }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="padding:2rem; text-align:center; color:var(--text-muted);">No daily data available.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
  window.serverPageData = @json($pageData);

  function toggleCustomDates(val) {
    document.getElementById('custom-dates-container').style.display = (val === 'custom') ? 'flex' : 'none';
  }

  function onLedgerTabChange(tabVal) {
    const memberSelect = document.getElementById('team-member-select');
    const visBtn = document.getElementById('team-visibility-btn-wrapper');
    const summaryCards = document.getElementById('ledger-summary-cards');
    const searchContainer = document.getElementById('ledger-search-container');
    const txTable = document.getElementById('ledger-tx-table');
    const dailyTable = document.getElementById('ledger-daily-table');

    if (tabVal === 'team') {
      if (memberSelect) memberSelect.style.display = 'block';
      if (visBtn) visBtn.style.display = 'block';
      if (summaryCards) summaryCards.style.display = 'grid';
      if (searchContainer) searchContainer.style.display = 'flex';
      if (txTable) txTable.style.display = 'table';
      if (dailyTable) dailyTable.style.display = 'none';
    } else if (tabVal === 'personal') {
      if (memberSelect) memberSelect.style.display = 'none';
      if (visBtn) visBtn.style.display = 'none';
      if (summaryCards) summaryCards.style.display = 'grid';
      if (searchContainer) searchContainer.style.display = 'flex';
      if (txTable) txTable.style.display = 'table';
      if (dailyTable) dailyTable.style.display = 'none';
    } else if (tabVal === 'daily') {
      if (memberSelect) memberSelect.style.display = 'none';
      if (visBtn) visBtn.style.display = 'none';
      if (summaryCards) summaryCards.style.display = 'none';
      if (searchContainer) searchContainer.style.display = 'none';
      if (txTable) txTable.style.display = 'none';
      if (dailyTable) dailyTable.style.display = 'table';
    }

    applyLedgerFilters();
  }

  function applyLedgerFilters() {
    const tabVal = document.getElementById('ledger-tab-select').value;
    if (tabVal === 'daily') return;

    const memberSelect = document.getElementById('team-member-select');
    const selectedMember = memberSelect ? memberSelect.value : 'all';
    const catVal = (document.getElementById('ledger-category-select').value || '').toLowerCase();
    const specificDate = document.getElementById('ledger-specific-date').value;
    const rangeVal = document.getElementById('ledger-range-select').value;
    const startDate = document.getElementById('ledger-start-date').value;
    const endDate = document.getElementById('ledger-end-date').value;
    const q = (document.getElementById('ledger-search-input').value || '').trim().toLowerCase();

    // Pick source dataset
    const source = (tabVal === 'team') 
      ? (window.serverPageData?.teamTransactions || []) 
      : (window.serverPageData?.transactions || []);

    let filtered = [...source];

    // 1. Team member filter
    if (tabVal === 'team' && selectedMember && selectedMember !== 'all') {
      filtered = filtered.filter(t => {
        return String(t.user_id) === String(selectedMember) 
            || (t.cashier_name && t.cashier_name.toLowerCase() === selectedMember.toLowerCase());
      });
    }

    // 2. Category filter
    if (catVal) {
      filtered = filtered.filter(t => {
        const c = (t.category || '').toLowerCase().replace(/ /g, '_');
        return c === catVal;
      });
    }

    // 3. Specific Date filter
    if (specificDate) {
      filtered = filtered.filter(t => (t.date || '').startsWith(specificDate));
    }

    // 4. Date Range filter
    if (rangeVal && rangeVal !== 'all') {
      const now = new Date();
      let start = null;
      let end = new Date();
      end.setHours(23, 59, 59, 999);

      if (rangeVal === 'today') {
        start = new Date();
        start.setHours(0, 0, 0, 0);
      } else if (rangeVal === 'this_week') {
        start = new Date();
        const day = start.getDay();
        const diff = start.getDate() - day + (day === 0 ? -6 : 1);
        start.setDate(diff);
        start.setHours(0, 0, 0, 0);
      } else if (rangeVal === 'last_week') {
        const lastWeek = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        const day = lastWeek.getDay();
        const diff = lastWeek.getDate() - day + (day === 0 ? -6 : 1);
        start = new Date(lastWeek.setDate(diff));
        start.setHours(0, 0, 0, 0);
        end = new Date(start.getTime() + 6 * 24 * 60 * 60 * 1000);
        end.setHours(23, 59, 59, 999);
      } else if (rangeVal === 'this_month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
      } else if (rangeVal === 'last_month') {
        start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        end = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59);
      } else if (rangeVal === 'custom') {
        if (startDate) start = new Date(startDate + 'T00:00:00');
        if (endDate) end = new Date(endDate + 'T23:59:59');
      }

      if (start) {
        filtered = filtered.filter(t => {
          const d = new Date(t.date);
          return d >= start && d <= end;
        });
      }
    }

    // 5. Search filter
    if (q) {
      filtered = filtered.filter(t => {
        const dateStr = formatDate(t.date);
        const combined = `${t.note || ''} ${t.description || ''} ${t.reference || ''} ${t.category || ''} ${t.cashier_name || ''} ${dateStr} ${t.amount || ''}`.toLowerCase();
        return combined.includes(q);
      });
    }

    // Sort by date desc
    filtered.sort((a, b) => new Date(b.date) - new Date(a.date));

    // Update KPI summary cards
    let totalIn = 0;
    let totalOut = 0;
    filtered.forEach(t => {
      const amt = Number(t.amount) || 0;
      if (t.type === 'IN') totalIn += amt;
      else if (t.type === 'OUT') totalOut += amt;
    });
    const balance = totalIn - totalOut;

    const inEl = document.getElementById('kpi-total-in');
    const outEl = document.getElementById('kpi-total-out');
    const balEl = document.getElementById('kpi-balance');

    if (inEl) inEl.innerText = '₹' + totalIn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (outEl) outEl.innerText = '₹' + totalOut.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (balEl) {
      balEl.innerText = '₹' + balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      balEl.style.color = (balance >= 0) ? '#16a34a' : '#dc2626';
    }

    // Render Table Rows
    const tbody = document.getElementById('ledger-tx-tbody');
    if (!tbody) return;

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" style="padding:2.5rem; text-align:center; color:var(--text-muted);">
            No transactions found matching criteria.
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(t => {
      const d = new Date(t.date);
      const dateFormatted = String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
      let hours = d.getHours();
      const minutes = String(d.getMinutes()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12;
      const timeFormatted = String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;

      const amtFormatted = (t.type === 'IN' ? '+' : '-') + '₹' + Number(t.amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const amtColor = (t.type === 'IN') ? '#16a34a' : '#dc2626';

      let billsHtml = '<span style="color:var(--text-muted); font-size:0.75rem;">No Bills</span>';
      if (t.bills && t.bills.length > 0) {
        billsHtml = `
          <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:4px;">
            ${t.bills.map(b => `
              <button onclick="app.viewBill(${b.id}, '${b.file_type}')" title="View ${b.original_name || 'Bill'}" style="background:none; border:none; cursor:pointer; color:var(--primary); padding:2px;">
                📎
              </button>
            `).join('')}
          </div>
        `;
      }

      return `
        <tr style="border-bottom:1px solid var(--border-soft, #DDCFAF);">
          <td style="padding:12px; font-size:0.75rem; white-space:nowrap;">
            ${dateFormatted}<br>
            <span style="color:var(--text-muted);">${timeFormatted}</span>
          </td>
          <td style="padding:12px;">
            <div style="font-weight:600;">${t.note || ('Cash ' + t.type)}</div>
            ${t.description ? `<div style="font-size:0.72rem; color:var(--text-muted);">${t.description}</div>` : ''}
            ${t.reference ? `<div style="font-size:0.72rem; color:var(--text-muted);">Ref: ${t.reference}</div>` : ''}
            ${(tabVal === 'team' && t.cashier_name) ? `<div style="font-size:0.75rem; color:var(--primary-dark, #b45309); font-weight:700; margin-top:4px;">👤 ${t.cashier_name}</div>` : ''}
          </td>
          <td style="padding:12px;">
            <span style="font-size:0.75rem; background:rgba(0,0,0,0.06); padding:2px 8px; border-radius:10px; font-weight:bold; white-space:nowrap;">
              ${(t.category || '').replace(/_/g, ' ').toUpperCase()}
            </span>
            ${t.site ? `<div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">📍 ${t.site}</div>` : ''}
          </td>
          <td style="padding:12px; font-weight:bold; color:${amtColor}; text-align:right; white-space:nowrap;">
            ${amtFormatted}
          </td>
          <td style="padding:12px; text-align:center; min-width:80px;">
            ${billsHtml}
          </td>
          <td style="padding:12px; text-align:center;">
            <div style="display:flex; justify-content:center; gap:6px;">
              <button class="btn btn-sm" onclick="app.uploadBillPrompt(${t.id})" title="Attach Bill" style="padding:4px 8px; font-size:0.75rem;">📎</button>
              <button class="btn btn-sm btn-secondary" onclick="app.editTransaction(${t.id})" title="Edit" style="padding:4px 8px; font-size:0.75rem;">✏️</button>
              <button class="btn btn-sm btn-danger" onclick="app.deleteTransaction(${t.id})" title="Delete" style="padding:4px 8px; font-size:0.75rem;">🗑️</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    // Update browser URL query params without reloading page
    const params = new URLSearchParams();
    if (tabVal && tabVal !== 'personal') params.set('tab', tabVal);
    if (tabVal === 'team' && selectedMember && selectedMember !== 'all') params.set('team_member', selectedMember);
    if (catVal) params.set('category', catVal);
    if (specificDate) params.set('specific_date', specificDate);
    if (rangeVal && rangeVal !== 'this_month') params.set('range', rangeVal);
    if (rangeVal === 'custom') {
      if (startDate) params.set('start', startDate);
      if (endDate) params.set('end', endDate);
    }
    if (q) params.set('q', q);

    const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
    window.history.replaceState({}, '', newUrl);
  }

  function resetLedgerFilters() {
    document.getElementById('ledger-tab-select').value = 'personal';
    const memberSelect = document.getElementById('team-member-select');
    if (memberSelect) memberSelect.value = 'all';
    document.getElementById('ledger-category-select').value = '';
    document.getElementById('ledger-specific-date').value = '';
    document.getElementById('ledger-range-select').value = 'this_month';
    document.getElementById('ledger-start-date').value = '';
    document.getElementById('ledger-end-date').value = '';
    document.getElementById('ledger-search-input').value = '';
    toggleCustomDates('this_month');
    onLedgerTabChange('personal');
  }

  function formatDate(dStr) {
    if (!dStr) return '';
    const d = new Date(dStr);
    return String(d.getDate()).padStart(2, '0') + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + d.getFullYear();
  }

  // Background live sync every 4 seconds so newly added users & entries appear automatically
  function startLedgerAutoSync() {
    setInterval(() => {
      if (document.hidden) return;
      if (document.querySelector('.swal2-container')) return;

      fetch(window.location.pathname + '?sync=1', {
        headers: { 'Accept': 'application/json' }
      })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.success || !data.pageData) return;

        const newPageData = data.pageData;
        window.serverPageData = newPageData;

        // Check and update team member options in select dropdown
        const memberSelect = document.getElementById('team-member-select');
        if (memberSelect && newPageData.teamMembers) {
          const currentVal = memberSelect.value;
          const currentOptions = Array.from(memberSelect.options).map(o => String(o.value));
          const newMembers = newPageData.teamMembers;
          
          let needsUpdate = (newMembers.length + 1 !== currentOptions.length);
          if (!needsUpdate) {
            needsUpdate = newMembers.some(m => !currentOptions.includes(String(m.id)));
          }

          if (needsUpdate) {
            let html = `<option value="all" ${currentVal === 'all' ? 'selected' : ''}>ALL TEAM MEMBERS</option>`;
            newMembers.forEach(tm => {
              const isSelected = (String(currentVal) === String(tm.id) || currentVal.toLowerCase() === tm.name.toLowerCase());
              html += `<option value="${tm.id}" ${isSelected ? 'selected' : ''}>${tm.name}</option>`;
            });
            memberSelect.innerHTML = html;
          }
        }

        // Live update the table and KPI cards with newest data
        applyLedgerFilters();
      })
      .catch(() => {});
    }, 4000);
  }

  document.addEventListener('DOMContentLoaded', () => {
    startLedgerAutoSync();
  });

  function showVisibilityInfo() {
    const allowed = @json($pageData['allowedCashiers'] ?? []);
    const disallowed = @json($pageData['disallowedCashiers'] ?? []);
    
    let html = '<div style="text-align:left; font-size:0.85rem;">';
    html += '<h4 style="margin-top:0; color:#4ade80;">Allowed Cashiers (Visible):</h4>';
    if (allowed.length > 0) {
      html += '<ul>' + allowed.map(c => `<li>${c}</li>`).join('') + '</ul>';
    } else {
      html += '<p style="color:#888;">None configured</p>';
    }
    
    html += '<h4 style="margin-top:10px; color:#f87171;">Disallowed Cashiers (Hidden):</h4>';
    if (disallowed.length > 0) {
      html += '<ul>' + disallowed.map(c => `<li>${c}</li>`).join('') + '</ul>';
    } else {
      html += '<p style="color:#888;">None</p>';
    }
    html += '</div>';

    Swal.fire({
      title: 'Team Visibility Info',
      html: html,
      confirmButtonText: 'Close',
      confirmButtonColor: '#f59e0b',
    });
  }
</script>
@endsection