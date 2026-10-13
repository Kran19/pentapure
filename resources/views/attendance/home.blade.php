@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  @if(session('success'))
    <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:0.8rem 1.2rem; border-radius:8px; margin-bottom:1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
      <span>✓ {{ session('success') }}</span>
      <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#166534; font-size:1.1rem; cursor:pointer;">&times;</button>
    </div>
  @endif

  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📊 Attendance Dashboard</h2>
    @if(empty($isReadOnly) && !empty($canEditWorkers))
    <button class="btn" onclick="openAddWorkerModal()" style="width:auto; padding:0.6rem 1.5rem;">+ Add Worker</button>
    @endif
  </div>

@php
  $seg = (request()->segment(1) === 'public' ? request()->segment(2) : request()->segment(1));
  $userRole = strtoupper(session('auth_user.role') ?? '');
  $isAdmin = in_array($userRole, ['ADMIN', 'SUB_ADMIN']) || str_starts_with((string)$seg, 'admin') || str_starts_with((string)$seg, 'sub_admin');
  $roleSlug = $seg ?: ($isAdmin ? 'admin' : 'attendance');
  $prefix = $isAdmin ? 'admin' : 'attendance';
  $workersUrl = $isAdmin 
      ? (Route::has($roleSlug . '.attendance.workers') ? route($roleSlug . '.attendance.workers') : url($roleSlug . '/attendance/workers')) 
      : (Route::has($roleSlug . '.workers') ? route($roleSlug . '.workers') : url($roleSlug . '/workers'));
  $dailyUrl = $isAdmin 
      ? (Route::has($roleSlug . '.attendance.daily') ? route($roleSlug . '.attendance.daily') : url($roleSlug . '/attendance/daily')) 
      : (Route::has($roleSlug . '.daily') ? route($roleSlug . '.daily') : url($roleSlug . '/daily'));
  $reportsUrl = $isAdmin 
      ? (Route::has($roleSlug . '.attendance.reports') ? route($roleSlug . '.attendance.reports') : url($roleSlug . '/attendance/reports')) 
      : (Route::has($roleSlug . '.history') ? route($roleSlug . '.history') : url($roleSlug . '/history'));

  $getDailyPdfUrl = function($targetDate) use ($isAdmin, $roleSlug) {
      if ($isAdmin) {
          $routeName = $roleSlug . '.attendance.daily.pdf';
          return Route::has($routeName) 
              ? route($routeName, ['date' => $targetDate]) 
              : url($roleSlug . '/attendance/daily/pdf?date=' . $targetDate);
      } else {
          $routeName = $roleSlug . '.daily.pdf';
          return Route::has($routeName) 
              ? route($routeName, ['date' => $targetDate]) 
              : url($roleSlug . '/daily/pdf?date=' . $targetDate);
      }
  };

  $rawPerms = session('auth_user.permissions') ?? [];
  if (is_string($rawPerms)) {
      $rawPerms = json_decode($rawPerms, true) ?? [];
  }
  if (!is_array($rawPerms)) {
      $rawPerms = [];
  }
  $normPerms = array_map(fn($p) => strtolower(trim((string)$p)), $rawPerms);

  $hasGranular = false;
  foreach ($normPerms as $p) {
      if (is_string($p) && (str_starts_with($p, 'view_') || str_starts_with($p, 'edit_'))) {
          $hasGranular = true;
          break;
      }
  }

  // Dashboard Edit access: If Dashboard is View-Only ($isReadOnly == true), dashboard MUST NEVER show edit actions
  $dashboardCanEdit = empty($isReadOnly) && (
      in_array($userRole, ['ADMIN', 'SUPER_ADMIN']) ||
      in_array('can_manage', $normPerms, true) ||
      in_array('edit_attendance_dashboard', $normPerms, true) ||
      (!$hasGranular && in_array('attendance_dashboard', $normPerms, true)) ||
      ($userRole === 'ATTENDANCE' && !$hasGranular)
  );

  // Daily attendance actions strictly depend on canEditDaily
  $userHasEditPermission = !empty($canEditDaily);
@endphp

  <!-- Summary Cards -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1.5rem;">
    <!-- Present Today -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2.5rem; font-weight:bold; color:var(--secondary);">{{ $presentToday }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem; font-weight:600;">Present Today</div>
    </div>

    <!-- Overtime (Hrs) -->
    <div class="card" style="padding:1.5rem; text-align:center;">
      <div style="font-size:2.5rem; font-weight:bold; color:var(--info);">{{ number_format($totalOT, 1) }}</div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem; font-weight:600;">Overtime (Hrs)</div>
    </div>

    <!-- Pending Attendance Card -->
    <div class="card" style="padding:1.5rem; text-align:center; cursor:pointer; border-top:4px solid {{ $pendingSubmissions->count() > 0 ? '#f59e0b' : '#10b981' }}; transition:transform 0.2s, box-shadow 0.2s;" onclick="switchAttendanceTab('pending'); document.getElementById('attendanceRecordsSection')?.scrollIntoView({behavior:'smooth'});" title="Click to view all pending attendance dates">
      <div style="font-size:2.5rem; font-weight:bold; color:{{ $pendingSubmissions->count() > 0 ? '#d97706' : '#059669' }};">
        {{ $pendingSubmissions->count() }}
      </div>
      <div style="color:var(--text-muted); font-size:0.9rem; margin-top:0.5rem; font-weight:600; display:flex; align-items:center; justify-content:center; gap:5px;">
        <span>⏳ Pending Attendance</span>
        @if($pendingSubmissions->count() > 0)
          <span style="background:#fef3c7; color:#b45309; font-size:0.7rem; padding:1px 6px; border-radius:10px; font-weight:700;">Action Required</span>
        @else
          <span style="background:#dcfce7; color:#15803d; font-size:0.7rem; padding:1px 6px; border-radius:10px; font-weight:700;">Clear ✓</span>
        @endif
      </div>
    </div>
  </div>

  <!-- Quick Actions (Only show pages the user has access to) -->
  @php
    $canViewDaily = in_array($userRole, ['ADMIN', 'SUPER_ADMIN']) || in_array('can_manage', $normPerms, true) || in_array('view_attendance_daily', $normPerms, true) || in_array('edit_attendance_daily', $normPerms, true) || (!$hasGranular && in_array('attendance_daily', $normPerms, true)) || ($userRole === 'ATTENDANCE' && !$hasGranular);
    $canViewReports = in_array($userRole, ['ADMIN', 'SUPER_ADMIN']) || in_array('can_manage', $normPerms, true) || in_array('view_attendance_reports', $normPerms, true) || in_array('edit_attendance_reports', $normPerms, true) || (!$hasGranular && in_array('attendance_reports', $normPerms, true)) || ($userRole === 'ATTENDANCE' && !$hasGranular);
    $canViewWorkers = in_array($userRole, ['ADMIN', 'SUPER_ADMIN']) || in_array('can_manage', $normPerms, true) || in_array('view_attendance_workers', $normPerms, true) || in_array('edit_attendance_workers', $normPerms, true) || (!$hasGranular && in_array('attendance_workers', $normPerms, true)) || ($userRole === 'ATTENDANCE' && !$hasGranular);
  @endphp
  @if($canViewDaily || $canViewReports || $canViewWorkers)
  <div class="card" style="margin-top:1.5rem; padding:1.5rem;">
    <h3 style="margin-top:0; margin-bottom:1rem;">⚡ Quick Actions</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1rem;">
      @if($canViewDaily)
      <a href="{{ $dailyUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">📝 Daily Punch Sheet</a>
      @endif
      @if($canViewReports)
      <a href="{{ $reportsUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">📊 Monthly Reports</a>
      @endif
      @if($canViewWorkers)
      <a href="{{ $workersUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">👥 Manage Workers</a>
      @endif
    </div>
  </div>
  @endif

  <!-- Attendance Records & Status with Tabs -->
  <div class="card" id="attendanceRecordsSection" style="margin-top:1.5rem; padding:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:12px;">
      <div>
        <h3 style="margin:0; font-size:1.25rem; display:flex; align-items:center; gap:8px;">
          <span>📅</span> Attendance Records & Status
        </h3>
        <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.85rem;">
          Track daily punch submissions, pending dates, and completed registers
        </p>
      </div>

      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <!-- Month Filter -->
        <form method="GET" action="{{ url()->current() }}" id="monthFilterForm" style="display:inline-flex; align-items:center; gap:6px;">
          <input type="hidden" name="tab" id="activeTabInput" value="{{ request('tab', 'all') }}">
          <label style="font-weight:600; font-size:0.85rem; color:#475569;">Month:</label>
          <input type="month" name="month" value="{{ request('month', $selectedMonth ?? date('Y-m')) }}" onchange="this.form.submit()" onclick="if(typeof this.showPicker === 'function'){ try{ this.showPicker(); }catch(e){} }" style="padding:0.4rem 0.65rem; border-radius:8px; border:1px solid #cbd5e1; font-weight:600; font-size:0.85rem; cursor:pointer; background:#fff; color:#0f172a;">
          @if(request()->filled('month'))
            <a href="{{ url()->current() }}#all" style="font-size:0.8rem; color:#ef4444; text-decoration:none; font-weight:600; padding:0.2rem 0.5rem; background:#fee2e2; border-radius:6px;" title="Clear month filter to view all records">✕ Clear</a>
          @endif
        </form>

        <!-- Navigation Tabs -->
        <div class="attendance-tabs" style="display:inline-flex; background:#f1f5f9; padding:4px; border-radius:10px; border:1px solid #e2e8f0; gap:4px;">
          <button type="button" id="tab-btn-pending" class="att-tab-btn" onclick="switchAttendanceTab('pending')" style="display:flex; align-items:center; gap:6px; padding:0.45rem 0.9rem; border-radius:8px; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; background:transparent; color:#64748b; transition:all 0.2s;">
            <span>⏳ Pending Attendance</span>
            <span style="background:{{ $pendingSubmissions->count() > 0 ? '#ef4444' : '#10b981' }}; color:white; font-size:0.75rem; padding:1px 7px; border-radius:12px; font-weight:700;">{{ $pendingSubmissions->count() }}</span>
          </button>

          <button type="button" id="tab-btn-all" class="att-tab-btn" onclick="switchAttendanceTab('all')" style="display:flex; align-items:center; gap:6px; padding:0.45rem 0.9rem; border-radius:8px; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; background:#ffffff; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1); transition:all 0.2s;">
            <span>📋 All Records</span>
            <span style="background:#e2e8f0; color:#334155; font-size:0.75rem; padding:1px 7px; border-radius:12px; font-weight:700;">{{ $recentSubmissions->count() }}</span>
          </button>

          <button type="button" id="tab-btn-submitted" class="att-tab-btn" onclick="switchAttendanceTab('submitted')" style="display:flex; align-items:center; gap:6px; padding:0.45rem 0.9rem; border-radius:8px; border:none; font-weight:600; font-size:0.85rem; cursor:pointer; background:transparent; color:#64748b; transition:all 0.2s;">
            <span>🔒 Submitted</span>
            <span style="background:#e2e8f0; color:#334155; font-size:0.75rem; padding:1px 7px; border-radius:12px; font-weight:700;">{{ $submittedSubmissions->count() }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 1: PENDING ATTENDANCE -->
    <div id="tab-content-pending" class="att-tab-pane" style="display:none;">
      @if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
      <div style="background:#fffbeb; border:1px solid #fef3c7; color:#92400e; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.88rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
        <span>⚠️ <strong>{{ $pendingSubmissions->count() }} pending attendance date(s)</strong> for {{ \Carbon\Carbon::parse(($selectedMonth ?? date('Y-m')) . '-01')->format('F Y') }} require entry or final submission.</span>
        @if($userHasEditPermission)
          <span style="font-size:0.8rem; color:#b45309;">Click "Open & Fill" to complete attendance</span>
        @else
          <span style="font-size:0.8rem; color:#b45309;">Click "View" to inspect attendance sheet</span>
        @endif
      </div>

      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr style="border-bottom:2px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Date</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Day</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Status</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Attendance Progress</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Initiated / Done By</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendingSubmissions as $sub)
            @php
              $dateObj = \Carbon\Carbon::parse($sub->attendance_date);
              $dateStr = $dateObj->format('Y-m-d');
              $counts = $pendingAttendanceCounts->get($dateStr);
              $totalEntered = $counts->total ?? 0;
              $presentCount = $counts->present_count ?? 0;
              $isToday = $dateObj->isToday();
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9; {{ $isToday ? 'background:#fefce8;' : '' }}">
              <td style="padding:0.75rem; font-weight:bold; color:#0f172a;">
                {{ $dateObj->format('d-m-Y') }}
                @if($isToday)
                  <span style="background:#fef08a; color:#854d0e; font-size:0.7rem; padding:2px 6px; border-radius:4px; margin-left:4px; font-weight:700;">TODAY</span>
                @endif
              </td>
              <td style="padding:0.75rem; color:#475569; font-weight:500;">
                {{ $dateObj->format('l') }}
              </td>
              <td style="padding:0.75rem;">
                @if($sub->status === 'PARTIAL_SAVED')
                  <span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:4px 9px; border-radius:6px; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                    ⏳ PARTIAL SAVED
                  </span>
                @else
                  <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:4px 9px; border-radius:6px; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                    🕒 PENDING
                  </span>
                @endif
              </td>
              <td style="padding:0.75rem;">
                @if($totalEntered > 0)
                  <div style="font-weight:600; color:#1e293b; font-size:0.88rem;">
                    {{ $totalEntered }} / {{ $totalWorkers }} Workers Marked
                  </div>
                  <div style="font-size:0.75rem; color:#059669; font-weight:600;">
                    ✓ {{ $presentCount }} Present
                  </div>
                @else
                  <span style="color:#d97706; font-size:0.82rem; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                    ⚠️ Attendance Not Entered
                  </span>
                @endif
              </td>
              <td style="padding:0.75rem; color:#475569; font-size:0.85rem;">
                @if($sub->createdBy)
                  <span style="font-weight:600; color:#1e293b;">{{ $sub->createdBy->name }}</span>
                  @if($sub->updated_at)
                    <br><small style="color:#94a3b8;">{{ $sub->updated_at->format('d-m-Y, H:i') }}</small>
                  @endif
                @else
                  <span style="color:#94a3b8;">—</span>
                @endif
              </td>
              <td style="padding:0.75rem; text-align:center;">
                <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                  @if($userHasEditPermission)
                    <a href="{{ $dailyUrl }}?date={{ $dateStr }}" class="btn" style="padding:0.35rem 0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-size:0.85rem; background:#0284c7; color:white; border-radius:6px; font-weight:600;">
                      📝 Open & Fill
                    </a>
                  @else
                    <a href="{{ $dailyUrl }}?date={{ $dateStr }}" class="btn btn-secondary" style="padding:0.35rem 0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; font-size:0.85rem; border-radius:6px;">
                      View
                    </a>
                  @endif
                  @if($sub->status === 'PARTIAL_SAVED' && $totalEntered > 0)
                    @php
                        $dailyPdfUrl = $getDailyPdfUrl($dateStr);
                    @endphp
                    <a href="{{ $dailyPdfUrl }}" class="btn" style="padding:0.35rem 0.65rem; text-decoration:none; display:inline-block; background:#c0392b; color:white; font-size:0.85rem; border-radius:6px;" target="_blank" title="Download Draft PDF">
                      PDF ↓
                    </a>
                  @endif
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div style="text-align:center; padding:3rem 1rem; background:#f8fafc; border-radius:8px; border:1px dashed #cbd5e1;">
        <div style="font-size:2.5rem; margin-bottom:0.5rem;">🎉</div>
        <h4 style="margin:0 0 0.25rem; color:#0f172a;">All Attendance Up to Date!</h4>
        <p style="margin:0; color:#64748b; font-size:0.88rem;">There are no pending attendance dates requiring submission.</p>
        <a href="{{ $dailyUrl }}" class="btn" style="margin-top:1rem; display:inline-block; padding:0.5rem 1.2rem; font-size:0.88rem; width:auto;">
          {{ $userHasEditPermission ? "📝 Open Today's Attendance Sheet" : "👁️ View Today's Attendance Sheet" }}
        </a>
      </div>
      @endif
    </div>

    <!-- TAB 2: ALL RECORDS -->
    <div id="tab-content-all" class="att-tab-pane">
      @if(isset($recentSubmissions) && $recentSubmissions->count() > 0)
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr style="border-bottom:2px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Date</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Day</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Status</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Done By</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Submitted By</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentSubmissions as $sub)
            @php
              $subDate = \Carbon\Carbon::parse($sub->attendance_date);
              $subDateStr = $subDate->format('Y-m-d');
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:0.75rem; font-weight:bold; color:#0f172a;">{{ $subDate->format('d-m-Y') }}</td>
              <td style="padding:0.75rem; color:#475569;">{{ $subDate->format('l') }}</td>
              <td style="padding:0.75rem;">
                @if($sub->status === 'SUBMITTED')
                  <span style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">🔒 SUBMITTED</span>
                @elseif($sub->status === 'PARTIAL_SAVED')
                  <span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">⏳ PARTIAL SAVED</span>
                @else
                  <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">🕒 PENDING</span>
                @endif
              </td>
              <td style="padding:0.75rem; color:#475569;">{{ $sub->createdBy->name ?? '—' }}</td>
              <td style="padding:0.75rem; color:#475569;">
                @if($sub->submittedBy)
                  <span style="font-weight:600; color:#1e293b;">{{ $sub->submittedBy->name }}</span>
                  <br><small style="color:#7f8c8d;">{{ $sub->submitted_at ? \Carbon\Carbon::parse($sub->submitted_at)->format('d-m-Y, H:i') : '' }}</small>
                @else
                  —
                @endif
              </td>
              <td style="padding:0.75rem;">
                <div style="display:flex; gap:5px; justify-content:center; align-items:center;">
                  @php
                    $isSubmitted = ($sub->status === 'SUBMITTED');
                    $canOpenForEdit = !$isSubmitted && $userHasEditPermission;
                  @endphp
                  <a href="{{ $dailyUrl }}?date={{ $subDateStr }}" class="btn {{ !$canOpenForEdit ? 'btn-secondary' : '' }}" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; font-size:0.85rem; border-radius:6px;">
                    {{ $canOpenForEdit ? 'Open' : 'View' }}
                  </a>
                  @if(in_array($sub->status, ['SUBMITTED', 'PARTIAL_SAVED']))
                    @php
                        $dailyPdfUrl = $getDailyPdfUrl($subDateStr);
                    @endphp
                    <a href="{{ $dailyPdfUrl }}" class="btn" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; background:#c0392b; color:white; font-size:0.85rem; border-radius:6px;" target="_blank">
                      PDF ↓
                    </a>
                  @endif
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <p style="color:var(--text-muted); text-align:center; padding:2rem;">No recent attendance records found.</p>
      @endif
    </div>

    <!-- TAB 3: SUBMITTED RECORDS -->
    <div id="tab-content-submitted" class="att-tab-pane" style="display:none;">
      @if(isset($submittedSubmissions) && $submittedSubmissions->count() > 0)
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
          <thead>
            <tr style="border-bottom:2px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Date</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Day</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Status</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Done By</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569;">Submitted By</th>
              <th style="padding:0.75rem; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; color:#475569; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($submittedSubmissions as $sub)
            @php
              $subDate = \Carbon\Carbon::parse($sub->attendance_date);
              $subDateStr = $subDate->format('Y-m-d');
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:0.75rem; font-weight:bold; color:#0f172a;">{{ $subDate->format('d-m-Y') }}</td>
              <td style="padding:0.75rem; color:#475569;">{{ $subDate->format('l') }}</td>
              <td style="padding:0.75rem;">
                <span style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">🔒 SUBMITTED</span>
              </td>
              <td style="padding:0.75rem; color:#475569;">{{ $sub->createdBy->name ?? '—' }}</td>
              <td style="padding:0.75rem; color:#475569;">
                @if($sub->submittedBy)
                  <span style="font-weight:600; color:#1e293b;">{{ $sub->submittedBy->name }}</span>
                  <br><small style="color:#7f8c8d;">{{ $sub->submitted_at ? \Carbon\Carbon::parse($sub->submitted_at)->format('d-m-Y, H:i') : '' }}</small>
                @else
                  —
                @endif
              </td>
              <td style="padding:0.75rem;">
                <div style="display:flex; gap:5px; justify-content:center; align-items:center;">
                  <a href="{{ $dailyUrl }}?date={{ $subDateStr }}" class="btn btn-secondary" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; font-size:0.85rem; border-radius:6px;">
                    View
                  </a>
                  @php
                      $dailyPdfUrl = $getDailyPdfUrl($subDateStr);
                  @endphp
                  <a href="{{ $dailyPdfUrl }}" class="btn" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; background:#c0392b; color:white; font-size:0.85rem; border-radius:6px;" target="_blank">
                    PDF ↓
                  </a>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <p style="color:var(--text-muted); text-align:center; padding:2rem;">No submitted records found.</p>
      @endif
    </div>
  </div>

</div>

<!-- Add Worker Modal -->
<div id="addWorkerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:flex-start; justify-content:center; padding-top:10vh;">
    <div class="card white-orange-card" style="width:90%; max-width:700px; padding:1.5rem; position:relative;">
        <h3 style="margin-top:0;">Add Worker</h3>
        <form id="addWorkerForm" onsubmit="submitWorkerForm(event)">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1rem;">
                <div class="form-group" style="grid-column:1/-1;">
                  <label>Full Name</label>
                  <input type="text" name="name" required placeholder="e.g. Ram Kumar" style="width:100%;">
                </div>
                <div class="form-group">
                  <label>Department</label>
                  <select name="department_id" id="modal-dept" required style="width:100%;" onchange="handleModalDepartmentChange()">
                    <option value="">-- Select --</option>
                    @foreach($departments as $d)
                      <option value="{{ $d->id }}" data-name="{{ $d->name }}">{{ $d->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label>Role</label>
                  <input type="text" name="role" placeholder="e.g. Operator" style="width:100%;">
                </div>
                <div class="form-group">
                  <label>Shift Type</label>
                  <select name="shift_type" style="width:100%;">
                    <option value="DAY">Day Shift</option>
                    <option value="NIGHT">Night Shift</option>
                    <option value="CUSTOM">Custom</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Salary Type</label>
                  <select name="salary_type" id="modal-salary-type" onchange="updateModalSalaryLabel()" style="width:100%;">
                    <option value="DAILY">Daily (₹ / Day)</option>
                    <option value="MONTHLY">Monthly (₹ / Month)</option>
                    <option value="FIXED_MONTHLY">Fixed Monthly (₹ / Month)</option>
                    <option value="LABOUR_MUKADAM">MUKADAM (₹ / LABOUR)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label id="modal-salary-label">Daily Salary (₹)</label>
                  <input type="number" name="salary_amount" required min="0" step="1" style="width:100%;">
                </div>
                <div class="form-group" id="modal-per-hour-group">
                  <label>Per Hour Salary (₹)</label>
                  <input type="number" name="per_hour_salary" min="0" step="1" style="width:100%;">
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" style="width:100%;">
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                  </select>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem;">
                <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;">Save Worker</button>
                <button type="button" class="btn btn-secondary" onclick="closeAddWorkerModal()" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

function switchAttendanceTab(tabName) {
  document.querySelectorAll('.att-tab-pane').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.att-tab-btn').forEach(btn => {
    btn.style.background = 'transparent';
    btn.style.color = '#64748b';
    btn.style.boxShadow = 'none';
  });
  
  const targetPane = document.getElementById('tab-content-' + tabName);
  if (targetPane) targetPane.style.display = 'block';

  const targetBtn = document.getElementById('tab-btn-' + tabName);
  if (targetBtn) {
    targetBtn.style.background = '#ffffff';
    targetBtn.style.color = '#0f172a';
    targetBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
  }

  const activeTabInput = document.getElementById('activeTabInput');
  if (activeTabInput) activeTabInput.value = tabName;

  history.replaceState(null, null, '#' + tabName);
}

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const tabParam = urlParams.get('tab') || window.location.hash.replace('#', '');
  if (['pending', 'all', 'submitted'].includes(tabParam)) {
    switchAttendanceTab(tabParam);
  } else {
    switchAttendanceTab('all');
  }
});


const defaultModalSalaryOptions = [
  { value: 'DAILY', text: 'Daily (₹ / Day)' },
  { value: 'MONTHLY', text: 'Monthly (₹ / Month)' },
  { value: 'FIXED_MONTHLY', text: 'Fixed Monthly (₹ / Month)' },
  { value: 'LABOUR_MUKADAM', text: 'MUKADAM (₹ / LABOUR)' }
];

function handleModalDepartmentChange(targetSalaryType = null) {
  const deptSelect = document.getElementById('modal-dept');
  const selectedOption = deptSelect && deptSelect.selectedIndex >= 0 ? deptSelect.options[deptSelect.selectedIndex] : null;
  const deptName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text || '').trim().toUpperCase() : '';
  const salaryTypeSelect = document.getElementById('modal-salary-type');
  if (!salaryTypeSelect) return;
  
  const isMukadam = deptName.includes('MUKADAM');
  const currentVal = targetSalaryType || salaryTypeSelect.value;

  salaryTypeSelect.innerHTML = '';
  defaultModalSalaryOptions.forEach(opt => {
    if (isMukadam) {
      if (opt.value === 'LABOUR_MUKADAM') {
        salaryTypeSelect.add(new Option(opt.text, opt.value));
      }
    } else {
      if (opt.value !== 'LABOUR_MUKADAM') {
        salaryTypeSelect.add(new Option(opt.text, opt.value));
      }
    }
  });

  if (isMukadam) {
    salaryTypeSelect.value = 'LABOUR_MUKADAM';
  } else {
    salaryTypeSelect.value = (currentVal && currentVal !== 'LABOUR_MUKADAM') ? currentVal : 'DAILY';
  }

  updateModalSalaryLabel();
}

function updateModalSalaryLabel() {
  const salaryTypeSelect = document.getElementById('modal-salary-type');
  if (!salaryTypeSelect) return;
  const type = salaryTypeSelect.value;
  let lbl = 'Daily Salary (₹)';
  if (type === 'MONTHLY') lbl = 'Monthly Salary (₹)';
  if (type === 'FIXED_MONTHLY') lbl = 'Fixed Monthly Salary (₹)';
  if (type === 'LABOUR_MUKADAM') lbl = 'Per Labour Salary (₹)';
  
  const labelEl = document.getElementById('modal-salary-label');
  if (labelEl) labelEl.innerText = lbl;
  
  const perHourGroup = document.getElementById('modal-per-hour-group');
  if (perHourGroup) {
    const isMonthly = (type === 'MONTHLY' || type === 'FIXED_MONTHLY');
    perHourGroup.style.display = isMonthly ? 'none' : 'block';
    if (isMonthly) {
      const perHourInput = perHourGroup.querySelector('input[name="per_hour_salary"]');
      if (perHourInput) perHourInput.value = '';
    }
  }
}

function openAddWorkerModal() {
    const modal = document.getElementById('addWorkerModal');
    if (modal) modal.style.display = 'flex';
    handleModalDepartmentChange();
}

function closeAddWorkerModal() {
    const modal = document.getElementById('addWorkerModal');
    if (modal) modal.style.display = 'none';
    const form = document.getElementById('addWorkerForm');
    if (form) form.reset();
    handleModalDepartmentChange('DAILY');
}

function submitWorkerForm(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    Swal.fire({
        title: 'Saving Worker',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('{{ $workersUrl }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            Swal.fire('Saved!', d.message, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            Swal.fire('Error', d.message || 'Validation failed', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'An unexpected error occurred', 'error');
    });
}
</script>

<style>
/* White and Orange Theme for Forms */
.white-orange-card {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
.white-orange-card .card-title,
.white-orange-card h3,
.white-orange-card h4 {
    color: #333333 !important;
    font-weight: 700 !important;
}
.white-orange-card label {
    color: #4b5563 !important;
    font-weight: 600 !important;
    display: block;
    margin-bottom: 0.5rem;
}
.white-orange-card input,
.white-orange-card select,
.white-orange-card textarea {
    background-color: #f9fafb !important;
    border: 1px solid #d1d5db !important;
    color: #333333 !important;
    padding: 0.6rem !important;
    border-radius: 4px !important;
    -webkit-text-fill-color: #333333 !important;
}
.white-orange-card input::placeholder,
.white-orange-card textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.white-orange-card .btn-primary,
.white-orange-card button[type="submit"] {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: none !important;
}
.white-orange-card .btn-secondary,
.white-orange-card button[type="button"] {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
    border: none !important;
}
</style>
@endsection
