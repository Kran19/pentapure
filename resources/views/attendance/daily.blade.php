@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">{{ $authUser['role'] === 'ADMIN' ? '🔍 Daily Attendance Review' : '📝 Daily Attendance Entry' }}</h2>
    
    <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; align-items:center;">
      <label style="font-weight:bold;">Date:</label>
      <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc;">
      
      <label style="font-weight:bold; margin-left:10px;">Status:</label>
      <select onchange="filterAttendanceTable(this.value)" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc;">
        <option value="ALL">All</option>
        <option value="PRESENT">Present</option>
        <option value="ABSENT">Absent</option>
        <option value="HALF_DAY">Half Day</option>
      </select>
    </form>
  </div>

  <div class="card" style="padding:1rem;">
    <div style="display:flex; justify-content:space-between; margin-bottom:1rem; align-items:center;">
      <span style="color:var(--text-muted);">{{ $authUser['role'] === 'ADMIN' ? 'Reviewing' : 'Managing' }} active workers for <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong></span>
      <div style="display:flex; gap:10px;">
        <button class="btn btn-sm btn-secondary" onclick="window.print()" style="width:auto; padding:0.4rem 1rem;">🖨️ Print Daily Sheet</button>
        <button class="btn btn-sm btn-secondary" onclick="exportDailyToCSV()" style="width:auto; padding:0.4rem 1rem; background:#27ae60; color:white;">📗 Export to Sheet</button>
        @if($authUser['role'] === 'ATTENDANCE')
          <button class="btn btn-sm" onclick="markAllPresent()" style="width:auto; padding:0.4rem 1rem;">Mark All Present (9-6)</button>
        @endif
      </div>
    </div>

    <form id="bulk-attendance-form">
      <div class="table-container" style="max-height: 60vh; overflow-y:auto;">
        <table style="min-width: 900px;">
          <thead style="position:sticky; top:0; background:white; z-index:1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <tr>
              <th>Name & Dept</th>
              <th>Status</th>
              <th>In Time</th>
              <th>Out Time</th>
              <th>Break In</th>
              <th>Break Out</th>
            </tr>
          </thead>
          <tbody>
            @foreach($workers as $index => $w)
              @php
                $att = $w->attendances->first();
                $status = $att ? $att->status : 'ABSENT';
                $in = $att && $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('H:i') : '--:--';
                $out = $att && $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('H:i') : '--:--';
                $bin = $att && $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('H:i') : '--:--';
                $bout = $att && $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('H:i') : '--:--';
                $isAdmin = ($authUser['role'] === 'ADMIN');
              @endphp
              <tr class="attendance-row">
                <input type="hidden" name="attendances[{{$index}}][worker_id]" value="{{ $w->id }}">
                
                <td>
                  <div style="font-weight:600;">{{ $w->name }}</div>
                  <div style="font-size:0.75rem; color:var(--text-muted);">{{ $w->department->name ?? 'N/A' }} ({{ $w->shift_type }})</div>
                </td>
                <td>
                  @if($isAdmin)
                    <span class="badge" style="background: {{ $status === 'PRESENT' ? '#2ecc71' : ($status === 'HALF_DAY' ? '#f1c40f' : '#e74c3c') }}; color:white; padding:4px 8px; border-radius:4px; font-size:0.75rem;">
                        {{ $status }}
                    </span>
                  @else
                    <select name="attendances[{{$index}}][status]" class="status-select" onchange="toggleTimeFields(this)">
                        <option value="PRESENT" {{ $status=='PRESENT'?'selected':'' }}>Present</option>
                        <option value="HALF_DAY" {{ $status=='HALF_DAY'?'selected':'' }}>Half Day</option>
                        <option value="ABSENT" {{ $status=='ABSENT'?'selected':'' }}>Absent</option>
                    </select>
                  @endif
                </td>
                <td>
                  @if($isAdmin) <span style="font-weight:bold;">{{ $in }}</span> @else <input type="time" name="attendances[{{$index}}][in_time]" value="{{ $in === '--:--' ? '' : $in }}" class="time-in"> @endif
                </td>
                <td>
                  @if($isAdmin) <span style="font-weight:bold;">{{ $out }}</span> @else <input type="time" name="attendances[{{$index}}][out_time]" value="{{ $out === '--:--' ? '' : $out }}" class="time-out"> @endif
                </td>
                <td>
                  @if($isAdmin) <span style="font-size:0.85rem; color:var(--text-muted);">{{ $bin }}</span> @else <input type="time" name="attendances[{{$index}}][break_in]" value="{{ $bin === '--:--' ? '' : $bin }}" class="time-bin"> @endif
                </td>
                <td>
                  @if($isAdmin) <span style="font-size:0.85rem; color:var(--text-muted);">{{ $bout }}</span> @else <input type="time" name="attendances[{{$index}}][break_out]" value="{{ $bout === '--:--' ? '' : $bout }}" class="time-bout"> @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($authUser['role'] !== 'ADMIN')
        <div style="margin-top:1.5rem; text-align:right;">
          <button type="submit" class="btn" style="width:auto; padding:0.6rem 2rem;">Save All Records</button>
        </div>
      @endif
    </form>
  </div>
</div>

  <div id="printable-daily" style="display:none; background:white; color:black; padding:1.5rem;">
    <div style="text-align:center; border-bottom:2px solid #000; margin-bottom:1rem; padding-bottom:0.5rem;">
      <h2 style="margin:0;">PENTAPURE FOODS & SPICES PVT.LTD.</h2>
      <h3 style="margin:5px 0;">DAILY ATTENDANCE SHEET</h3>
      <div style="font-weight:bold;">DATE: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
    </div>

    @php
      $groupedWorkers = $workers->groupBy(fn($w) => $w->department->name ?? 'Other');
    @endphp

    @foreach($groupedWorkers as $deptName => $deptWorkers)
      <div style="margin-top:1.5rem;">
        <h4 style="margin:0 0 5px 0; border-bottom:1px solid #000; width:fit-content;">{{ strtoupper($deptName) }}</h4>
        <table style="width:100%; border-collapse:collapse; border:1px solid #000;">
          <thead>
            <tr style="background:#eee;">
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem;">S.R.NO</th>
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem; text-align:left;">STAFF</th>
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem;">IN TIME</th>
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem;">OUT TIME</th>
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem;">O.T.</th>
              <th style="border:1px solid #000; padding:4px; font-size:0.8rem;">SIGN</th>
            </tr>
          </thead>
          <tbody>
            @foreach($deptWorkers as $i => $w)
              @php $att = $w->attendances->first(); @endphp
              <tr style="border-bottom:1px solid #000;">
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $i + 1 }}</td>
                <td style="border:1px solid #000; padding:4px; font-weight:600;">{{ $w->name }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $att?->in_time ? date('H:i', strtotime($att->in_time)) : 'A' }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $att?->out_time ? date('H:i', strtotime($att->out_time)) : 'A' }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $att?->overtime_hours > 0 ? number_format($att->overtime_hours, 1) : '-' }}</td>
                <td style="border:1px solid #000; padding:4px; width:80px;"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endforeach

    <div style="margin-top:3rem; display:flex; justify-content:space-between; font-weight:bold; font-size:0.9rem;">
      <div>PREPARED BY</div>
      <div>CHECKED BY</div>
      <div>AUTHORIZED SIGNATORY</div>
    </div>
  </div>
</div>

<style>
@media print {
  body * { visibility: hidden; background:white !important; color:black !important; }
  #printable-daily, #printable-daily * { visibility: visible; }
  #printable-daily { display: block !important; position: absolute; left: 0; top: 0; width: 100%; box-shadow:none; padding:0; }
  .admin-sidebar, .admin-mobile-header, .flex-between, .card, nav { display: none !important; }
}
</style>

<script>
function toggleTimeFields(selectEl) {
  const row = selectEl.closest('tr');
  const inputs = row.querySelectorAll('input[type="time"]');
  if (selectEl.value === 'ABSENT') {
    inputs.forEach(i => { i.value = ''; i.disabled = true; });
  } else {
    inputs.forEach(i => { i.disabled = false; });
  }
}

// Initial toggle setup
document.querySelectorAll('.status-select').forEach(sel => toggleTimeFields(sel));

function markAllPresent() {
  document.querySelectorAll('.attendance-row').forEach(row => {
    const status = row.querySelector('.status-select');
    status.value = 'PRESENT';
    toggleTimeFields(status);
    
    // Set default standard times (9 AM to 6 PM, break 1 PM to 2 PM)
    row.querySelector('.time-in').value = '09:00';
    row.querySelector('.time-out').value = '18:00';
    row.querySelector('.time-bin').value = '13:00';
    row.querySelector('.time-bout').value = '14:00';
  });
}

document.getElementById('bulk-attendance-form').onsubmit = function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  
  // Convert FormData to nested object
  const data = { date: '{{ $date }}', attendances: [] };
  
  document.querySelectorAll('.attendance-row').forEach((row, i) => {
    data.attendances.push({
      worker_id: formData.get(`attendances[${i}][worker_id]`),
      status: formData.get(`attendances[${i}][status]`),
      in_time: formData.get(`attendances[${i}][in_time]`),
      out_time: formData.get(`attendances[${i}][out_time]`),
      break_in: formData.get(`attendances[${i}][break_in]`),
      break_out: formData.get(`attendances[${i}][break_out]`)
    });
  });

  Swal.fire({
    title: 'Saving Records',
    text: 'Please wait...',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(window.location.pathname, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(data)
  }).then(r=>r.json()).then(d=>{
    if(d.success) Swal.fire('Saved!', d.message, 'success');
    else Swal.fire('Error', d.message, 'error');
  });
};

function exportDailyToCSV() {
    let csv = [];
    // Header
    csv.push("Worker Name,Department,Status,In Time,Out Time,Break In,Break Out,Total Hours,OT Hours");
    
    document.querySelectorAll('.attendance-row').forEach(row => {
        const name = row.querySelector('td:first-child div:first-child').innerText.trim();
        const dept = row.querySelector('td:first-child div:last-child').innerText.trim();
        let status, inT, outT, bIn, bOut;
        
        const statusEl = row.querySelector('.status-select');
        if (statusEl) {
            status = statusEl.value;
            inT = row.querySelector('.time-in').value || '--:--';
            outT = row.querySelector('.time-out').value || '--:--';
            bIn = row.querySelector('.time-bin').value || '--:--';
            bOut = row.querySelector('.time-bout').value || '--:--';
        } else {
            // Admin view (plain text)
            status = row.querySelector('td:nth-child(2) span').innerText.trim();
            inT = row.querySelector('td:nth-child(3) span').innerText.trim();
            outT = row.querySelector('td:nth-child(4) span').innerText.trim();
            bIn = row.querySelector('td:nth-child(5) span').innerText.trim();
            bOut = row.querySelector('td:nth-child(6) span').innerText.trim();
        }
        
        const line = [name, dept, status, inT, outT, bIn, bOut].map(v => `"${v}"`).join(",");
        csv.push(line);
    });
    
    const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    const downloadLink = document.createElement("a");
    downloadLink.download = "Daily_Attendance_{{ $date }}.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

function filterAttendanceTable(statusFilter) {
  document.querySelectorAll('.attendance-row').forEach(row => {
    let currentStatus;
    const select = row.querySelector('.status-select');
    if (select) {
      currentStatus = select.value;
    } else {
      currentStatus = row.querySelector('td:nth-child(2) span').innerText.trim();
    }
    
    if (statusFilter === 'ALL' || currentStatus === statusFilter) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>
@endsection
