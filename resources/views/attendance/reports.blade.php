@extends($layout)

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
      <button class="btn btn-sm" onclick="window.print()" style="width:auto; padding:0.4rem 1rem; background:var(--secondary);">🖨️ Print / Save PDF</button>
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
            <th>Present</th>
            <th>Half Days</th>
            <th>Absent</th>
            <th>Total OT Hrs</th>
            <th>Total Payable (₹)</th>
            <th class="no-print">Action</th>
          </tr>
        </thead>
        <tbody>
          @php 
            $grandTotal = 0; 
            $prefix = request()->is('admin/*') ? '/admin/attendance' : '/attendance';
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
                <td style="color:var(--secondary); font-weight:bold;">{{ $data['present'] }}</td>
                <td style="color:var(--info);">{{ $data['half'] }}</td>
                <td style="color:var(--danger);">{{ $data['absent'] }}</td>
                <td style="font-weight:bold;">{{ number_format($data['total_ot'], 1) }}</td>
                <td style="font-weight:bold; color:var(--primary-light); font-size:1.1rem;">₹{{ number_format($data['total_wage'], 2) }}</td>
                <td class="no-print">
                  <a href="{{ $prefix }}/reports/worker/{{ $data['worker']->id }}?month={{ $month }}" class="btn btn-sm" style="width:auto; padding:0.2rem 0.6rem; font-size:0.7rem;">View Sheet</a>
                </td>
              </tr>
            @endforeach
          @endforeach
          
          @if(empty($reportData))
            <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">No attendance records found for this month.</td></tr>
          @else
            <tr style="background:var(--glass-bg); font-weight:bold;">
              <td colspan="7" style="text-align:right;">Grand Total Payroll Liability:</td>
              <td style="color:var(--secondary); font-size:1.2rem;">₹{{ number_format($grandTotal, 2) }}</td>
              <td class="no-print"></td>
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
    const rows = document.querySelectorAll("table tr");
    for (let i = 0; i < rows.length; i++) {
        if(rows[i].classList.contains('no-print')) continue;
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            if(cols[j].classList.contains('no-print')) continue;
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s+)/gm, ' ');
            data = data.replace(/"/g, '""');
            row.push('"' + data + '"');
        }
        csv.push(row.join(","));
    }
    const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    const downloadLink = document.createElement("a");
    downloadLink.download = "Monthly_Payroll_Report_{{ $month }}.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
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
