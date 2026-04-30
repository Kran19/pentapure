@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <h2 style="margin-bottom:1.5rem;">📊 Attendance Dashboard</h2>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
    <!-- Total Workers -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--primary-light);">{{ $totalWorkers }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Total Employees</div>
    </div>

    <!-- Present Today -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">{{ $presentToday }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Present Today</div>
    </div>

    <!-- Absent Today -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--danger);">{{ $absentToday }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Absent Today</div>
    </div>

    <!-- Total OT -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--info);">{{ number_format($totalOT, 1) }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Total OT Hours Today</div>
    </div>
  </div>

  <div style="margin-top:2rem;">
    <p style="color:var(--text-muted);">Use the sidebar menu to manage workers, track daily attendance, and generate payroll reports.</p>
  </div>
</div>
@endsection
