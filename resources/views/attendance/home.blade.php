@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <h2 style="margin-bottom:1.5rem;">📊 Attendance Dashboard</h2>

@php
  $prefix = request()->segment(1) == 'admin' ? 'admin' : 'attendance';
  $workersUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.workers') : route(request()->segment(1) . '.workers');
  $dailyUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.daily') : route(request()->segment(1) . '.daily');
  $reportsUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.reports') : route(request()->segment(1) . '.history');
@endphp

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
    <!-- Total Workers -->
    <a href="{{ $workersUrl }}" class="card clickable-card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--primary-light);">{{ $totalWorkers }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Total Employees</div>
    </a>

    <!-- Present Today -->
    <a href="{{ $dailyUrl }}" class="card clickable-card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--secondary);">{{ $presentToday }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Present Today</div>
    </a>

    <!-- Absent Today -->
    <a href="{{ $dailyUrl }}" class="card clickable-card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--danger);">{{ $absentToday }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Absent Today</div>
    </a>

    <!-- Total OT -->
    <a href="{{ $reportsUrl }}" class="card clickable-card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2rem; font-weight:bold; color:var(--info);">{{ number_format($totalOT, 1) }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem;">Total OT Hours Today</div>
    </a>
  </div>

  <div style="margin-top:2rem;">
    <p style="color:var(--text-muted);">Use the sidebar menu to manage workers, track daily attendance, and generate payroll reports.</p>
  </div>
</div>
@endsection
