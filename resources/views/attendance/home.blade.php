@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📊 Attendance Dashboard</h2>
    <button class="btn" onclick="openAddWorkerModal()" style="width:auto; padding:0.6rem 1.5rem;">+ Add Worker</button>
  </div>

@php
  $prefix = request()->segment(1) == 'admin' ? 'admin' : 'attendance';
  $workersUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.workers') : route(request()->segment(1) . '.workers');
  $dailyUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.daily') : route(request()->segment(1) . '.daily');
  $reportsUrl = $prefix == 'admin' ? route(request()->segment(1) . '.attendance.reports') : route(request()->segment(1) . '.history');
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
  </div>

  <!-- Quick Actions -->
  <div class="card" style="margin-top:1.5rem; padding:1.5rem;">
    <h3 style="margin-top:0; margin-bottom:1rem;">⚡ Quick Actions</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1rem;">
      <a href="{{ $dailyUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">📝 Daily Punch Sheet</a>
      <a href="{{ $reportsUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">📊 Monthly Reports</a>
      <a href="{{ $workersUrl }}" class="btn btn-secondary" style="text-align:center; padding:1rem; border-radius:8px; text-decoration:none;">👥 Manage Workers</a>
    </div>
  </div>

  <!-- Recent Attendance Status -->
  <div class="card" style="margin-top:1.5rem; padding:1.5rem;">
    <h3 style="margin-top:0; margin-bottom:1rem;">📅 Recent Attendance Status</h3>
    @if(isset($recentSubmissions) && $recentSubmissions->count() > 0)
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
          <tr style="border-bottom:2px solid #eee;">
            <th style="padding:0.75rem;">Date</th>
            <th style="padding:0.75rem;">Day</th>
            <th style="padding:0.75rem;">Status</th>
            <th style="padding:0.75rem;">Done By</th>
            <th style="padding:0.75rem;">Submitted By</th>
            <th style="padding:0.75rem; text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentSubmissions as $sub)
          <tr style="border-bottom:1px solid #eee;">
            <td style="padding:0.75rem; font-weight:bold;">{{ $sub->attendance_date->format('d M Y') }}</td>
            <td style="padding:0.75rem;">{{ $sub->attendance_date->format('l') }}</td>
            <td style="padding:0.75rem;">
              @if($sub->status === 'SUBMITTED')
                <span style="background:#e74c3c; color:white; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">🔒 SUBMITTED</span>
              @elseif($sub->status === 'PARTIAL_SAVED')
                <span style="background:#f39c12; color:white; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">⏳ PARTIAL SAVED</span>
              @else
                <span style="background:#3498db; color:white; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">🕒 PENDING</span>
              @endif
            </td>
            <td style="padding:0.75rem;">{{ $sub->createdBy->name ?? '—' }}</td>
            <td style="padding:0.75rem;">
              @if($sub->submittedBy)
                {{ $sub->submittedBy->name }} <br><small style="color:#7f8c8d;">{{ $sub->submitted_at->format('d M, H:i') }}</small>
              @else
                —
              @endif
            </td>
            <td style="padding:0.75rem;">
              <div style="display:flex; gap:5px; justify-content:center; align-items:center;">
                <a href="{{ $dailyUrl }}?date={{ $sub->attendance_date->format('Y-m-d') }}" class="btn {{ $sub->status === 'SUBMITTED' ? 'btn-secondary' : '' }}" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; font-size: 0.85rem;">
                  {{ $sub->status === 'SUBMITTED' && $prefix !== 'admin' ? 'View' : 'Open' }}
                </a>
                @if(in_array($sub->status, ['SUBMITTED', 'PARTIAL_SAVED']))
                  @php
                      $pdfRoute = (isset($prefix) && $prefix === 'admin') ? 'admin.attendance.daily.pdf' : 'attendance.daily.pdf';
                  @endphp
                  <a href="{{ route($pdfRoute, ['date' => $sub->attendance_date->format('Y-m-d')]) }}" class="btn" style="padding:0.3rem 0.6rem; text-decoration:none; display:inline-block; background: #c0392b; color: white; font-size: 0.85rem;" target="_blank">
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
    <p style="color:var(--text-muted);">No recent attendance records found.</p>
    @endif
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
                  <select name="department_id" required style="width:100%;">
                    <option value="">-- Select --</option>
                    @foreach($departments as $d)
                      <option value="{{ $d->id }}">{{ $d->name }}</option>
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
                  <select name="salary_type" id="modal-salary-type" onchange="let lbl = 'Daily Salary (₹)'; if(this.value === 'MONTHLY') lbl = 'Monthly Salary (₹)'; if(this.value === 'FIXED_MONTHLY') lbl = 'Fixed Monthly Salary (₹)'; if(this.value === 'LABOUR_MUKADAM') lbl = 'Per Labour Salary (₹)'; document.getElementById('modal-salary-label').innerText = lbl; document.getElementById('modal-per-hour-group').style.display = (this.value === 'FIXED_MONTHLY') ? 'none' : 'block';" style="width:100%;">
                    <option value="DAILY">Daily (₹ / Day)</option>
                    <option value="MONTHLY">Monthly (₹ / Month)</option>
                    <option value="FIXED_MONTHLY">Fixed Monthly (₹ / Month)</option>
                    <option value="LABOUR_MUKADAM">LABOUR(MUKADAM)</option>
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
function openAddWorkerModal() {
    document.getElementById('addWorkerModal').style.display = 'flex';
}

function closeAddWorkerModal() {
    document.getElementById('addWorkerModal').style.display = 'none';
    document.getElementById('addWorkerForm').reset();
    document.getElementById('modal-salary-label').innerText = 'Daily Salary (₹)';
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
