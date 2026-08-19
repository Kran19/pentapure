@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🏷️ Products Master</h2>
    <div style="display:flex; gap:0.5rem;">
      <a href="{{ url(request()->segment(1) . '/products/pdf') }}" class="btn btn-secondary" style="width:auto; padding:0.6rem 1.2rem; border-color:#DDCFAF !important;">📄 Generate PDF Report</a>
      <button class="btn" onclick="document.getElementById('prod-form').style.display='block'" style="width:auto; padding:0.6rem 1.2rem;">+ Add Product</button>
    </div>
  </div>

  <!-- Add Form -->
  <div id="prod-form" class="card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title">Add Product</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
      <div class="form-group">
        <label>Name *</label>
        <input type="text" id="p-name" placeholder="Product name">
      </div>
      <div class="form-group">
        <label>Type *</label>
        <select id="p-type" onchange="toggleGradeDisplay()">
          <option value="RAW">RAW</option>
          <option value="SEMI">SEMI</option>
          <option value="FINISHED">FINISHED</option>
        </select>
      </div>
      <div class="form-group">
        <label>Unit *</label>
        <div style="display:flex; flex-direction:column; gap:4px;">
          <select id="p-unit-select" onchange="toggleCustomUnitInput()" style="width:100%;">
            <option value="KG" selected>KG</option>
            <option value="LTR">LTR</option>
            <option value="PCS">PCS</option>
            <option value="OTHER">OTHER (TYPE CUSTOM)</option>
          </select>
          <input type="text" id="p-unit-custom" placeholder="Type custom unit (e.g. BOTTLE, BOX)" style="display:none; width:100%; margin-top:4px;">
        </div>
      </div>
      <input type="hidden" id="p-threshold" value="0.00">
    </div>

    <div id="grade-selection-area" style="margin-top:1rem; border-top:1px solid var(--glass-border); padding-top:1rem;">
        <label style="font-size:0.9rem; color:var(--primary-light); margin-bottom:0.5rem; display:block;">Select Allowed Grades for this Product</label>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px;">
            @foreach($pageData['allGrades'] as $g)
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;">
                <input type="checkbox" name="p-grades" value="{{ $g->id }}" {{ $g->name === 'NONE' ? 'checked' : '' }} style="width:auto;"> {{ $g->name }}
            </label>
            @endforeach
        </div>
    </div>

    <div style="margin-top:1rem; border-top:1px solid var(--glass-border); padding-top:1rem;">
        <label style="font-size:0.9rem; color:var(--primary-light); margin-bottom:0.5rem; display:block;">Visible To / Allowed User Types</label>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:8px;">
            @foreach(['ADMIN', 'RAW', 'SEMI', 'FINISHED', 'SALES', 'DISPATCH'] as $role)
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;">
                <input type="checkbox" name="p-roles" value="{{ $role }}" checked style="width:auto;"> {{ $role }}
            </label>
            @endforeach
        </div>

    </div>

    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
      <button class="btn" id="btn-save-prod" onclick="adminSaveProduct()" style="width:auto; padding:0.6rem 1.5rem;">Save Product</button>
      <button class="btn btn-secondary" onclick="document.getElementById('prod-form').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
  </div>

  @php 
    $rawProds = $pageData['rawProducts'];
    $semiProds = $pageData['semiProducts'];
    $finishedProds = $pageData['finishedProducts'];
  @endphp

  <!-- RAW Products -->
  <div id="raw-section" class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <div class="card-title" style="color:var(--primary-light); margin:0;">🌿 RAW Materials ({{ $rawProds->count() }})</div>
    </div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Grades</th>
            <th>Unit</th><th>Active</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="raw-tbody">
          @foreach($rawProds as $p)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="prod-name" style="font-weight:400;">{{ $p['name'] }}</td>
            <td style="white-space:normal; min-width:200px;">
                @if(!empty($p['gradeNames']))
                  @foreach($p['gradeNames'] as $gn)
                      <span style="font-size:0.75rem; background:var(--bg-hover); color:var(--primary-light); padding:4px 8px; border-radius:6px; margin:2px; display:inline-block; border:1px solid var(--primary-light); font-weight:600;">{{ $gn }}</span>
                  @endforeach
                @endif
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
  </div>

  <!-- SEMI Products -->
  <div id="semi-section" class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <div class="card-title" style="color:var(--warning); margin:0;">⏳ SEMI Products ({{ $semiProds->count() }})</div>
    </div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Grades</th>
            <th>Unit</th><th>Active</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="semi-tbody">
          @foreach($semiProds as $p)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="prod-name" style="font-weight:400;">{{ $p['name'] }}</td>
            <td style="white-space:normal; min-width:200px;">
                @if(!empty($p['gradeNames']))
                  @foreach($p['gradeNames'] as $gn)
                      <span style="font-size:0.75rem; background:var(--bg-hover); color:var(--warning); padding:4px 8px; border-radius:6px; margin:2px; display:inline-block; border:1px solid var(--warning); font-weight:600;">{{ $gn }}</span>
                  @endforeach
                @endif
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
  </div>

  <!-- FINISHED Products -->
  <div id="finished-section" class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <div class="card-title" style="color:var(--secondary); margin:0;">📦 FINISHED Products ({{ $finishedProds->count() }})</div>
    </div>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Grades</th>
            <th>Unit</th><th>Active</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="fin-tbody">
          @foreach($finishedProds as $p)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="prod-name" style="font-weight:400;">{{ $p['name'] }}</td>
            <td style="white-space:normal; min-width:200px;">
                @if(!empty($p['gradeNames']))
                  @foreach($p['gradeNames'] as $gn)
                      <span style="font-size:0.75rem; background:var(--bg-hover); color:var(--secondary); padding:4px 8px; border-radius:6px; margin:2px; display:inline-block; border:1px solid var(--secondary); font-weight:600;">{{ $gn }}</span>
                  @endforeach
                @endif
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
  </div>





<script>
function filterTable(input, tbodyId) {
    const filter = input.value.toUpperCase();
    const tbody = document.getElementById(tbodyId);
    const trs = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < trs.length; i++) {
        const td = trs[i].querySelector('.prod-name');
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                trs[i].style.display = "";
            } else {
                trs[i].style.display = "none";
            }
        }
    }
}

function toggleGradeDisplay() {
    const type = document.getElementById('p-type').value;
    const area = document.getElementById('grade-selection-area');
    area.style.display = (type === 'RAW') ? 'none' : 'block';
}

function toggleCustomUnitInput() {
  const sel = document.getElementById('p-unit-select').value;
  const customInput = document.getElementById('p-unit-custom');
  if (sel === 'OTHER') {
    customInput.style.display = 'block';
  } else {
    customInput.style.display = 'none';
  }
}

function getSelectedUnitValue() {
  const sel = document.getElementById('p-unit-select').value;
  if (sel === 'OTHER') {
    const custom = document.getElementById('p-unit-custom').value.trim();
    return custom ? custom.toUpperCase() : 'KG';
  }
  return sel;
}

const allGrades = @json($pageData['allGrades']);

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[char]));
}

let editingProductId = null;

function adminEditProduct(prod) {
  editingProductId = prod.id;
  
  // Build the grades HTML
  let gradesHtml = '';
  const prodGradeIds = prod.gradeIds || [];
  allGrades.forEach(g => {
    const isChecked = prodGradeIds.includes(g.id) ? 'checked' : '';
    gradesHtml += `<label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;"><input type="checkbox" id="swal-grade-${g.id}" value="${g.id}" ${isChecked} style="width:auto;"> ${escapeHtml(g.name)}</label>`;
  });

  // Build the roles HTML
  let rolesHtml = '';
  const allRoles = ['ADMIN', 'RAW', 'SEMI', 'FINISHED', 'SALES', 'DISPATCH'];
  // Default to all roles if none are explicitly set
  const prodRoles = (prod.allowed_roles && prod.allowed_roles.length > 0) ? prod.allowed_roles : allRoles;
  allRoles.forEach(r => {
    const isChecked = prodRoles.includes(r) ? 'checked' : '';
    rolesHtml += `<label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; background:var(--bg-hover); border:1px solid var(--border-soft); padding:6px 10px; border-radius:6px; cursor:pointer;"><input type="checkbox" id="swal-role-${r}" value="${r}" ${isChecked} style="width:auto;"> ${r}</label>`;
  });

  // Build the Unit logic
  const unitUpper = (prod.unit || 'KG').toUpperCase();
  const isCustomUnit = !['KG', 'LTR', 'PCS'].includes(unitUpper);

  Swal.fire({
    title: 'Edit Product',
    width: '600px',
    html: `
      <div style="text-align:left; display:flex; flex-direction:column; gap:1rem;">
        <div class="form-group">
          <label style="font-size:0.85rem; font-weight:600; color:#6b7280; display:block; margin-bottom:4px;">Name *</label>
          <input type="text" id="swal-p-name" value="${escapeHtml(prod.name)}" style="width:100%; padding:0.65rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#333;">
        </div>

        <div style="display:flex; gap:1rem;">
          <div class="form-group" style="flex:1;">
            <label style="font-size:0.85rem; font-weight:600; color:#6b7280; display:block; margin-bottom:4px;">Type *</label>
            <select id="swal-p-type" style="width:100%; padding:0.65rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#333;" onchange="document.getElementById('swal-grades-area').style.display = this.value === 'RAW' ? 'none' : 'block'">
              <option value="RAW" ${prod.type === 'RAW' ? 'selected' : ''}>RAW</option>
              <option value="SEMI" ${prod.type === 'SEMI' ? 'selected' : ''}>SEMI</option>
              <option value="FINISHED" ${prod.type === 'FINISHED' ? 'selected' : ''}>FINISHED</option>
            </select>
          </div>
          
          <input type="hidden" id="swal-p-threshold" value="${prod.threshold || '0.00'}">
        </div>

        <div class="form-group">
          <label style="font-size:0.85rem; font-weight:600; color:#6b7280; display:block; margin-bottom:4px;">Unit *</label>
          <select id="swal-p-unit-select" style="width:100%; padding:0.65rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#333;" onchange="document.getElementById('swal-p-unit-custom').style.display = this.value === 'OTHER' ? 'block' : 'none'">
            <option value="KG" ${unitUpper === 'KG' ? 'selected' : ''}>KG</option>
            <option value="LTR" ${unitUpper === 'LTR' ? 'selected' : ''}>LTR</option>
            <option value="PCS" ${unitUpper === 'PCS' ? 'selected' : ''}>PCS</option>
            <option value="OTHER" ${isCustomUnit ? 'selected' : ''}>OTHER (TYPE CUSTOM)</option>
          </select>
          <input type="text" id="swal-p-unit-custom" value="${isCustomUnit ? unitUpper : ''}" placeholder="Type custom unit (e.g. BOTTLE)" style="display:${isCustomUnit ? 'block' : 'none'}; width:100%; padding:0.65rem; margin-top:8px; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#333;">
        </div>

        <div id="swal-grades-area" style="display:${prod.type === 'RAW' ? 'none' : 'block'}; margin-top:0.5rem;">
          <label style="font-size:0.85rem; font-weight:600; color:#6b7280; display:block; margin-bottom:8px;">Select Allowed Grades</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px;">
            ${gradesHtml}
          </div>
        </div>

        <div style="margin-top:0.5rem;">
          <label style="font-size:0.85rem; font-weight:600; color:#6b7280; display:block; margin-bottom:8px;">Visible To / Allowed User Types</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(100px, 1fr)); gap:8px;">
            ${rolesHtml}
          </div>
        </div>
      </div>
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'Update Product',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    preConfirm: () => {
      const name = document.getElementById('swal-p-name').value;
      if(!name) {
        Swal.showValidationMessage('Name is required');
        return false;
      }
      
      const type = document.getElementById('swal-p-type').value;
      const threshold = document.getElementById('swal-p-threshold').value;
      
      const selUnit = document.getElementById('swal-p-unit-select').value;
      let unit = selUnit;
      if(selUnit === 'OTHER') {
        unit = document.getElementById('swal-p-unit-custom').value.trim();
      }
      
      const selectedGrades = [];
      allGrades.forEach(g => {
        if(document.getElementById(`swal-grade-${g.id}`) && document.getElementById(`swal-grade-${g.id}`).checked) selectedGrades.push(g.id);
      });
      
      const selectedRoles = [];
      allRoles.forEach(r => {
        if(document.getElementById(`swal-role-${r}`) && document.getElementById(`swal-role-${r}`).checked) selectedRoles.push(r);
      });

      return { name, type, unit, threshold, grades: selectedGrades, roles: selectedRoles };
    }
  }).then(result => {
    if(result.isConfirmed) {
      const formData = new FormData();
      formData.append('product_id', prod.id);
      formData.append('name', result.value.name);
      formData.append('type', result.value.type);
      formData.append('unit', result.value.unit);
      formData.append('rate', prod.rate || '0.00'); // preserve existing rate or default
      formData.append('threshold', result.value.threshold || '0.00');
      formData.append('grades', JSON.stringify(result.value.grades));
      formData.append('allowed_roles', JSON.stringify(result.value.roles));

      fetch(window.baseUrl + '/' + window.userSlug + '/products', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire({ icon:'success', title:'Updated!', text:data.message, background: '#ffffff', color: '#333333', confirmButtonColor:'#f59e0b', timer:1500, showConfirmButton:false})
          .then(() => location.reload());
        } else {
          Swal.fire({ icon:'error', title:'Error', text:data.message || data.error || 'Failed to save', background: '#ffffff', color: '#333333', confirmButtonColor:'#f59e0b'});
        }
      })
      .catch(err => {
        console.error(err);
        Swal.fire({ icon:'error', title:'Error', text:'Network error', background: '#ffffff', color: '#333333', confirmButtonColor:'#f59e0b'});
      });
    }
  });
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
  formData.append('unit', getSelectedUnitValue());
  formData.append('rate', '0.00');
  formData.append('threshold', document.getElementById('p-threshold').value || '0.00');
  formData.append('grades', JSON.stringify(selectedGrades));
  formData.append('allowed_roles', JSON.stringify(selectedRoles));
  
  fetch(window.baseUrl + '/' + window.userSlug + '/products', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    body: formData
  }).then(r => r.json()).then(d => {
    if (d.success) { 
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: d.message,
          timer: 1000,
          showConfirmButton: false,
          background: '#ffffff',
          color: '#333333'
        }).then(() => {
            sessionStorage.setItem('keepProdFormOpen', 'true');
            location.reload();
        }); 
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
  fetch(window.baseUrl + '/' + window.userSlug + '/products/toggle/' + id, {
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
      fetch(window.baseUrl + '/' + window.userSlug + '/products/' + id, {
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

document.addEventListener('DOMContentLoaded', function() {
  if (sessionStorage.getItem('keepProdFormOpen') === 'true') {
    document.getElementById('prod-form').style.display = 'block';
    sessionStorage.removeItem('keepProdFormOpen');
  }
});
</script>
@endsection
