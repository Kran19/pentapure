@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1" style="flex-wrap: wrap; gap: 10px;">
    <h2 style="margin:0;">🕐 System Activity Logs</h2>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
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
          @foreach($pageData['logs'] as $log)
          <tr class="log-row" data-category="{{ $log['category'] }}" data-user="{{ $log['by'] }}" data-date="{{ explode(' ', $log['date'])[0] }}">
            <td style="font-size:0.85rem; font-family:monospace; color:var(--text-muted);">
              {{ \Carbon\Carbon::parse($log['date'])->format('d M Y, H:i') }}
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
          @endforeach
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
</script>
@endsection
