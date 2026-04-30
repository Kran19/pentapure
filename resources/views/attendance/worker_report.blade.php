@extends($layout)

@section('content')
<div style="padding:0.5rem;">
  <div class="flex-between mb-1 no-print" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📅 Monthly Attendance Sheet</h2>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-sm btn-secondary" onclick="window.history.back()">Back</button>
        <button class="btn btn-sm" onclick="exportToExcel()" style="background:#2ecc71;">📊 Export to Excel</button>
    </div>
  </div>

  <div class="card" id="printable-sheet" style="padding:0.5rem; background:white; color:black; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; border:1px solid #ddd;">
    <!-- Official Header -->
    <div style="text-align:center; margin-bottom:1rem;">
      <div style="font-size:0.7rem; font-weight:bold; border:1px solid #000; padding:2px 10px; display:inline-block; margin-bottom:10px;">OFFICIAL RECORD</div>
      <h1 style="margin:0; color:#000; font-weight:900; letter-spacing:1px; font-size:1.5rem;">PENTAPURE FOODS & SPICES PVT.LTD.</h1>
      <div style="font-size:0.75rem; color:#444; margin-bottom:5px;">Factory & Warehouse Operations</div>
      <div style="display:inline-block; border-top:2px solid #000; border-bottom:2px solid #000; padding:3px 15px; font-weight:bold; font-size:1rem; letter-spacing:2px; background:#f9f9f9;">ATTENDANCE SHEET</div>
    </div>

    <!-- Worker Info Grid -->
    <div class="info-grid">
      <div class="info-box">
        <div style="font-size:0.6rem; font-weight:bold; color:#666;">EMPLOYEE NAME</div>
        <div style="font-weight:bold; font-size:0.95rem;">{{ strtoupper($worker->name) }}</div>
      </div>
      <div class="info-box">
        <div style="font-size:0.6rem; font-weight:bold; color:#666;">DEPARTMENT</div>
        <div style="font-weight:bold; font-size:0.95rem;">{{ strtoupper($worker->department->name ?? 'GENERAL') }}</div>
      </div>
      <div class="info-box">
        <div style="font-size:0.6rem; font-weight:bold; color:#666;">MONTH / YEAR</div>
        <div style="font-weight:bold; font-size:0.95rem;">{{ strtoupper(\Carbon\Carbon::parse($month)->format('F Y')) }}</div>
      </div>
      <div class="info-box" style="background:#fff8f8;">
        <div style="font-size:0.6rem; font-weight:bold; color:#666;">DAILY RATE (₹)</div>
        <div style="font-weight:bold; font-size:0.95rem; color:#d00;">{{ number_format($worker->daily_salary, 0) }} / DAY</div>
      </div>
    </div>

    <!-- Detailed Ledger Table -->
    <div class="table-scroll">
      <table>
        <thead>
          <tr style="background:#f0f0f0;">
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">DATE / DAY</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">STATUS</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">CLOCK IN</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">CLOCK OUT</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">BREAK (IN/OUT)</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">WORKING HRS</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">OVERTIME</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">DAILY WAGE (₹)</th>
            <th style="border:1px solid #000; padding:6px; font-size:0.75rem;">REMARK / SIGN</th>
          </tr>
        </thead>
        <tbody>
          @php 
            $startDate = \Carbon\Carbon::parse($month)->startOfMonth();
            $endDate = \Carbon\Carbon::parse($month)->endOfMonth();
            $totalW = 0;
            $totalH = 0;
            $totalOT = 0;
          @endphp
          @for($date = $startDate; $date->lte($endDate); $date->addDay())
            @php 
                $dStr = $date->toDateString();
                $att = $attendances->get($dStr);
                $totalW += $att?->calculated_wage ?? 0;
                $totalH += $att?->total_hours ?? 0;
                $totalOT += $att?->overtime_hours ?? 0;
                $isSunday = $date->isSunday();
            @endphp
            <tr style="border-bottom:1px solid #000; {{ $isSunday ? 'background:#fff8f8;' : '' }}">
              <td style="border:1px solid #000; padding:5px; text-align:center; font-size:0.8rem; font-weight:600;">
                {{ $date->format('d-M') }} <span style="font-weight:normal; font-size:0.7rem;">({{ $date->format('D') }})</span>
              </td>
              <td style="border:1px solid #000; padding:5px; text-align:center; font-weight:bold; font-size:0.75rem;">
                <span style="color: {{ $att?->status === 'PRESENT' ? '#008000' : ($att?->status === 'ABSENT' ? '#d00' : '#888') }};">
                  {{ $att?->status ?? ($isSunday ? 'SUNDAY' : 'ABSENT') }}
                </span>
              </td>
              <td style="border:1px solid #000; padding:5px; text-align:center; font-size:0.85rem;">{{ $att?->in_time ? date('H:i', strtotime($att->in_time)) : '--:--' }}</td>
              <td style="border:1px solid #000; padding:5px; text-align:center; font-size:0.85rem;">{{ $att?->out_time ? date('H:i', strtotime($att->out_time)) : '--:--' }}</td>
              <td style="border:1px solid #000; padding:5px; text-align:center; font-size:0.75rem; color:#666;">
                @if($att?->break_in)
                    {{ date('H:i', strtotime($att->break_in)) }} - {{ date('H:i', strtotime($att->break_out)) }}
                @else
                    --:--
                @endif
              </td>
              <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $att?->total_hours > 0 ? number_format($att->total_hours, 1) : '0.0' }}</td>
              <td style="border:1px solid #000; padding:5px; text-align:center;">{{ $att?->overtime_hours > 0 ? number_format($att->overtime_hours, 1) : '0.0' }}</td>
              <td style="border:1px solid #000; padding:5px; text-align:center; font-weight:900; color: {{ $att?->calculated_wage > 0 ? '#d00' : '#888' }}; font-size:1rem;">
                {{ $att?->calculated_wage > 0 ? number_format($att->calculated_wage, 0) : '0' }}
              </td>
              <td style="border:1px solid #000; padding:5px; width:100px;"></td>
            </tr>
          @endfor
        </tbody>
        <tfoot>
          <tr style="background:#fff2f2; font-weight:bold; border-top:2px solid #000;">
            <td colspan="5" style="border:1px solid #000; padding:10px; text-align:right; letter-spacing:1px; font-size:1rem;">TOTAL MONTHLY EARNINGS:</td>
            <td style="border:1px solid #000; padding:10px; text-align:center; font-size:1.1rem;">{{ number_format($totalH, 1) }}h</td>
            <td style="border:1px solid #000; padding:10px; text-align:center; font-size:1.1rem; color:#555;">{{ number_format($totalOT, 1) }}h</td>
            <td style="border:1px solid #000; padding:10px; text-align:center; font-size:1.5rem; color:#d00; border:2px solid #d00;">₹{{ number_format($totalW, 2) }}</td>
            <td style="border:1px solid #000;"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Official Authorization -->
    <div class="no-print" style="margin-top:2rem; display:grid; grid-template-columns: repeat(3, 1fr); gap:40px; text-align:center;">
      <div style="border-top:1px solid #000; padding-top:10px; font-size:0.8rem; font-weight:bold;">FACTORY MANAGER<br><span style="font-weight:normal; font-size:0.7rem;">Verified & Checked</span></div>
      <div style="border-top:1px solid #000; padding-top:10px; font-size:0.8rem; font-weight:bold;">FINANCE / ACCOUNTS<br><span style="font-weight:normal; font-size:0.7rem;">Audit Approved</span></div>
      <div style="border-top:1px solid #000; padding-top:10px; font-size:0.8rem; font-weight:bold;">AUTHORIZED SIGNATORY<br><span style="font-weight:normal; font-size:0.7rem;">Pentapure Foods & Spices</span></div>
    </div>
    
    <!-- Fine Print -->
    <div class="no-print" style="margin-top:2rem; font-size:0.6rem; color:#888; text-align:center; border-top:1px dashed #ddd; padding-top:10px;">
      This is a computer-generated official attendance record. Any alterations made manually without authorization will render this document invalid.
    </div>
  </div>
</div>

<script>
function exportToExcel() {
    let table = document.querySelector("#printable-sheet table");
    let rows = Array.from(table.rows);
    let csv = rows.map(row => {
        let cells = Array.from(row.cells);
        return cells.map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(",");
    }).join("\n");
    
    let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", "{{ $worker->name }}_Attendance_{{ $month }}.csv");
    link.click();
}
</script>

<style>
/* Force white background for the entire page on screen when viewing report */
body {
    background: #121212 !important; /* Keep app dark but report paper white */
}

#printable-sheet {
    background: white !important;
    color: black !important;
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0.5rem !important;
    box-shadow: none;
    border-radius: 0;
    overflow-x: hidden;
}

/* Fix the info grid for mobile */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 2px solid #000;
    margin-bottom: 0.5rem;
}

.info-box {
    border: 1px solid #000;
    padding: 6px;
}

/* Horizontal scroll for the table on mobile */
.table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 2px solid #000;
}

#printable-sheet table {
    width: 1000px; 
    border-collapse: collapse;
}

#printable-sheet th, #printable-sheet td {
    border: 1px solid #000 !important;
    padding: 6px 3px !important;
    text-align: center;
    font-size: 0.8rem !important;
    color: #000 !important;
}

#printable-sheet th {
    background: #eee !important;
    font-weight: bold;
}

@media print {
  body, html {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  nav, aside, footer, header, .admin-sidebar, .admin-mobile-header, .app-nav {
    display: none !important;
  }
  
  .no-print { display: none !important; }
  
  #printable-sheet {
    position: absolute;
    left: 0;
    top: 0;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    border: none !important;
  }
  
  .table-scroll {
    overflow: visible !important;
    border: none !important;
  }
  
  #printable-sheet table {
    width: 100% !important;
    table-layout: fixed;
  }
  
  #printable-sheet th, #printable-sheet td {
    font-size: 8.5px !important;
    padding: 2px !important;
  }

  @page {
    size: A4;
    margin: 5mm;
  }
}
</style>
@endsection
