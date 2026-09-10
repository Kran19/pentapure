@extends($layout)

@php
    $allSheetsPdfUrl = request()->segment(1) === 'attendance'
        ? url('attendance/history/all-sheets/pdf?month=' . $month)
        : url(request()->segment(1) . '/attendance/reports/all-sheets/pdf?month=' . $month);
@endphp

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📑 Monthly Attendance & Salary Report</h2>
    
    <div style="display:flex; gap:10px; align-items:center;">
      <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; align-items:center;">
        <label style="font-weight:bold;">Month:</label>
        <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc;">
      </form>
      <button class="btn btn-sm" onclick="exportToExcel()" style="width:auto; padding:0.4rem 1rem; background:#27ae60; color:white;">📗 Export to Excel</button>
      <a class="btn btn-sm" href="{{ $allSheetsPdfUrl }}" target="_blank" style="width:auto; padding:0.4rem 1rem; background:#3498db; color:white; border:none; text-decoration:none; display:inline-flex; align-items:center; font-weight:600;">📄 Download All Sheets</a>
      <button class="btn btn-sm" onclick="window.print()" style="width:auto; padding:0.4rem 1rem; background:var(--secondary); color:white; border:none; cursor:pointer;">Print Summary</button>
    </div>
  </div>

  <div class="card" id="printable-report" style="padding:1.5rem;">
    <div style="text-align:center; margin-bottom:2rem;">
      <h3 style="margin:0;">PENTAPURE FACTORY</h3>
      <div style="color:var(--text-muted);">Attendance & Payroll Report - {{ \Carbon\Carbon::parse($month)->format('F Y') }}</div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th style="width:45px;">#</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Salary</th>
            <th class="no-print">Action</th>
            <th>Total Present</th>
            <th>Total OT Hrs</th>
            <th>Total Payable (₹)</th>
            <th style="min-width:220px;">Mark as Paid</th>
          </tr>
        </thead>
        <tbody>
          @php 
            $grandTotal = 0; 
            $isAttendanceApp = request()->segment(1) === 'attendance';
            $grouped = [];
            foreach($reportData as $d) {
                $dept = $d['worker']->department->name ?? 'Other';
                $grouped[$dept][] = $d;
            }
          @endphp
          
          @foreach($grouped as $deptName => $workers)
            @php
              $deptTotalPresent = array_sum(array_column($workers, 'present'));
              $deptTotalOt = array_sum(array_column($workers, 'total_ot'));
              $deptTotalWage = array_sum(array_column($workers, 'total_wage'));
              $deptWorkerCount = count($workers);
            @endphp
            <tr style="background:rgba(255,255,255,0.07); border-top:2px solid var(--border-soft, #e5e7eb);">
              <td colspan="5" style="font-weight:bold; color:var(--secondary); font-size:0.92rem;">
                📂 {{ strtoupper($deptName) }} <span style="font-size:0.8rem; font-weight:600; opacity:0.85; margin-left:0.4rem; color:var(--text-color);">(Total Workers: {{ $deptWorkerCount }})</span>
              </td>
              <td style="font-weight:bold; color:var(--secondary); font-size:0.95rem;">{{ $deptTotalPresent > 0 ? (floor($deptTotalPresent) == $deptTotalPresent ? number_format($deptTotalPresent, 0) : number_format($deptTotalPresent, 1)) : 0 }}</td>
              <td style="font-weight:bold; font-size:0.9rem;">{{ number_format($deptTotalOt, 1) }}</td>
              <td style="font-weight:bold; color:var(--primary-light); font-size:1.05rem;">₹{{ number_format($deptTotalWage, 2) }}</td>
              <td></td>
            </tr>
            @foreach($workers as $data)
              @php 
                $grandTotal += $data['total_wage']; 
                $adj = $data['adjustment'] ?? null;
                $isPaid = (bool)($adj?->is_paid ?? false);
                $rawDate = $adj?->paid_at ? \Carbon\Carbon::parse($adj->paid_at)->format('Y-m-d') : ($adj?->paid_note && preg_match('/^\d{4}-\d{2}-\d{2}$/', $adj->paid_note) ? $adj->paid_note : '');
                $paidDate = $isPaid ? ($rawDate ?: now()->format('Y-m-d')) : $rawDate;
              @endphp
              <tr class="report-row" id="row-worker-{{ $data['worker']->id }}">
                <td style="font-weight:bold; color:var(--text-muted);">{{ $data['worker_number'] ?? '-' }}</td>
                <td style="font-weight:600;">{{ $data['worker']->name }}</td>
                <td>{{ $data['worker']->department->name }}</td>
                <td>
                    <div style="font-weight:bold;">₹{{ number_format($data['worker']->salary_amount, 0) }}</div>
                    <div style="font-size:0.65rem; opacity:0.7;">{{ $data['worker']->salary_type }}</div>
                </td>
                <td class="no-print">
                  @php
                    $reportUrl = $isAttendanceApp 
                        ? url('attendance/history/worker/' . $data['worker']->id)
                        : url(request()->segment(1) . '/attendance/reports/worker/' . $data['worker']->id);
                  @endphp
                  <a href="{{ $reportUrl }}?month={{ $month }}" class="btn btn-sm" style="width:auto; padding:0.2rem 0.6rem; font-size:0.7rem; text-transform:uppercase;">View Sheet</a>
                </td>
                <td style="color:var(--secondary); font-weight:bold;">{{ $data['present'] }}</td>
                <td style="font-weight:bold;">{{ number_format($data['total_ot'], 1) }}</td>
                <td style="font-weight:bold; color:var(--primary-light); font-size:1.1rem;">₹{{ number_format($data['total_wage'], 2) }}</td>
                <td>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" 
                            onclick="togglePaidStatus({{ $data['worker']->id }}, true)"
                            class="btn-paid-check"
                            style="border:none; background:{{ $isPaid ? '#22c55e' : '#e2e8f0' }}; color:{{ $isPaid ? '#fff' : '#64748b' }}; border-radius:50%; width:28px; height:28px; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px;"
                            title="Mark as Paid">
                      ✓
                    </button>
                    <button type="button" 
                            onclick="togglePaidStatus({{ $data['worker']->id }}, false)"
                            class="btn-paid-cross"
                            style="border:none; background:{{ !$isPaid ? '#ef4444' : '#e2e8f0' }}; color:{{ !$isPaid ? '#fff' : '#64748b' }}; border-radius:50%; width:28px; height:28px; font-weight:bold; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px;"
                            title="Mark as Unpaid">
                      ✕
                    </button>
                    <input type="date" 
                           id="paid-date-{{ $data['worker']->id }}" 
                           value="{{ $paidDate }}" 
                           onchange="updatePaidDate({{ $data['worker']->id }})"
                           style="padding:3px 6px; border:1px solid #ccc; border-radius:4px; font-size:0.75rem; width:125px; background:white; color:black; outline:none;"
                           title="Select Payment Date">
                  </div>
                  <div id="paid-badge-{{ $data['worker']->id }}" style="margin-top:3px; font-size:0.68rem; font-weight:bold; color:{{ $isPaid ? '#22c55e' : '#ef4444' }};">
                    {{ $isPaid ? '✓ PAID' . ($paidDate ? ' (' . \Carbon\Carbon::parse($paidDate)->format('d M Y') . ')' : '') : '✕ UNPAID' }}
                  </div>
                </td>
              </tr>
            @endforeach
          @endforeach
          
          @if(empty($reportData))
            <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">No attendance records found for this month.</td></tr>
          @else
            <tr style="background:var(--glass-bg); font-weight:bold;">
              <td colspan="4" style="text-align:right;">Grand Total Payroll Liability:</td>
              <td class="no-print"></td>
              <td colspan="2"></td>
              <td style="color:var(--secondary); font-size:1.2rem;">₹{{ number_format($grandTotal, 2) }}</td>
              <td></td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function getTodayString() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function togglePaidStatus(workerId, isPaid) {
    const dateInput = document.getElementById('paid-date-' + workerId);
    if (isPaid && dateInput && !dateInput.value) {
        dateInput.value = getTodayString();
    }
    const paidDate = dateInput ? dateInput.value : '';
    sendPaidStatusUpdate(workerId, isPaid, paidDate);
}

function updatePaidDate(workerId) {
    const badge = document.getElementById('paid-badge-' + workerId);
    const isPaid = badge ? badge.innerText.includes('PAID') : false;
    const dateInput = document.getElementById('paid-date-' + workerId);
    const paidDate = dateInput ? dateInput.value : '';
    sendPaidStatusUpdate(workerId, isPaid, paidDate);
}

function sendPaidStatusUpdate(workerId, isPaid, paidDate) {
    const postUrl = "{{ str_contains(request()->path(), 'admin') ? url(str_replace('reports', 'reports', request()->path()) . '/worker/') : url(request()->path() . '/worker/') }}/" + workerId + "/toggle-paid";
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('month', '{{ $month }}');
    formData.append('is_paid', isPaid ? 1 : 0);
    formData.append('paid_date', paidDate || '');
    formData.append('paid_note', paidDate || '');

    fetch(postUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            const row = document.getElementById('row-worker-' + workerId);
            if (row) {
                const btnCheck = row.querySelector('.btn-paid-check');
                const btnCross = row.querySelector('.btn-paid-cross');
                const badge = document.getElementById('paid-badge-' + workerId);
                const dateInput = document.getElementById('paid-date-' + workerId);

                if (data.paid_date && dateInput) {
                    dateInput.value = data.paid_date;
                }

                if (data.is_paid) {
                    btnCheck.style.background = '#22c55e'; btnCheck.style.color = '#fff';
                    btnCross.style.background = '#e2e8f0'; btnCross.style.color = '#64748b';
                    if (badge) { 
                        const formatted = data.paid_date ? formatDisplayDate(data.paid_date) : '';
                        badge.innerText = '✓ PAID' + (formatted ? ' (' + formatted + ')' : ''); 
                        badge.style.color = '#22c55e'; 
                    }
                } else {
                    btnCheck.style.background = '#e2e8f0'; btnCheck.style.color = '#64748b';
                    btnCross.style.background = '#ef4444'; btnCross.style.color = '#fff';
                    if (badge) { badge.innerText = '✕ UNPAID'; badge.style.color = '#ef4444'; }
                }
            }
        }
    }).catch(err => console.error('Error updating paid status:', err));
}

function exportToExcel() {
    let csv = [];
    const table = document.querySelector("table");
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [];
        let cols = rows[i].querySelectorAll("td, th");
        
        if (rows[i].classList.contains('no-print')) continue;

        for (let j = 0; j < cols.length; j++) {
            if (cols[j].classList.contains('no-print')) continue;
            
            let data = cols[j].innerText.trim()
                .replace(/\n/g, " ")
                .replace(/\s\s+/g, " ");
            
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        
        if (row.length > 0) {
            csv.push(row.join(","));
        }
    }
    
    const csvFile = new Blob([csv.join("\n")], {type: "text/csv;charset=utf-8;"});
    const downloadLink = document.createElement("a");
    const fileName = "Monthly_Payroll_Report_{{ $month }}.csv";
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(csvFile, fileName);
    } else {
        downloadLink.download = fileName;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
}

function downloadAllIndividualSheets() {
    window.open("{{ $allSheetsPdfUrl }}", '_blank');
}
</script>

<style>
@media print {
  body * { visibility: hidden; }
  #printable-report, #printable-report * { visibility: visible; }
  #printable-report { position: absolute; left: 0; top: 0; width: 100%; box-shadow:none; }
  .admin-sidebar { display: none; }
  .admin-mobile-header { display: none; }
}
</style>
@endsection
