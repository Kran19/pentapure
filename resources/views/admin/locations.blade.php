@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">📍 Warehouse / Storage Locations Master</h2>
    <button class="btn" onclick="document.getElementById('loc-form-card').style.display='block'; resetLocationForm();" style="width:auto; padding:0.6rem 1.2rem;">+ Add Location</button>
  </div>

  <!-- Add / Edit Form Card (Open by default) -->
  <div id="loc-form-card" class="card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="loc-card-title">Add / Edit Warehouse Location</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
      <div class="form-group">
        <label>Location Name *</label>
        <input type="text" id="loc-name" placeholder="e.g. Main Warehouse, Cold Storage A, Processing Bay">
      </div>
      <div class="form-group">
        <label>Description / Notes</label>
        <input type="text" id="loc-description" placeholder="e.g. Primary raw storage aisle 3">
      </div>
    </div>

    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
      <button class="btn" id="btn-save-loc" onclick="adminSaveLocation()" style="width:auto; padding:0.6rem 1.5rem;">Save Location</button>
      <button class="btn btn-secondary" onclick="document.getElementById('loc-form-card').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
  </div>

  <!-- Locations List Table -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div class="card-title" style="color:var(--primary-light);">🏢 All Storage Locations ({{ $locations->total() }})</div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Location / Warehouse Name</th>
            <th>Description</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($locations as $loc)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td style="font-weight:600; color:var(--dark-brand);">{{ $loc->name }}</td>
            <td style="color:var(--text-muted);">{{ $loc->description ?: '—' }}</td>
            <td>{{ date('d M Y, h:i A', strtotime($loc->created_at)) }}</td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="adminEditLocation({{ json_encode($loc) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon delete" onclick="adminDeleteLocation({{ $loc->id }}, {{ json_encode($loc->name) }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="margin-top:1rem; display:flex; justify-content:flex-end;">
      {{ $locations->links() }}
    </div>
  </div>
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
let editingLocationId = null;

function resetLocationForm() {
  editingLocationId = null;
  document.getElementById('loc-card-title').innerText = 'Add Warehouse Location';
  document.getElementById('loc-name').value = '';
  document.getElementById('loc-description').value = '';
}

function adminEditLocation(loc) {
  editingLocationId = loc.id;
  document.getElementById('loc-form-card').style.display = 'block';
  document.getElementById('loc-card-title').innerText = 'Edit Warehouse Location';
  document.getElementById('loc-name').value = loc.name || '';
  document.getElementById('loc-description').value = loc.description || '';
  document.getElementById('loc-form-card').scrollIntoView({ behavior: 'smooth' });
}

function adminSaveLocation() {
  const name = document.getElementById('loc-name').value.trim();
  const description = document.getElementById('loc-description').value.trim();

  if (!name) {
    Swal.fire('Error', 'Please enter a location name.', 'error');
    return;
  }

  const btn = document.getElementById('btn-save-loc');
  btn.disabled = true;
  btn.style.opacity = '0.7';

  const payload = { name, description };
  if (editingLocationId) payload.location_id = editingLocationId;

  fetch('/api/locations', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken 
    },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      Swal.fire('Success', d.message, 'success');
      setTimeout(() => location.reload(), 700);
    } else {
      Swal.fire('Error', d.message || 'Could not save location.', 'error');
      btn.disabled = false;
      btn.style.opacity = '1';
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.style.opacity = '1';
  });
}

function adminDeleteLocation(id, name) {
  Swal.fire({
    title: 'Delete Location?',
    text: `Are you sure you want to delete location "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (result.isConfirmed) {
      fetch(`/api/locations/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          Swal.fire('Deleted', d.message, 'success');
          setTimeout(() => location.reload(), 700);
        } else {
          Swal.fire('Error', d.message || 'Could not delete location.', 'error');
        }
      });
    }
  });
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  }[char]));
}
</script>
@endsection
