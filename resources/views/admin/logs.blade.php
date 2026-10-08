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
        <option value="Attendance">Attendance</option>
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
          @forelse($pageData['logs'] as $log)
          @php
            $cat = $log['category'] ?? 'General';
            $badgeBg = match($cat) {
              'Production' => 'background:#3b82f6; color:#ffffff !important;',
              'Sales'      => 'background:#10b981; color:#ffffff !important;',
              'Dispatch'   => 'background:#8b5cf6; color:#ffffff !important;',
              'Purchase'   => 'background:#f59e0b; color:#ffffff !important;',
              'Inventory'  => 'background:#06b6d4; color:#ffffff !important;',
              'Cashier'    => 'background:#ec4899; color:#ffffff !important;',
              'Attendance' => 'background:#14b8a6; color:#ffffff !important;',
              default      => 'background:#64748b; color:#ffffff !important;',
            };
            $logDate = explode('T', explode(' ', $log['date'] ?? '')[0])[0];
          @endphp
          <tr class="log-row" data-category="{{ $cat }}" data-user="{{ $log['by'] }}" data-date="{{ $logDate }}">
            <td style="font-size:0.85rem; font-family:monospace; color:var(--text-muted); white-space:nowrap;">
              {{ \Carbon\Carbon::parse($log['date'])->format('d-m-Y, h:i A') }}
            </td>
            <td>
              <span class="badge" style="font-size:0.75rem; padding:3px 8px; border-radius:6px; font-weight:700; {{ $badgeBg }}">
                {{ $cat }}
              </span>
            </td>
            <td style="font-size:0.9rem;">
              <div style="font-weight:600; color:var(--text-main);">{{ $log['description'] }}</div>
            </td>
            <td style="white-space:nowrap;">
              <div style="font-weight:bold; color:var(--text-main);">{{ $log['by'] ?? 'System' }}</div>
              @if(!empty($log['user_id']))
                <div style="font-size:0.75rem; color:var(--primary-light); font-weight:700; margin-top:2px;">
                  User ID: {{ $log['user_id'] }}
                </div>
              @endif
              @if(!empty($log['role']))
                <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">{{ $log['role'] }}</div>
              @endif
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

</script>
@endsection
