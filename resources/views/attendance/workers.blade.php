@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1">
    <h2 style="margin:0;">👷‍♂️ Workers Master</h2>
    <button class="btn btn-sm" style="width:auto; padding:0.4rem 1rem;" onclick="openWorkerModal()">+ Add Worker</button>
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
            <th>Daily Salary</th>
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
            <td style="font-weight:bold; color:var(--primary-light);">₹{{ number_format($w->daily_salary, 2) }}</td>
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

<!-- Modal -->
<div class="modal-overlay" id="worker-modal">
  <div class="modal-content" style="max-width:500px;">
    <h3 class="mb-1" id="w-modal-title">Add Worker</h3>
    <form id="worker-form">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
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
          <label>Daily Salary (₹)</label>
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
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeWorkerModal()">Cancel</button>
        <button type="submit" class="btn">Save Worker</button>
      </div>
    </form>
  </div>
</div>

<script>
let editingWorkerId = null;

function openWorkerModal() {
  editingWorkerId = null;
  document.getElementById('w-modal-title').innerText = 'Add Worker';
  document.getElementById('w-name').value = '';
  document.getElementById('w-dept').value = '';
  document.getElementById('w-role').value = '';
  document.getElementById('w-shift').value = 'DAY';
  document.getElementById('w-salary').value = '';
  document.getElementById('w-status').value = 'ACTIVE';
  document.getElementById('worker-modal').classList.add('active');
}

function editWorker(w) {
  editingWorkerId = w.id;
  document.getElementById('w-modal-title').innerText = 'Edit Worker';
  document.getElementById('w-name').value = w.name;
  document.getElementById('w-dept').value = w.department_id;
  document.getElementById('w-role').value = w.role || '';
  document.getElementById('w-shift').value = w.shift_type;
  document.getElementById('w-salary').value = parseFloat(w.daily_salary);
  document.getElementById('w-status').value = w.status;
  document.getElementById('worker-modal').classList.add('active');
}

function closeWorkerModal() {
  document.getElementById('worker-modal').classList.remove('active');
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
      daily_salary: document.getElementById('w-salary').value,
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
