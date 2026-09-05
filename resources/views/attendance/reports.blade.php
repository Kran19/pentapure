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
            <th>Employee Name</th>
            <th>Department</th>
            <th>Salary</th>
            <th class="no-print">Action</th>
            <th>Present</th>
            <th>Half Days</th>
            <th>Absent</th>
            <th>Total OT Hrs</th>
            <th>Total Payable (₹)</th>
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
            <tr style="background:rgba(255,255,255,0.05);">
                <td colspan="9" style="font-weight:bold; color:var(--secondary);">📂 {{ strtoupper($deptName) }}</td>
            </tr>
            @foreach($workers as $data)
              @php $grandTotal += $data['total_wage']; @endphp
              <tr class="report-row">
                <td style="padding-left:1.5rem; font-weight:600;">{{ $data['worker']->name }}</td>
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
                <td style="color:var(--info);">{{ $data['half'] }}</td>
                <td style="color:var(--danger);">{{ $data['absent'] }}</td>
                <td style="font-weight:bold;">{{ number_format($data['total_ot'], 1) }}</td>
                <td style="font-weight:bold; color:var(--primary-light); font-size:1.1rem;">₹{{ number_format($data['total_wage'], 2) }}</td>
              </tr>
            @endforeach
          @endforeach
          
          @if(empty($reportData))
            <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">No attendance records found for this month.</td></tr>
          @else
            <tr style="background:var(--glass-bg); font-weight:bold;">
              <td colspan="3" style="text-align:right;">Grand Total Payroll Liability:</td>
              <td class="no-print"></td>
              <td colspan="4"></td>
              <td style="color:var(--secondary); font-size:1.2rem;">₹{{ number_format($grandTotal, 2) }}</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
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
