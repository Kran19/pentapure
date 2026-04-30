@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div class="flex-between mb-1">
    <h2 style="margin:0;">🏢 Departments</h2>
    <button class="btn btn-sm" style="width:auto; padding:0.4rem 1rem;" onclick="openDeptModal()">+ Add Department</button>
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

<!-- Modal -->
<div class="modal-overlay" id="dept-modal">
  <div class="modal-content">
    <h3 class="mb-1" id="dept-modal-title">Add Department</h3>
    <form id="dept-form">
      <div class="form-group">
        <label>Department Name</label>
        <input type="text" id="dept-name" required placeholder="e.g. Staff, Boiler">
      </div>
      <div style="display:flex; gap:10px; margin-top:1rem;">
        <button type="button" class="btn btn-secondary" onclick="closeDeptModal()">Cancel</button>
        <button type="submit" class="btn">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
let editingDeptId = null;

function openDeptModal() {
  editingDeptId = null;
  document.getElementById('dept-modal-title').innerText = 'Add Department';
  document.getElementById('dept-name').value = '';
  document.getElementById('dept-modal').classList.add('active');
}

function editDept(d) {
  editingDeptId = d.id;
  document.getElementById('dept-modal-title').innerText = 'Edit Department';
  document.getElementById('dept-name').value = d.name;
  document.getElementById('dept-modal').classList.add('active');
}

function closeDeptModal() {
  document.getElementById('dept-modal').classList.remove('active');
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
