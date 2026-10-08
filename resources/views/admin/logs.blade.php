@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  @if(session('success'))
    <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:0.8rem 1.2rem; border-radius:8px; margin-bottom:1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
      <span>✓ {{ session('success') }}</span>
      <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; font-size:1.1rem; cursor:pointer;">&times;</button>
    </div>
  @endif

  <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
    <h2 style="margin:0;">🕐 System Activity Logs</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <select id="blade-cat-filter" class="btn-sm" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="applyBladeFilters()">
        <option value="">All Categories</option>
        <option value="Production">Production</option>
        <option value="Sales">Sales</option>
        <option value="Dispatch">Dispatch</option>
        <option value="Purchase">Purchase</option>
        <option value="Inventory">Inventory</option>
        <option value="Cashier">Cashier</option>
      </select>
      <select id="blade-user-filter" class="btn-sm" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="applyBladeFilters()">
        <option value="">All Users</option>
        @foreach($pageData['users'] as $u)
          <option value="{{ $u['name'] }}">{{ $u['name'] }} ({{ $u['role'] }})</option>
        @endforeach
      </select>
      <input type="date" id="blade-date-filter" class="btn-sm" style="background:var(--glass-bg); color:white; border:1px solid var(--glass-border); padding:5px 10px;" onchange="applyBladeFilters()">
      <button class="btn btn-sm btn-secondary" onclick="resetBladeFilters()">Reset</button>
      @if(empty($isReadOnly))
      <button type="button" class="btn btn-sm" onclick="confirmClearLogs()" style="background:#dc2626; border:1px solid #dc2626; color:#ffffff !important; font-weight:700; padding:5px 12px; border-radius:6px; cursor:pointer; width:auto;" title="Clear all activity logs from view">
        🗑️ Clear All Logs
      </button>
      @endif
    </div>
  </div>

  <div class="card" style="padding:1.2rem;">
    <div class="table-container">
      <table id="logs-table">
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Category</th>
            <th>Activity Description</th>
            <th>Performed By</th>
          </tr>
        </thead>
        <tbody id="logs-tbody">
          @forelse($pageData['logs'] as $log)
          <tr class="log-row" data-category="{{ $log['category'] }}" data-user="{{ $log['by'] }}" data-date="{{ explode(' ', $log['date'])[0] }}">
            <td style="font-size:0.85rem; font-family:monospace; color:var(--text-muted);">
              {{ \Carbon\Carbon::parse($log['date'])->format('d-m-Y, H:i') }}
            </td>
            <td>
              <span class="badge {{ $log['category'] === 'Production' ? 'badge-pending' : ($log['category'] === 'Sales' ? 'badge-open' : ($log['category'] === 'Inventory' ? 'badge-closed' : ($log['category'] === 'Cashier' ? 'badge-open' : 'badge-done'))) }}" style="font-size:0.7rem;">
                {{ $log['category'] }}
              </span>
            </td>
            <td style="font-size:0.9rem;">
              <div style="font-weight:600; color:var(--text-main);">{{ $log['description'] }}</div>
            </td>
            <td>
              <div style="font-weight:bold;">{{ $log['by'] ?? 'System' }}</div>
              <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">{{ $log['role'] ?? '' }}</div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" style="text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:1rem;">
              No activity logs found.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function applyBladeFilters() {
  const cat = document.getElementById('blade-cat-filter').value;
  const user = document.getElementById('blade-user-filter').value;
  const date = document.getElementById('blade-date-filter').value;
  
  document.querySelectorAll('.log-row').forEach(row => {
    let show = true;
    if(cat && row.dataset.category !== cat) show = false;
    if(user && row.dataset.user !== user) show = false;
    if(date && row.dataset.date !== date) show = false;
    row.style.display = show ? '' : 'none';
  });
}

function resetBladeFilters() {
  document.getElementById('blade-cat-filter').value = '';
  document.getElementById('blade-user-filter').value = '';
  document.getElementById('blade-date-filter').value = '';
  applyBladeFilters();
}

@php
  $currentSlug = auth()->user()?->login_slug 
      ?? (request()->segment(1) === 'public' ? request()->segment(2) : request()->segment(1)) 
      ?: 'admin';
  $clearUrl = Route::has($currentSlug . '.logs.clear') 
      ? route($currentSlug . '.logs.clear') 
      : (Route::has('admin.logs.clear') ? route('admin.logs.clear') : url('admin/logs/clear'));
@endphp

function confirmClearLogs() {
  if (!confirm('Are you sure you want to permanently clear all activity logs? This action will clear the logs view while keeping current stock balances, users, and products 100% intact.')) {
    return;
  }
  const clearUrl = @json($clearUrl);
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = clearUrl;
  
  const csrf = document.createElement('input');
  csrf.type = 'hidden';
  csrf.name = '_token';
  csrf.value = '{{ csrf_token() }}';
  form.appendChild(csrf);
  
  document.body.appendChild(form);
  form.submit();
}
</script>
@endsection
