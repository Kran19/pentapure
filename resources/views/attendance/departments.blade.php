@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🏢 Departments</h2>
    <button class="btn" onclick="openDeptForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add Department</button>
  </div>

  <!-- Add/Edit Form Card (Open by default) -->
  <div id="dept-form-card" class="card white-orange-card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="dept-form-title">Add Department</div>
    <form id="dept-form">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
        <div class="form-group">
          <label>Department Name</label>
          <input type="text" id="dept-name" required placeholder="e.g. Staff, Boiler">
        </div>
      </div>
      <div style="display:flex; gap:1rem; margin-top:1.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;">Save</button>
        <button type="button" class="btn btn-secondary" onclick="closeDeptForm()" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Department Name</th>
            <th>Total Workers</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($departments as $d)
          <tr>
            <td>{{ $d->id }}</td>
            <td style="font-weight:600;">{{ $d->name }}</td>
            <td><span class="badge badge-info">{{ $d->workers_count }}</span></td>
            <td><span class="badge {{ $d->is_active ? 'badge-done' : 'badge-danger' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="editDept({{ json_encode($d) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if($d->workers_count == 0)
                <button class="btn-icon delete" onclick="deleteDept({{ $d->id }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
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
let editingDeptId = null;

function openDeptForm() {
  editingDeptId = null;
  document.getElementById('dept-form-title').innerText = 'Add Department';
  document.getElementById('dept-name').value = '';
  document.getElementById('dept-form-card').style.display = 'block';
  document.getElementById('dept-form-card').scrollIntoView({ behavior: 'smooth' });
}

function editDept(d) {
  Swal.fire({
    title: 'Edit Department',
    html: `
      <div style="display:flex; flex-direction:column; gap:10px; text-align:left;">
        <label style="font-weight:600; color:#4b5563;">Department Name</label>
        <input type="text" id="edit-dept-name" class="swal2-input" style="margin:0;" value="${String(d.name || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]))}">
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    confirmButtonColor: '#f59e0b',
    preConfirm: () => {
      const name = document.getElementById('edit-dept-name').value.trim();
      if (!name) Swal.showValidationMessage('Department name is required');
      return name;
    }
  }).then((res) => {
    if (res.isConfirmed) {
      fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ department_id: d.id, name: res.value })
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

function closeDeptForm() {
  document.getElementById('dept-form-card').style.display = 'none';
}

document.getElementById('dept-form').onsubmit = function(e) {
  e.preventDefault();
  fetch(window.location.pathname, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({
      department_id: editingDeptId,
      name: document.getElementById('dept-name').value
    })
  }).then(r=>r.json()).then(d=>{
    if(d.success) { Swal.fire('Success', d.message, 'success'); setTimeout(()=>location.reload(),800); }
    else Swal.fire('Error', d.message, 'error');
  });
};

function deleteDept(id) {
  Swal.fire({
    title: 'Delete Department?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete'
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
