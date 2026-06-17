@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🏷️ Products Master</h2>
    <button class="btn" onclick="document.getElementById('prod-form').style.display='block'" style="width:auto; padding:0.6rem 1.2rem;">+ Add Product</button>
  </div>

  <!-- Add Form -->
  <div id="prod-form" class="card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title">Add / Edit Product</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
      <div class="form-group">
        <label>Name *</label>
        <input type="text" id="p-name" placeholder="Product name">
      </div>
      <div class="form-group">
        <label>Type *</label>
        <select id="p-type" onchange="toggleGradeDisplay()">
          <option value="RAW">RAW (Input Material)</option>
          <option value="FINISHED">FINISHED (Packaged / Intermediate Goods)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Unit</label>
        <input type="text" id="p-unit" value="kg" placeholder="kg, liters, pieces...">
      </div>
      <div class="form-group">
        <label>Rate (For Live Stock Ref)</label>
        <input type="number" id="p-rate" step="0.01" value="0.00" placeholder="e.g. 150.00">
      </div>
    </div>

    <div id="grade-selection-area" style="margin-top:1rem; border-top:1px solid var(--glass-border); padding-top:1rem;">
        <label style="font-size:0.9rem; color:var(--primary-light); margin-bottom:0.5rem; display:block;">Select Allowed Grades for this Product</label>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px;">
            @foreach($pageData['allGrades'] as $g)
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;">
                <input type="checkbox" name="p-grades" value="{{ $g->id }}" style="width:auto;"> {{ $g->name }}
            </label>
            @endforeach
        </div>
    </div>

    <div style="margin-top:1rem; border-top:1px solid var(--glass-border); padding-top:1rem;">
        <label style="font-size:0.9rem; color:var(--primary-light); margin-bottom:0.5rem; display:block;">Visible To / Allowed User Types</label>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:8px;">
            @foreach(['ADMIN', 'RAW', 'SEMI', 'FINISHED', 'SALES', 'DISPATCH', 'CASHIER', 'ATTENDANCE'] as $role)
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;">
                <input type="checkbox" name="p-roles" value="{{ $role }}" style="width:auto;"> {{ $role }}
            </label>
            @endforeach
        </div>
        <small style="color:var(--text-muted); font-size:0.75rem; margin-top:8px; display:block;">If none selected, it will be visible to all users by default.</small>
    </div>

    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
      <button class="btn" id="btn-save-prod" onclick="adminSaveProduct()" style="width:auto; padding:0.6rem 1.5rem;">Save Product</button>
      <button class="btn btn-secondary" onclick="document.getElementById('prod-form').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
    </div>
  </div>

  @php 
    $rawProds = $pageData['rawProducts'];
    $finishedProds = $pageData['finishedProducts'];
  @endphp

  <!-- RAW Products -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div class="card-title" style="color:var(--primary-light);">🌿 RAW Materials ({{ $rawProds->total() }})</div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Unit</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          @foreach($rawProds as $p)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td style="font-weight:600;">{{ $p['name'] }}</td>
            <td>{{ $p['unit'] }}</td>
            <td>
              <label class="switch">
                <input type="checkbox" {{ $p['is_active'] ? 'checked' : '' }} onchange="adminToggleProduct({{ $p['id'] }})">
                <span class="slider"></span>
              </label>
            </td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="adminEditProduct({{ json_encode($p) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon delete" onclick="adminDeleteProduct({{ $p['id'] }})" title="Delete">
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
      {{ $rawProds->appends(request()->except('raw_page'))->links() }}
    </div>
  </div>

  <!-- FINISHED Products -->
  <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div class="card-title" style="color:var(--secondary);">📦 FINISHED Products ({{ $finishedProds->total() }})</div>
    <div class="table-container">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Grades</th><th>Unit</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          @foreach($finishedProds as $p)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td style="font-weight:600;">{{ $p['name'] }}</td>
            <td style="white-space:normal; min-width:200px;">
                @foreach($p['gradeNames'] as $gn)
                    <span style="font-size:0.75rem; background:var(--bg-hover); color:var(--secondary); padding:4px 8px; border-radius:6px; margin:2px; display:inline-block; border:1px solid var(--secondary); font-weight:600;">{{ $gn }}</span>
                @endforeach
            </td>
            <td>{{ $p['unit'] }}</td>
            <td>
              <label class="switch">
                <input type="checkbox" {{ $p['is_active'] ? 'checked' : '' }} onchange="adminToggleProduct({{ $p['id'] }})">
                <span class="slider"></span>
              </label>
            </td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="adminEditProduct({{ json_encode($p) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon delete" onclick="adminDeleteProduct({{ $p['id'] }})" title="Delete">
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
      {{ $finishedProds->appends(request()->except('fin_page'))->links() }}
    </div>
  </div>





<script>
function toggleGradeDisplay() {
    const type = document.getElementById('p-type').value;
    const area = document.getElementById('grade-selection-area');
    area.style.display = (type === 'RAW') ? 'none' : 'block';
}

let editingProductId = null;

function adminEditProduct(prod) {
  editingProductId = prod.id;
  document.getElementById('prod-form').style.display = 'block';
  document.querySelector('#prod-form .card-title').innerText = 'Edit Product';
  document.getElementById('p-name').value = prod.name;
  document.getElementById('p-type').value = prod.type;
  document.getElementById('p-unit').value = prod.unit;
  document.getElementById('p-rate').value = prod.rate || '0.00';
  
  // Reset and set grades
  document.querySelectorAll('input[name="p-grades"]').forEach(cb => cb.checked = false);
  if (prod.gradeIds) {
    prod.gradeIds.forEach(id => {
      const cb = document.querySelector(`input[name="p-grades"][value="${id}"]`);
      if (cb) cb.checked = true;
    });
  }

  // Reset and set roles
  document.querySelectorAll('input[name="p-roles"]').forEach(cb => cb.checked = false);
  if (prod.allowed_roles) {
    prod.allowed_roles.forEach(role => {
      const cb = document.querySelector(`input[name="p-roles"][value="${role}"]`);
      if (cb) cb.checked = true;
    });
  }

  toggleGradeDisplay();
  document.getElementById('prod-form').scrollIntoView({ behavior: 'smooth' });
}

function adminSaveProduct() {
  const btn = document.getElementById('btn-save-prod');
  btn.disabled = true;
  btn.style.opacity = '0.7';

  const selectedGrades = Array.from(document.querySelectorAll('input[name="p-grades"]:checked')).map(cb => cb.value);
  const selectedRoles = Array.from(document.querySelectorAll('input[name="p-roles"]:checked')).map(cb => cb.value);
  
  const formData = new FormData();
  if (editingProductId) formData.append('product_id', editingProductId);
  formData.append('name', document.getElementById('p-name').value);
  formData.append('type', document.getElementById('p-type').value);
  formData.append('unit', document.getElementById('p-unit').value || 'kg');
  formData.append('rate', document.getElementById('p-rate').value || '0.00');
  formData.append('grades', JSON.stringify(selectedGrades));
  formData.append('allowed_roles', JSON.stringify(selectedRoles));
  
  fetch('/admin/products', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    body: formData
  }).then(r => r.json()).then(d => {
    if (d.success) { 
        Swal.fire('Success', d.message, 'success');
        setTimeout(() => location.reload(), 800); 
    } else {
        Swal.fire('Error', d.message || 'Error', 'error');
        btn.disabled = false;
        btn.style.opacity = '1';
    }
  }).catch(() => {
    btn.disabled = false;
    btn.style.opacity = '1';
  });
}

function adminToggleProduct(id) {
  fetch(`/admin/products/toggle/${id}`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  }).then(r => r.json()).then(d => {
    if (d.success) app.toast('Status updated');
    else { app.toast('Error updating status', 'error'); location.reload(); }
  });
}

function adminDeleteProduct(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`/admin/products/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
      }).then(r => r.json()).then(d => {
        if (d.success) { 
          Swal.fire('Deleted!', d.message, 'success');
          setTimeout(() => location.reload(), 800); 
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
        }
      });
    }
  });
}
toggleGradeDisplay();
</script>
@endsection
