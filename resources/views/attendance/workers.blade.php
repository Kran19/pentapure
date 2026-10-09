@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">👷‍♂️ Workers Master</h2>
    @if(empty($isReadOnly))
    <button class="btn" onclick="openWorkerForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add Worker</button>
    @endif
  </div>

  <!-- Add/Edit Form Card -->
  <div id="worker-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="w-form-title">Add Worker</div>
    <form id="worker-form">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1rem;">
        <div class="form-group" style="grid-column:1/-1;">
          <label>Full Name</label>
          <input type="text" id="w-name" required placeholder="e.g. Ram Kumar">
        </div>
        <div class="form-group">
          <label>Department</label>
          <select id="w-dept" required onchange="handleDepartmentChange()">
            <option value="">-- Select --</option>
            @foreach($departments as $d)
              <option value="{{ $d->id }}" data-name="{{ $d->name }}">{{ $d->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Role</label>
          <input type="text" id="w-role" placeholder="e.g. Operator">
        </div>
        <div class="form-group">
          <label>Shift Type</label>
          <select id="w-shift">
            <option value="DAY">Day Shift</option>
            <option value="NIGHT">Night Shift</option>
            <option value="CUSTOM">Custom</option>
          </select>
        </div>
        <div class="form-group">
          <label>Salary Type</label>
          <select id="w-salary-type" onchange="updateSalaryLabel()">
            <option value="DAILY">Daily (₹ / Day)</option>
            <option value="MONTHLY">Monthly (₹ / Month)</option>
            <option value="FIXED_MONTHLY">Fixed Monthly (₹ / Month)</option>
            <option value="LABOUR_MUKADAM">MUKADAM (₹ / LABOUR)</option>
          </select>
        </div>
        <div class="form-group">
          <label id="salary-label">Salary Amount (₹)</label>
          <input type="number" id="w-salary" required min="0" step="1">
        </div>
        <div class="form-group" id="per-hour-group">
          <label>Per Hour Salary (₹)</label>
          <input type="number" id="w-per-hour" min="0" step="1">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="w-status">
            <option value="ACTIVE">Active</option>
            <option value="INACTIVE">Inactive</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:1rem; margin-top:1.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;">Save Worker</button>
        <button type="button" class="btn btn-secondary" onclick="closeWorkerForm()" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th style="width:45px;">#</th>
            <th>Name</th>
            <th>Department</th>
            <th>Role</th>
            <th>Shift</th>
            <th>Salary</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($workers as $i => $w)
          <tr class="worker-row">
            <td style="text-align:center; font-weight:bold; color:var(--text-muted); font-size:0.85rem;">{{ $i + 1 }}</td>
            <td style="font-weight:600;">{{ $w->name }}</td>
            <td>{{ $w->department->name }}</td>
            <td style="color:var(--text-muted);">{{ $w->role ?? '—' }}</td>
            <td><span class="badge {{ $w->shift_type=='NIGHT'?'badge-danger':'badge-info' }}">{{ $w->shift_type }}</span></td>
            <td style="font-weight:bold; color:var(--primary-light);">
              ₹{{ number_format($w->salary_amount, 2) }}
              @php
                $isMukadam = ($w->salary_type === 'LABOUR_MUKADAM' || stripos($w->department->name ?? '', 'MUKADAM') !== false);
              @endphp
              @if($isMukadam)
                <div style="font-size:0.65rem; font-weight:bold; color:#000000;">PER LABOUR SALARY</div>
              @else
                <div style="font-size:0.65rem; opacity:0.7; color:var(--text-muted);">{{ $w->salary_type }}</div>
              @endif
            </td>
            <td>
              <span class="badge {{ $w->status=='ACTIVE'?'badge-done':'badge-danger' }}"
                style="cursor:pointer;"
                onclick="toggleWorkerStatus({{ $w->id }}, '{{ addslashes($w->name) }}', '{{ $w->status }}')"
                title="Click to {{ $w->status == 'ACTIVE' ? 'Disable (mark Inactive)' : 'Enable (mark Active)' }}">
                {{ $w->status == 'ACTIVE' ? 'Active' : 'Inactive (Disabled)' }}
              </span>
            </td>
            <td>
              <div class="action-btns" style="display:flex; align-items:center; gap:6px;">
                @if(empty($isReadOnly))
                  <button type="button" class="btn-icon edit" onclick="editWorker({{ json_encode($w) }})" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                  </button>

                  @if($w->status === 'ACTIVE')
                    <button type="button" class="btn-icon" onclick="toggleWorkerStatus({{ $w->id }}, '{{ addslashes($w->name) }}', 'ACTIVE')"
                      style="background:rgba(239, 68, 68, 0.12); color:#dc2626; border:1px solid rgba(239, 68, 68, 0.3); border-radius:6px; padding:4px 8px; font-size:0.75rem; font-weight:700; cursor:pointer;"
                      title="Disable Worker (mark Inactive)">
                      Disable
                    </button>
                  @else
                    <button type="button" class="btn-icon" onclick="toggleWorkerStatus({{ $w->id }}, '{{ addslashes($w->name) }}', 'INACTIVE')"
                      style="background:rgba(22, 163, 74, 0.12); color:#16a34a; border:1px solid rgba(22, 163, 74, 0.3); border-radius:6px; padding:4px 8px; font-size:0.75rem; font-weight:700; cursor:pointer;"
                      title="Enable Worker (mark Active)">
                      Enable
                    </button>
                  @endif

                  @if(!empty($w->is_used))
                    <button type="button" class="btn-icon delete" disabled
                      onclick="inUseWorkerAlert('{{ addslashes($w->name) }}', {{ $w->id }}, '{{ $w->status }}')"
                      style="opacity:0.35; cursor:not-allowed; background:#f3f4f6; color:#9ca3af; border:1px solid #d1d5db;"
                      title="Worker in use (Attendance / Salary records exist) - Cannot delete, Disable instead">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  @else
                    <button type="button" class="btn-icon delete" onclick="deleteWorker({{ $w->id }})" title="Delete Worker (No records in use)">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                  @endif
                @else
                  <span style="font-size:0.8rem; color:var(--text-muted);">View Only</span>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal removed, now using inline form -->

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
let editingWorkerId = null;

const defaultSalaryOptions = [
  { value: 'DAILY', text: 'Daily (₹ / Day)' },
  { value: 'MONTHLY', text: 'Monthly (₹ / Month)' },
  { value: 'FIXED_MONTHLY', text: 'Fixed Monthly (₹ / Month)' },
  { value: 'LABOUR_MUKADAM', text: 'MUKADAM (₹ / LABOUR)' }
];

function handleDepartmentChange(targetSalaryType = null) {
  const deptSelect = document.getElementById('w-dept');
  const selectedOption = deptSelect && deptSelect.selectedIndex >= 0 ? deptSelect.options[deptSelect.selectedIndex] : null;
  const deptName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text || '').trim().toUpperCase() : '';
  const salaryTypeSelect = document.getElementById('w-salary-type');
  if (!salaryTypeSelect) return;
  
  const isMukadam = deptName.includes('MUKADAM');
  const currentVal = targetSalaryType || salaryTypeSelect.value;

  salaryTypeSelect.innerHTML = '';
  defaultSalaryOptions.forEach(opt => {
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
  
  updateSalaryLabel();
}

function openWorkerForm() {
  editingWorkerId = null;
  document.getElementById('w-form-title').innerText = 'Add Worker';
  document.getElementById('w-name').value = '';
  document.getElementById('w-dept').value = '';
  document.getElementById('w-role').value = '';
  document.getElementById('w-shift').value = 'DAY';
  document.getElementById('w-salary').value = '';
  document.getElementById('w-per-hour').value = '';
  document.getElementById('w-status').value = 'ACTIVE';
  handleDepartmentChange('DAILY');
  document.getElementById('worker-form-card').style.display = 'block';
  document.getElementById('worker-form-card').scrollIntoView({ behavior: 'smooth' });
}

function updateSalaryLabel() {
  const salaryTypeSelect = document.getElementById('w-salary-type');
  if (!salaryTypeSelect) return;
  const type = salaryTypeSelect.value;
  let label = 'Daily Salary (₹)';
  if (type === 'MONTHLY') label = 'Monthly Salary (₹)';
  if (type === 'FIXED_MONTHLY') label = 'Fixed Monthly Salary (₹)';
  if (type === 'LABOUR_MUKADAM') label = 'Per Labour Salary (₹)';
  document.getElementById('salary-label').innerText = label;
  
  const perHourGroup = document.getElementById('per-hour-group');
  if (perHourGroup) {
      const isMonthly = (type === 'MONTHLY' || type === 'FIXED_MONTHLY');
      perHourGroup.style.display = isMonthly ? 'none' : 'block';
      if (isMonthly) {
          const perHourInput = document.getElementById('w-per-hour');
          if (perHourInput) perHourInput.value = '';
      }
  }
}

function editWorker(w) {
  editingWorkerId = w.id;
  document.getElementById('w-form-title').innerText = 'Edit Worker';
  document.getElementById('w-name').value = w.name;
  document.getElementById('w-dept').value = w.department_id;
  document.getElementById('w-role').value = w.role || '';
  document.getElementById('w-shift').value = w.shift_type || 'DAY';
  
  handleDepartmentChange(w.salary_type);
  
  document.getElementById('w-salary').value = w.salary_amount;
  document.getElementById('w-per-hour').value = w.per_hour_salary || '';
  document.getElementById('w-status').value = w.status || 'ACTIVE';
  updateSalaryLabel();
  document.getElementById('worker-form-card').style.display = 'block';
  document.getElementById('worker-form-card').scrollIntoView({ behavior: 'smooth' });
}

function closeWorkerForm() {
  document.getElementById('worker-form-card').style.display = 'none';
}

document.getElementById('worker-form').onsubmit = function(e) {
  e.preventDefault();
  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({
      worker_id: editingWorkerId,
      name: document.getElementById('w-name').value,
      department_id: document.getElementById('w-dept').value,
      role: document.getElementById('w-role').value,
      shift_type: document.getElementById('w-shift').value,
      salary_type: document.getElementById('w-salary-type').value,
      salary_amount: document.getElementById('w-salary').value,
      per_hour_salary: document.getElementById('w-per-hour').value,
      status: document.getElementById('w-status').value
    })
  }).then(r=>r.json()).then(d=>{
    if(d.success) { Swal.fire('Success', d.message, 'success'); setTimeout(()=>location.reload(),800); }
    else Swal.fire('Error', d.message || 'Validation failed', 'error');
  }).catch(e=>{
    Swal.fire('Error', 'An unexpected error occurred or validation failed.', 'error');
  });
};



function inUseWorkerAlert(name, id, currentStatus) {
  if (currentStatus === 'ACTIVE') {
    Swal.fire({
      title: 'Cannot Delete Worker',
      text: `Worker "${name}" has associated attendance or salary records and cannot be deleted. Would you like to Disable (mark INACTIVE) this worker instead?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Disable Worker',
      confirmButtonColor: '#f59e0b',
      cancelButtonText: 'Cancel'
    }).then((res) => {
      if (res.isConfirmed) {
        toggleWorkerStatus(id, name, 'ACTIVE');
      }
    });
  } else {
    Swal.fire({
      title: 'Worker In Use & Disabled',
      text: `Worker "${name}" is already Disabled (Inactive). This worker cannot be deleted permanently because historical attendance or salary records exist for data integrity.`,
      icon: 'info',
      confirmButtonText: 'OK'
    });
  }
}

function toggleWorkerStatus(id, name, currentStatus) {
  const isActivating = (currentStatus === 'INACTIVE');
  const actionText = isActivating ? 'Enable (Activate)' : 'Disable (Deactivate)';
  Swal.fire({
    title: `${actionText} Worker?`,
    text: isActivating 
      ? `Are you sure you want to activate worker "${name}"?` 
      : `Are you sure you want to disable worker "${name}"? They will no longer appear for daily attendance marking.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: isActivating ? '#16a34a' : '#d33',
    confirmButtonText: isActivating ? 'Yes, Enable' : 'Yes, Disable'
  }).then((res) => {
    if (res.isConfirmed) {
      const baseUrl = window.location.href.split('?')[0].replace(/\/$/, '');
      fetch(`${baseUrl}/${id}/toggle-status`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken 
        }
      }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) throw new Error(d.message || ('Server error ' + r.status));
        return d;
      }).then(d => {
        Swal.fire('Success', d.message || 'Status updated', 'success');
        setTimeout(() => location.reload(), 700);
      }).catch(err => Swal.fire('Error', err.message || 'Failed to update worker status', 'error'));
    }
  });
}

function deleteWorker(id) {
  Swal.fire({
    title: 'Delete Worker?',
    text: "This removes the worker profile, but cannot be done if attendance or salary records exist.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete'
  }).then((res) => {
    if(res.isConfirmed) {
      const targetUrl = window.location.href.split('?')[0].replace(/\/$/, '') + '/' + id;
      fetch(targetUrl, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
      }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) {
          throw new Error(d.message || ('Deletion failed (status ' + r.status + ')'));
        }
        return d;
      }).then(d => {
        Swal.fire('Deleted!', d.message || '', 'success'); 
        setTimeout(()=>location.reload(),800); 
      }).catch(e => {
        Swal.fire({
          title: 'Cannot Delete Worker',
          text: e.message || 'Worker cannot be deleted because they are in use. Would you like to Disable this worker instead?',
          icon: 'error',
          showCancelButton: true,
          confirmButtonText: 'Disable Worker Instead',
          confirmButtonColor: '#f59e0b',
          cancelButtonText: 'Cancel'
        }).then((promptRes) => {
          if (promptRes.isConfirmed) {
            toggleWorkerStatus(id, '', 'ACTIVE');
          }
        });
      });
    }
  });
}
</script>
@endsection

<style>
/* White and Orange Theme for Forms */
.white-orange-card {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
.white-orange-card .card-title,
.white-orange-card h4 {
    color: #333333 !important;
    font-weight: 700 !important;
}
.white-orange-card label {
    color: #4b5563 !important;
    font-weight: 600 !important;
}
.white-orange-card input,
.white-orange-card select,
.white-orange-card textarea {
    background-color: #f9fafb !important;
    border: 1px solid #d1d5db !important;
    color: #333333 !important;
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
.white-orange-card span {
    color: #333333 !important;
}
</style>
