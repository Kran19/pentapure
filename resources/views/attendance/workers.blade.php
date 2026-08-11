@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">👷‍♂️ Workers Master</h2>
    <button class="btn" onclick="openWorkerForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add Worker</button>
  </div>

  <!-- Add/Edit Form Card (Open by default) -->
  <div id="worker-form-card" class="card white-orange-card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="w-form-title">Add Worker</div>
    <form id="worker-form">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1rem;">
        <div class="form-group" style="grid-column:1/-1;">
          <label>Full Name</label>
          <input type="text" id="w-name" required placeholder="e.g. Ram Kumar">
        </div>
        <div class="form-group">
          <label>Department</label>
          <select id="w-dept" required>
            <option value="">-- Select --</option>
            @foreach($departments as $d)
              <option value="{{ $d->id }}">{{ $d->name }}</option>
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
            <option value="LABOUR_MUKADAM">Labour (Mukadam)</option>
          </select>
        </div>
        <div class="form-group">
          <label id="salary-label">Salary Amount (₹)</label>
          <input type="number" id="w-salary" required min="0" step="1">
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
          @foreach($workers as $w)
          <tr class="worker-row">
            <td style="font-weight:600;">{{ $w->name }}</td>
            <td>{{ $w->department->name }}</td>
            <td style="color:var(--text-muted);">{{ $w->role ?? '—' }}</td>
            <td><span class="badge {{ $w->shift_type=='NIGHT'?'badge-danger':'badge-info' }}">{{ $w->shift_type }}</span></td>
            <td style="font-weight:bold; color:var(--primary-light);">
              ₹{{ number_format($w->salary_amount, 2) }}
              <div style="font-size:0.65rem; opacity:0.7; color:var(--text-muted);">{{ $w->salary_type }}</div>
            </td>
            <td>
              <span class="badge {{ $w->status=='ACTIVE'?'badge-done':'badge-danger' }}">{{ $w->status }}</span>
            </td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="editWorker({{ json_encode($w) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon delete" onclick="deleteWorker({{ $w->id }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
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
let editingWorkerId = null;

function openWorkerForm() {
  editingWorkerId = null;
  document.getElementById('w-form-title').innerText = 'Add Worker';
  document.getElementById('w-name').value = '';
  document.getElementById('w-dept').value = '';
  document.getElementById('w-role').value = '';
  document.getElementById('w-shift').value = 'DAY';
  document.getElementById('w-salary-type').value = 'DAILY';
  document.getElementById('w-salary').value = '';
  document.getElementById('w-status').value = 'ACTIVE';
  updateSalaryLabel();
  document.getElementById('worker-form-card').style.display = 'block';
  document.getElementById('worker-form-card').scrollIntoView({ behavior: 'smooth' });
}

function updateSalaryLabel() {
  const type = document.getElementById('w-salary-type').value;
  document.getElementById('salary-label').innerText = (type === 'DAILY') ? 'Daily Salary (₹)' : 'Monthly Salary (₹)';
}

function editWorker(w) {
  let deptsOptions = `@foreach($departments as $d)
    <option value="{{ $d->id }}">{{ $d->name }}</option>
  @endforeach`;

  Swal.fire({
    title: 'Edit Worker',
    html: `
      <div style="display:flex; flex-direction:column; gap:10px; text-align:left;">
        <label style="font-weight:600; color:#4b5563;">Full Name</label>
        <input type="text" id="edit-w-name" class="swal2-input" style="margin:0;" value="${String(w.name || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]))}">
        
        <label style="font-weight:600; color:#4b5563;">Department</label>
        <select id="edit-w-dept" class="swal2-select" style="margin:0; width:100%; display:block;">
          ${deptsOptions}
        </select>
        
        <label style="font-weight:600; color:#4b5563;">Role</label>
        <input type="text" id="edit-w-role" class="swal2-input" style="margin:0;" value="${String(w.role || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]))}">
        
        <label style="font-weight:600; color:#4b5563;">Shift Type</label>
        <select id="edit-w-shift" class="swal2-select" style="margin:0; width:100%; display:block;">
          <option value="DAY">Day Shift</option>
          <option value="NIGHT">Night Shift</option>
          <option value="CUSTOM">Custom</option>
        </select>
        
        <label style="font-weight:600; color:#4b5563;">Salary Type</label>
        <select id="edit-w-salary-type" class="swal2-select" style="margin:0; width:100%; display:block;" onchange="
            const t = this.value; 
            document.getElementById('edit-salary-label').innerText = (t === 'DAILY') ? 'Daily Salary (₹)' : 'Monthly Salary (₹)';
        ">
            <option value="DAILY">Daily (₹ / Day)</option>
            <option value="MONTHLY">Monthly (₹ / Month)</option>
            <option value="FIXED_MONTHLY">Fixed Monthly (₹ / Month)</option>
            <option value="LABOUR_MUKADAM">Labour (Mukadam)</option>
        </select>
        
        <label id="edit-salary-label" style="font-weight:600; color:#4b5563;">Salary Amount (₹)</label>
        <input type="number" id="edit-w-salary" class="swal2-input" style="margin:0;" value="${w.salary_amount || w.daily_salary || 0}">
        
        <label style="font-weight:600; color:#4b5563;">Status</label>
        <select id="edit-w-status" class="swal2-select" style="margin:0; width:100%; display:block;">
          <option value="ACTIVE">Active</option>
          <option value="INACTIVE">Inactive</option>
        </select>
      </div>
    `,
    didOpen: () => {
        document.getElementById('edit-w-dept').value = w.department_id;
        document.getElementById('edit-w-shift').value = w.shift_type;
        document.getElementById('edit-w-salary-type').value = w.salary_type || 'DAILY';
        document.getElementById('edit-w-status').value = w.status;
        const t = document.getElementById('edit-w-salary-type').value;
        document.getElementById('edit-salary-label').innerText = (t === 'DAILY') ? 'Daily Salary (₹)' : 'Monthly Salary (₹)';
    },
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    confirmButtonColor: '#f59e0b',
    preConfirm: () => {
      const name = document.getElementById('edit-w-name').value.trim();
      const department_id = document.getElementById('edit-w-dept').value;
      if (!name || !department_id) {
        Swal.showValidationMessage('Name and Department are required');
      }
      return {
        name,
        department_id,
        role: document.getElementById('edit-w-role').value.trim(),
        shift_type: document.getElementById('edit-w-shift').value,
        salary_type: document.getElementById('edit-w-salary-type').value,
        salary_amount: document.getElementById('edit-w-salary').value,
        status: document.getElementById('edit-w-status').value
      };
    }
  }).then((res) => {
    if (res.isConfirmed) {
      fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ worker_id: w.id, ...res.value })
      }).then(r=>r.json()).then(resp=>{
        if(resp.success) { 
          Swal.fire('Success', resp.message, 'success'); 
          setTimeout(()=>location.reload(),800); 
        } else {
          Swal.fire('Error', resp.message, 'error');
        }
      });
    }
  });
}

function closeWorkerForm() {
  document.getElementById('worker-form-card').style.display = 'none';
}

document.getElementById('worker-form').onsubmit = function(e) {
  e.preventDefault();
  fetch(window.location.pathname, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({
      worker_id: editingWorkerId,
      name: document.getElementById('w-name').value,
      department_id: document.getElementById('w-dept').value,
      role: document.getElementById('w-role').value,
      shift_type: document.getElementById('w-shift').value,
      salary_type: document.getElementById('w-salary-type').value,
      salary_amount: document.getElementById('w-salary').value,
      status: document.getElementById('w-status').value
    })
  }).then(r=>r.json()).then(d=>{
    if(d.success) { Swal.fire('Success', d.message, 'success'); setTimeout(()=>location.reload(),800); }
    else Swal.fire('Error', d.message, 'error');
  });
};

function deleteWorker(id) {
  Swal.fire({
    title: 'Delete Worker?',
    text: "This removes the worker profile, but attendance history might be affected.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete'
  }).then((res) => {
    if(res.isConfirmed) {
      fetch(`${window.location.pathname}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
      }).then(r=>r.json()).then(d=>{
        if(d.success) { Swal.fire('Deleted!', '', 'success'); setTimeout(()=>location.reload(),800); }
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
