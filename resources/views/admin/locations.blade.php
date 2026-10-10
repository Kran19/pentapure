@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">📍 Warehouse / Storage Locations Master</h2>
    @if(empty($isReadOnly))
    <button class="btn" onclick="openAddLocationForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add Location</button>
    @endif
  </div>

  <!-- Add / Edit Form Card -->
  @if(empty($isReadOnly))
  <div id="loc-form-card" class="card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="loc-card-title">Add Warehouse Location</div>
    <input type="hidden" id="loc-id" value="">
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
      <button class="btn btn-secondary" onclick="resetLocationForm(); document.getElementById('loc-form-card').style.display='none';" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
  </div>
  @endif

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
          @php
            $isFixed = in_array(strtoupper(trim($loc->name)), ['MAIN WAREHOUSE', 'DEFAULT', 'COLD STORAGE'], true);
            $usageCount = ($loc->stocks_count ?? 0) + ($loc->dispatch_locations_count ?? 0);
            $isInUse = $usageCount > 0;
          @endphp
          <tr>
            <td>{{ ($locations->currentPage() - 1) * $locations->perPage() + $loop->iteration }}</td>
            <td style="font-weight:600; color:var(--dark-brand);">
              {{ $loc->name }}
              @if($isFixed)
                <span style="font-size:0.7rem; background:#374151; color:#9ca3af; padding:2px 6px; border-radius:4px; margin-left:6px; font-weight:500;">System Fixed</span>
              @elseif($isInUse)
                <span style="font-size:0.7rem; background:rgba(59, 130, 246, 0.15); color:#60a5fa; border:1px solid rgba(59, 130, 246, 0.3); padding:2px 6px; border-radius:4px; margin-left:6px; font-weight:500;" title="Used in {{ $usageCount }} {{ \Illuminate\Support\Str::plural('record', $usageCount) }}">
                  {{ $usageCount }} {{ \Illuminate\Support\Str::plural('record', $usageCount) }}
                </span>
              @endif
            </td>
            <td style="color:var(--text-muted);">{{ $loc->description ?: '—' }}</td>
            <td>{{ date('d-m-Y, h:i A', strtotime($loc->created_at)) }}</td>
            <td>
              @if(!empty($isReadOnly))
                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">View Only</span>
              @else
              <div class="action-btns" style="display:flex; gap:6px; align-items:center;">
                <button type="button" class="btn-icon edit" 
                  data-id="{{ $loc->id }}" 
                  data-name="{{ $loc->name }}" 
                  data-description="{{ $loc->description ?? '' }}" 
                  onclick="adminEditLocationFromBtn(this)" 
                  title="Edit Location">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if(!$isFixed && !$isInUse)
                <button type="button" class="btn-icon delete" onclick="adminDeleteLocation({{ $loc->id }}, '{{ addslashes($loc->name) }}')" title="Delete Location" style="color:var(--danger, #ef4444); background:none; border:none; cursor:pointer; padding:4px; display:inline-flex; align-items:center;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
                @elseif($isFixed)
                <button type="button" class="btn-icon delete is-disabled" onclick="adminUsedLocationAlert('{{ addslashes($loc->name) }}', 'system')" style="opacity:0.35; cursor:not-allowed; background:none; border:none; padding:4px; display:inline-flex; align-items:center;" title="System fixed location cannot be deleted">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
                @else
                <button type="button" class="btn-icon delete is-disabled" onclick="adminUsedLocationAlert('{{ addslashes($loc->name) }}', {{ $usageCount }})" style="opacity:0.35; cursor:not-allowed; background:none; border:none; padding:4px; display:inline-flex; align-items:center;" title="Cannot delete: Location is currently in use across {{ $usageCount }} {{ \Illuminate\Support\Str::plural('record', $usageCount) }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
                @endif
              </div>
              @endif
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
let editingLocationId = null;

function getLocationsEndpoint() {
  return '{{ url(request()->path()) }}' || window.location.pathname.replace(/\/+$/, '');
}

function getCsrfToken() {
  return window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
}

function openAddLocationForm() {
  resetLocationForm();
  const card = document.getElementById('loc-form-card');
  if (card) {
    card.style.display = 'block';
    card.scrollIntoView({ behavior: 'smooth' });
  }
  const nameInput = document.getElementById('loc-name');
  if (nameInput) setTimeout(() => nameInput.focus(), 150);
}

function resetLocationForm() {
  editingLocationId = null;
  const idInput = document.getElementById('loc-id');
  if (idInput) idInput.value = '';
  const title = document.getElementById('loc-card-title');
  if (title) title.innerText = 'Add Warehouse Location';
  const name = document.getElementById('loc-name');
  if (name) name.value = '';
  const desc = document.getElementById('loc-description');
  if (desc) desc.value = '';
  const btn = document.getElementById('btn-save-loc');
  if (btn) btn.innerText = 'Save Location';
}

function adminEditLocationFromBtn(btn) {
  const id = btn.getAttribute('data-id');
  const name = btn.getAttribute('data-name') || '';
  const description = btn.getAttribute('data-description') || '';
  adminEditLocation({ id: parseInt(id, 10), name: name, description: description });
}

function adminEditLocation(loc) {
  if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }

  const id = (typeof loc === 'object') ? (loc.id || loc.location_id) : loc;
  const currentName = (typeof loc === 'object') ? (loc.name || '') : '';
  const currentDesc = (typeof loc === 'object') ? (loc.description || '') : '';
  const locId = parseInt(id, 10);

  // 1. Populate and show inline form card
  editingLocationId = locId;
  const idInput = document.getElementById('loc-id');
  if (idInput) idInput.value = locId;
  const title = document.getElementById('loc-card-title');
  if (title) title.innerText = `Edit Warehouse Location: "${currentName}"`;
  const nameInput = document.getElementById('loc-name');
  if (nameInput) nameInput.value = currentName;
  const descInput = document.getElementById('loc-description');
  if (descInput) descInput.value = currentDesc;
  const btn = document.getElementById('btn-save-loc');
  if (btn) btn.innerText = 'Update Location';

  const formCard = document.getElementById('loc-form-card');
  if (formCard) {
    formCard.style.display = 'block';
    formCard.scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => { if (nameInput) nameInput.focus(); }, 150);
  }

  // 2. Also open SweetAlert modal for quick in-place editing
  openEditLocationModal(locId, currentName, currentDesc);
}

function openEditLocationModal(locId, currentName, currentDesc) {
  Swal.fire({
    title: 'Edit Warehouse Location',
    html: `
      <div style="display:flex; flex-direction:column; gap:12px; text-align:left;">
        <div>
          <label style="display:block; font-weight:600; font-size:0.85rem; color:#4b5563; margin-bottom:5px;">Location Name *</label>
          <input type="text" id="edit-loc-name" class="swal2-input" style="width:100%; box-sizing:border-box; margin:0; font-size:0.95rem; height:42px; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827;" value="${escapeHtml(currentName)}">
        </div>
        <div>
          <label style="display:block; font-weight:600; font-size:0.85rem; color:#4b5563; margin-bottom:5px;">Description / Notes</label>
          <input type="text" id="edit-loc-desc" class="swal2-input" style="width:100%; box-sizing:border-box; margin:0; font-size:0.95rem; height:42px; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827;" value="${escapeHtml(currentDesc)}">
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#6b7280',
    showLoaderOnConfirm: true,
    didOpen: () => {
      const input = document.getElementById('edit-loc-name');
      if (input) { input.focus(); input.select(); }
    },
    preConfirm: () => {
      const name = document.getElementById('edit-loc-name').value.trim();
      const description = document.getElementById('edit-loc-desc').value.trim();
      if (!name) {
        Swal.showValidationMessage('Location name is required');
        return false;
      }

      const endpoint = getLocationsEndpoint();
      const token = getCsrfToken();

      return fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          location_id: locId,
          name: name,
          description: description
        })
      })
      .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
          const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Could not save location.');
          throw new Error(errMsg);
        }
        return data;
      })
      .catch(error => {
        Swal.showValidationMessage(error.message || 'Failed to update location.');
      });
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then((res) => {
    if (res.isConfirmed && res.value && res.value.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: res.value.message || 'Location updated successfully!',
        confirmButtonColor: '#f59e0b',
        timer: 1200
      }).then(() => {
        window.location.reload();
      });
    }
  });
}

function adminSaveLocation() {
  if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
  const name = document.getElementById('loc-name').value.trim();
  const description = document.getElementById('loc-description').value.trim();
  const rawId = editingLocationId || document.getElementById('loc-id')?.value;
  const locId = (rawId && !isNaN(parseInt(rawId, 10))) ? parseInt(rawId, 10) : null;

  if (!name) {
    Swal.fire('Error', 'Please enter a location name.', 'error');
    return;
  }

  const btn = document.getElementById('btn-save-loc');
  btn.disabled = true;
  btn.style.opacity = '0.7';

  const payload = { 
    name: name, 
    description: description 
  };
  if (locId) {
    payload.location_id = locId;
  }

  const endpoint = getLocationsEndpoint();
  const token = getCsrfToken();

  fetch(endpoint, {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(async r => {
    const d = await r.json().catch(() => ({}));
    if (!r.ok || !d.success) {
      const errMsg = d.message || (d.errors ? Object.values(d.errors).flat().join('\n') : 'Could not save location.');
      throw new Error(errMsg);
    }
    return d;
  })
  .then(d => {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: d.message || (locId ? 'Location updated successfully!' : 'Location added successfully!'),
      confirmButtonColor: '#f59e0b',
      timer: 1200
    }).then(() => {
      window.location.reload();
    });
  })
  .catch(err => {
    Swal.fire('Error', err.message || 'Could not save location.', 'error');
    btn.disabled = false;
    btn.style.opacity = '1';
  });
}

function adminUsedLocationAlert(name, typeOrCount) {
  if (typeOrCount === 'system') {
    Swal.fire({
      icon: 'info',
      title: 'Fixed System Location',
      text: `Location "${name}" is a protected system location and cannot be deleted.`,
      confirmButtonColor: '#3b82f6'
    });
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'Location In Use',
      html: `Warehouse location <strong>"${escapeHtml(name)}"</strong> is currently in use across <strong>${typeOrCount} record(s)</strong>.<br><br><span style="color:#ef4444; font-weight:600;">Locations in use cannot be deleted to protect inventory integrity.</span>`,
      confirmButtonColor: '#ef4444'
    });
  }
}

function adminDeleteLocation(id, name) {
  if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }

  const warningHtml = `Are you sure you want to delete warehouse location <strong>"${escapeHtml(name)}"</strong>?`;

  Swal.fire({
    title: 'Delete Location?',
    html: warningHtml,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Delete',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    showLoaderOnConfirm: true,
    preConfirm: () => {
      const endpoint = getLocationsEndpoint() + '/' + id;
      const token = getCsrfToken();

      return fetch(endpoint, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        }
      })
      .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok || !d.success) {
          throw new Error(d.message || 'Failed to delete location.');
        }
        return d;
      })
      .catch((err) => {
        Swal.showValidationMessage(err.message || 'An unexpected error occurred while deleting.');
      });
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then(result => {
    if (result.isConfirmed && result.value && result.value.success) {
      Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: result.value.message || 'Location deleted successfully.',
        timer: 1000,
        showConfirmButton: false
      }).then(() => {
        window.location.reload();
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

<style>
.btn-icon:disabled,
.btn-icon.is-disabled {
    opacity: 0.35 !important;
    cursor: not-allowed !important;
    background: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #6b7280 !important;
    pointer-events: auto !important;
}
.btn-icon:disabled:hover,
.btn-icon.is-disabled:hover {
    background: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #6b7280 !important;
}
</style>
@endsection
