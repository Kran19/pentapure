@extends($layout)

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🏢 Departments</h2>
    @if(empty($isReadOnly))
    <button class="btn" onclick="openDeptForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add Department</button>
    @endif
  </div>

  <!-- Add/Edit Form Card -->
  <div id="dept-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="dept-form-title">Add Department</div>
    <form id="dept-form">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
        <div class="form-group">
          <label>Department Name</label>
          <input type="text" id="dept-name" required placeholder="e.g. Staff, Boiler">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="dept-status" style="width:100%; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid #d1d5db; background:#f9fafb;">
            <option value="1">Active</option>
            <option value="0">Inactive (Disabled)</option>
          </select>
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
            <td>
              <span class="badge {{ $d->is_active ? 'badge-done' : 'badge-danger' }}"
                style="cursor:pointer;"
                onclick="toggleDeptStatus({{ $d->id }}, '{{ addslashes($d->name) }}', {{ $d->is_active ? 'false' : 'true' }})"
                title="Click to {{ $d->is_active ? 'Disable' : 'Enable' }} Department">
                {{ $d->is_active ? 'Active' : 'Inactive (Disabled)' }}
              </span>
            </td>
            <td>
              <div class="action-btns" style="display:flex; align-items:center; gap:6px;">
                @if(strtoupper(trim($d->name)) === 'MUKADAM')
                  <span class="badge badge-info" style="font-size:0.75rem; background:var(--primary-light, #f59e0b); color:#fff; padding:4px 8px; border-radius:4px; font-weight:bold;">FIXED DEPT</span>
                @elseif(empty($isReadOnly))
                  <button class="btn-icon edit" onclick="editDept({{ json_encode($d) }})" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                  </button>

                  @if($d->is_active)
                    <button type="button" class="btn-icon" onclick="toggleDeptStatus({{ $d->id }}, '{{ addslashes($d->name) }}', false)"
                      style="background:rgba(239, 68, 68, 0.12); color:#dc2626; border:1px solid rgba(239, 68, 68, 0.3); border-radius:6px; padding:4px 8px; font-size:0.75rem; font-weight:700; cursor:pointer;"
                      title="Disable Department">
                      Disable
                    </button>
                  @else
                    <button type="button" class="btn-icon" onclick="toggleDeptStatus({{ $d->id }}, '{{ addslashes($d->name) }}', true)"
                      style="background:rgba(22, 163, 74, 0.12); color:#16a34a; border:1px solid rgba(22, 163, 74, 0.3); border-radius:6px; padding:4px 8px; font-size:0.75rem; font-weight:700; cursor:pointer;"
                      title="Enable Department">
                      Enable
                    </button>
                  @endif

                  @if(!empty($d->is_used))
                    <button type="button" class="btn-icon delete" disabled
                      onclick="inUseDeptAlert('{{ addslashes($d->name) }}', {{ $d->id }}, {{ $d->is_active ? 'true' : 'false' }})"
                      style="opacity:0.35; cursor:not-allowed; background:#f3f4f6; color:#9ca3af; border:1px solid #d1d5db;"
                      title="Department in use (workers/attendance exist) - Cannot delete, Disable instead">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="14" x2="14" y2="17"></line></svg>
                    </button>
                  @else
                    <button type="button" class="btn-icon delete" onclick="deleteDept({{ $d->id }})" title="Delete Department (No workers or records in use)">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="14" x2="14" y2="17"></line></svg>
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

<script>
let editingDeptId = null;

function openDeptForm() {
  editingDeptId = null;
  document.getElementById('dept-form-title').innerText = 'Add Department';
  document.getElementById('dept-name').value = '';
  document.getElementById('dept-status').value = '1';
  document.getElementById('dept-form-card').style.display = 'block';
  document.getElementById('dept-form-card').scrollIntoView({ behavior: 'smooth' });
}

function editDept(d) {
  Swal.fire({
    title: 'Edit Department',
    html: `
      <div style="display:flex; flex-direction:column; gap:12px; text-align:left;">
        <div>
          <label style="font-weight:600; color:#4b5563; font-size:0.85rem; margin-bottom:4px; display:block;">Department Name</label>
          <input type="text" id="edit-dept-name" class="swal2-input" style="margin:0; width:100%; box-sizing:border-box;" value="${String(d.name || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]))}">
        </div>
        <div>
          <label style="font-weight:600; color:#4b5563; font-size:0.85rem; margin-bottom:4px; display:block;">Status</label>
          <select id="edit-dept-status" class="swal2-input" style="margin:0; width:100%; height:45px; box-sizing:border-box;">
            <option value="1" ${d.is_active ? 'selected' : ''}>Active</option>
            <option value="0" ${!d.is_active ? 'selected' : ''}>Inactive (Disabled)</option>
          </select>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    confirmButtonColor: '#f59e0b',
    preConfirm: () => {
      const name = document.getElementById('edit-dept-name').value.trim();
      const isActive = document.getElementById('edit-dept-status').value === '1';
      if (!name) Swal.showValidationMessage('Department name is required');
      return { name, is_active: isActive };
    }
  }).then((res) => {
    if (res.isConfirmed) {
      const baseUrl = window.location.href.split('?')[0].replace(/\/$/, '');
      fetch(baseUrl, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
        },
        body: JSON.stringify({ 
          department_id: d.id, 
          name: res.value.name,
          is_active: res.value.is_active
        })
      }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) throw new Error(d.message || ('Server returned ' + r.status));
        return d;
      }).then(resp => {
        Swal.fire('Success', resp.message || 'Department updated successfully', 'success'); 
        setTimeout(() => location.reload(), 700); 
      }).catch(err => Swal.fire('Error', err.message || 'Failed to update department', 'error'));
    }
  });
}

function inUseDeptAlert(name, id, isActive) {
  if (isActive) {
    Swal.fire({
      title: 'Cannot Delete Department',
      text: `Department "${name}" is currently in use (it has assigned workers or attendance/salary records) and cannot be deleted. Would you like to Disable (Deactivate) this department instead?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Disable Department',
      confirmButtonColor: '#f59e0b',
      cancelButtonText: 'Cancel'
    }).then((res) => {
      if (res.isConfirmed) {
        toggleDeptStatus(id, name, false);
      }
    });
  } else {
    Swal.fire({
      title: 'Department In Use & Disabled',
      text: `Department "${name}" is already Disabled (Inactive). It cannot be deleted permanently because historical records or assigned workers exist for data integrity.`,
      icon: 'info',
      confirmButtonText: 'OK'
    });
  }
}

function toggleDeptStatus(id, name, targetActive) {
  const actionText = targetActive ? 'Enable (Activate)' : 'Disable (Deactivate)';
  Swal.fire({
    title: `${actionText} Department?`,
    text: targetActive 
      ? `Are you sure you want to activate department "${name}"?` 
      : `Are you sure you want to disable department "${name}"? It will not be available for new worker assignments.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: targetActive ? '#16a34a' : '#d33',
    confirmButtonText: targetActive ? 'Yes, Enable' : 'Yes, Disable'
  }).then((res) => {
    if (res.isConfirmed) {
      const baseUrl = window.location.href.split('?')[0].replace(/\/$/, '');
      fetch(`${baseUrl}/${id}/toggle-status`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
        }
      }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) throw new Error(d.message || ('Server error ' + r.status));
        return d;
      }).then(d => {
        Swal.fire('Success', d.message || 'Status updated', 'success');
        setTimeout(() => location.reload(), 700);
      }).catch(err => Swal.fire('Error', err.message || 'Failed to update department status', 'error'));
    }
  });
}

function closeDeptForm() {
  document.getElementById('dept-form-card').style.display = 'none';
}

document.getElementById('dept-form').onsubmit = function(e) {
  e.preventDefault();
  const baseUrl = window.location.href.split('?')[0].replace(/\/$/, '');
  fetch(baseUrl, {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
    },
    body: JSON.stringify({
      department_id: editingDeptId,
      name: document.getElementById('dept-name').value,
      is_active: document.getElementById('dept-status').value === '1'
    })
  }).then(async r => {
    const d = await r.json().catch(() => ({}));
    if (!r.ok || !d.success) throw new Error(d.message || ('Server returned ' + r.status));
    return d;
  }).then(d => {
    Swal.fire('Success', d.message || 'Department saved', 'success'); 
    setTimeout(() => location.reload(), 700); 
  }).catch(err => Swal.fire('Error', err.message || 'Failed to save department', 'error'));
};

function deleteDept(id) {
  Swal.fire({
    title: 'Delete Department?',
    text: 'Are you sure you want to permanently delete this department?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, Delete'
  }).then((res) => {
    if(res.isConfirmed) {
      const baseUrl = window.location.href.split('?')[0].replace(/\/$/, '');
      fetch(baseUrl + '/' + id, {
        method: 'DELETE',
        headers: { 
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
        }
      }).then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) {
          throw new Error(d.message || ('Server returned ' + r.status));
        }
        return d;
      }).then(d => {
        Swal.fire('Deleted!', d.message || '', 'success'); 
        setTimeout(() => location.reload(), 700); 
      }).catch(err => {
        Swal.fire({
          title: 'Cannot Delete Department',
          text: err.message || 'Department cannot be deleted because it is in use. Would you like to Disable it instead?',
          icon: 'error',
          showCancelButton: true,
          confirmButtonText: 'Disable Department Instead',
          confirmButtonColor: '#f59e0b',
          cancelButtonText: 'Cancel'
        }).then((promptRes) => {
          if (promptRes.isConfirmed) {
            toggleDeptStatus(id, '', false);
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
