@extends('layouts.admin')

@section('content')
<style>
.user-status-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  margin: 0;
  cursor: pointer;
  vertical-align: middle;
}
.user-status-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.user-status-slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #cbd5e1;
  transition: 0.3s;
  border-radius: 24px;
  border: 1px solid #94a3b8;
}
.user-status-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 2px;
  bottom: 2px;
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.25);
  transition: 0.3s;
  border-radius: 50%;
}
.user-status-switch input:checked + .user-status-slider {
  background-color: #f59e0b;
  border-color: #d97706;
}
.user-status-switch input:checked + .user-status-slider:before {
  transform: translateX(20px);
}
</style>
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">👥 Users & Hierarchy</h2>
    <button class="btn" onclick="resetUserForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add User</button>
  </div>

  <!-- Add/Edit Form -->
  <div id="user-form-card" class="card white-orange-card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title">Create New User</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" id="u-name" placeholder="User name">
      </div>
      <div class="form-group">
        <label>Email (Optional)</label>
        <input type="email" id="u-email" placeholder="email@pentapure.com">
      </div>
      <div class="form-group">
        <label>Phone Number *</label>
        <div style="display:flex; gap:8px;">
          <select id="u-country-code" onchange="onAdminPhoneCodeChange()" style="width:72px; padding:0.6rem 0.2rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; flex-shrink:0; text-align:center; cursor:pointer;">
            <option value="+91" selected>+91</option>
            <option value="+1">+1</option>
            <option value="+44">+44</option>
            <option value="+971">+971</option>
            <option value="+966">+966</option>
            <option value="+61">+61</option>
            <option value="+65">+65</option>
            <option value="+49">+49</option>
            <option value="+33">+33</option>
            <option value="+86">+86</option>
            <option value="+81">+81</option>
            <option value="other">+...</option>
          </select>
          <input type="text" id="u-phone" oninput="onAdminPhoneInput(this)" maxlength="10" placeholder="10-digit mobile or 079 landline" style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
        </div>
      </div>
      <div class="form-group">
        <label>Role *</label>
        <select id="u-role" onchange="toggleRoleFields(this.value)">
          <option value="">-- Select Role --</option>
          @foreach(['RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','ADMIN','SUB_ADMIN','STOCK_MANAGER'] as $r)
            <option value="{{ $r }}">{{ $r }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" id="branch-field-container" style="display:none;">
        <label>Assigned Branch (Cashier Only)</label>
        <input type="text" id="u-branch" placeholder="e.g. Main Factory">
      </div>
    </div>

    <div id="visible-cashiers-container" style="display:none; margin-top:1rem; border:1px solid var(--glass-border); padding:1.2rem; border-radius:8px; background:var(--glass-bg);">
      <h4 style="margin-top:0; margin-bottom:1rem; color:var(--secondary); font-size:1.1rem; text-transform:none;">Team Ledger Visibility (Cashier Only)</h4>
      <div style="margin-bottom:1rem; font-size:0.9rem; color:var(--text-muted);">
        Select which other cashiers this user is allowed to see in their Team Ledger.
      </div>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
        @foreach($pageData['cashiers'] as $c)
          <div style="display:flex; align-items:center; gap:8px;" class="visible-cashier-wrapper">
            <input type="checkbox" class="visible-cashier-cb" value="{{ $c['id'] }}" style="width:16px;height:16px;margin:0;">
            <span style="font-size:0.9rem; text-transform:none;">{{ $c['name'] }}</span>
          </div>
        @endforeach
      </div>
    </div>
    
    <div id="attendance-permissions-container" style="display:none; margin-top:1rem; border:1px solid var(--glass-border); padding:1.2rem; border-radius:8px; background:var(--glass-bg);">
      <h4 style="margin-top:0; margin-bottom:1rem; color:var(--primary); font-size:1.1rem; text-transform:none;">Assigned Departments (Attendance Only)</h4>
      <div style="margin-bottom:1rem; font-size:0.9rem; color:var(--text-muted);">
        Select which departments this user is allowed to manage attendance for.
      </div>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
        @foreach($pageData['departments'] as $d)
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" class="attendance-dept-cb" value="{{ $d->id }}" style="width:16px;height:16px;margin:0;">
            <span style="font-size:0.9rem; text-transform:none;">{{ $d->name }}</span>
          </div>
        @endforeach
      </div>
    </div>
    
    <div id="permissions-container" style="display:none; margin-top:1rem; border:1px solid var(--glass-border); padding:1.2rem; border-radius:8px; background:var(--glass-bg);">
      <h4 style="margin-top:0; margin-bottom:1rem; color:var(--primary); font-size:1.1rem; text-transform:none;">Sub-Admin / Stock Manager Permissions</h4>
      <div style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--glass-border);">
        <div style="display:flex; align-items:center; gap:10px;">
          <input type="checkbox" id="perm-can_manage" value="can_manage" style="width:20px; height:20px; margin:0; cursor:pointer;">
          <span style="font-weight:bold; color:var(--danger); font-size:0.95rem; text-transform:none;">
            Allow to Manage/Edit Data (If unchecked, user can only VIEW allowed panels)
          </span>
        </div>
      </div>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_dashboard" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Dashboard</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_users" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Users & Hierarchy</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_products" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Products Master</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_stock" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Live Stock</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_po" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Purchase Requests</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_logs" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Activity Logs</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_grades" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Grades Master</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_locations" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Storage Location</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_categories" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Expense Category Master</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_dispatch" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Dispatch Activity</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_cashier" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Cashier Overview</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_notifications" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Notifications</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_attendance" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Attendance & HR</span></div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
      <div class="form-group">
        <label>Password *</label>
        <div class="password-wrapper">
          <input type="password" id="u-password" placeholder="Set password" style="padding-right:2.5rem;">
          <button type="button" class="password-toggle" onclick="app.togglePassword('u-password')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
        </div>
      </div>
    </div>
    <div style="display:flex; gap:1rem; margin-top:1rem;">
      <button class="btn" onclick="adminSaveUser()" style="width:auto; padding:0.6rem 1.5rem;">Save User</button>
      <button class="btn btn-secondary" onclick="document.getElementById('user-form-card').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
  </div>

  <!-- Users Table -->
  <div class="card" style="padding:1.2rem;">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Contact Info</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['users'] as $user)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td style="font-weight:600;">{{ $user['name'] }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted);">
                <div>{{ $user['phone'] }}</div>
                @if($user['email'])<div style="font-size:0.75rem;">{{ $user['email'] }}</div>@endif
            </td>
            <td><span class="badge badge-info">{{ $user['role'] }}</span></td>
            <td>
              @if($user['id'] == auth()->id())
                <span class="badge" style="background:var(--primary, #f59e0b); color:#fff; padding:4px 10px; font-weight:700; border-radius:12px; font-size:0.75rem;">YOU</span>
              @else
                <label class="user-status-switch" title="Toggle Active / Blocked">
                  <input type="checkbox" id="status-toggle-{{ $user['id'] }}" {{ $user['status'] === 'ACTIVE' ? 'checked' : '' }} 
                    onchange="adminToggleUser({{ $user['id'] }})">
                  <span class="user-status-slider"></span>
                </label>
              @endif
            </td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="adminEditUser({{ json_encode($user) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if($user['id'] != auth()->id())
                  <button class="btn-icon notify" onclick="openNotifyModal({{ $user['id'] }}, '{{ addslashes($user['name']) }}')" title="Notify User">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                  </button>
                  <button class="btn-icon delete" onclick="adminDeleteUser({{ $user['id'] }})" title="Delete">
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

    <!-- Pagination Links -->
    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['users']->links() }}
    </div>
  </div>
</div>

<script>
let editingUserId = null;

function resetUserForm() {
  editingUserId = null;
  document.querySelector('#user-form-card .card-title').innerText = 'Create New User';
  document.getElementById('u-name').value = '';
  document.getElementById('u-email').value = '';
  document.getElementById('u-phone').value = ''; if(document.getElementById('u-country-code')) document.getElementById('u-country-code').value = '+91';
  document.getElementById('u-role').value = '';
  document.getElementById('u-branch').value = '';
  document.getElementById('u-password').value = '';
  document.getElementById('u-password').placeholder = 'Set password';
  
  if(document.getElementById('perm-can_manage')) document.getElementById('perm-can_manage').checked = false;
  document.querySelectorAll('.sub-perm').forEach(cb => cb.checked = false);
  document.querySelectorAll('.visible-cashier-cb').forEach(cb => {
      cb.checked = false;
      cb.parentElement.style.display = 'flex';
  });
  document.querySelectorAll('.attendance-dept-cb').forEach(cb => cb.checked = false);
  
  toggleRoleFields('');
  onAdminPhoneCodeChange();
  document.getElementById('user-form-card').style.display = 'block';
  document.getElementById('user-form-card').scrollIntoView({ behavior: 'smooth' });
}

function toggleRoleFields(role) {
  const branchContainer = document.getElementById('branch-field-container');
  const permContainer = document.getElementById('permissions-container');
  const visibilityContainer = document.getElementById('visible-cashiers-container');
  const attPermContainer = document.getElementById('attendance-permissions-container');
  
  if (role === 'CASHIER') {
    branchContainer.style.display = 'block';
    if(visibilityContainer) visibilityContainer.style.display = 'block';
  } else {
    branchContainer.style.display = 'none';
    if(visibilityContainer) {
        visibilityContainer.style.display = 'none';
        document.querySelectorAll('.visible-cashier-cb').forEach(cb => cb.checked = false);
    }
    document.getElementById('u-branch').value = '';
  }
  
  if (role === 'SUB_ADMIN' || role === 'STOCK_MANAGER') {
    permContainer.style.display = 'block';
  } else {
    permContainer.style.display = 'none';
  }

  if (role === 'ATTENDANCE') {
    if(attPermContainer) attPermContainer.style.display = 'block';
  } else {
    if(attPermContainer) {
        attPermContainer.style.display = 'none';
        document.querySelectorAll('.attendance-dept-cb').forEach(cb => cb.checked = false);
    }
  }
}

function onAdminPhoneInput(inputEl) {
  const codeEl = document.getElementById('u-country-code');
  if (codeEl && codeEl.value === '+91') {
    inputEl.value = inputEl.value.replace(/\D/g, '').slice(0, 10);
  }
}

function onAdminPhoneCodeChange() {
  const codeEl = document.getElementById('u-country-code');
  const inputEl = document.getElementById('u-phone');
  if (!codeEl || !inputEl) return;
  if (codeEl.value === '+91') {
    inputEl.placeholder = '10-digit mobile or 079 landline';
    inputEl.setAttribute('maxlength', '10');
    onAdminPhoneInput(inputEl);
  } else if (codeEl.value === 'other') {
    inputEl.placeholder = 'e.g. +44 123456789 or 079 landline';
    inputEl.removeAttribute('maxlength');
  } else {
    inputEl.placeholder = 'Phone number without ' + codeEl.value;
    inputEl.removeAttribute('maxlength');
  }
}

function adminEditUser(user) {
  editingUserId = user.id;
  document.getElementById('user-form-card').style.display = 'block';
  document.querySelector('#user-form-card .card-title').innerText = 'Edit User';
  document.getElementById('u-name').value = user.name;
  document.getElementById('u-email').value = user.email || '';
  
  const userPhone = (user.phone || '').trim();
  const match = userPhone.match(/^(\+\d{1,4})\s*(.*)$/);
  const codeEl = document.getElementById('u-country-code');
  if (match && codeEl) {
    const hasOption = Array.from(codeEl.options).some(op => op.value === match[1]);
    if (hasOption) {
      codeEl.value = match[1];
      document.getElementById('u-phone').value = match[2];
    } else {
      codeEl.value = 'other';
      document.getElementById('u-phone').value = userPhone;
    }
  } else {
    if (codeEl) codeEl.value = '+91';
    document.getElementById('u-phone').value = userPhone;
  }
  onAdminPhoneCodeChange();
  
  document.getElementById('u-role').value = user.role;
  document.getElementById('u-branch').value = user.branch || '';
  toggleRoleFields(user.role);
  
  // Set permissions if it's a SUB_ADMIN or STOCK_MANAGER or ATTENDANCE
  const perms = user.permissions || [];
  document.getElementById('perm-can_manage').checked = perms.includes('can_manage');
  document.querySelectorAll('.sub-perm').forEach(cb => {
      cb.checked = perms.includes(cb.value);
  });
  
  document.querySelectorAll('.attendance-dept-cb').forEach(cb => {
      cb.checked = perms.includes(parseInt(cb.value)) || perms.includes(cb.value.toString());
  });

  // Set visible cashiers if it's a CASHIER
  const visCashiers = user.visible_cashiers || [];
  document.querySelectorAll('.visible-cashier-cb').forEach(cb => {
      if (cb.value == user.id) {
          cb.parentElement.style.display = 'none'; // hide themselves
      } else {
          cb.parentElement.style.display = 'flex';
      }
      cb.checked = visCashiers.includes(parseInt(cb.value)) || visCashiers.includes(cb.value.toString());
  });
  
  document.getElementById('u-password').value = ''; 
  document.getElementById('u-password').placeholder = '(Leave blank to keep current)';
  document.getElementById('user-form-card').scrollIntoView({ behavior: 'smooth' });
}

function adminSaveUser() {
  const perms = [];
  if (document.getElementById('perm-can_manage').checked) perms.push('can_manage');
  document.querySelectorAll('.sub-perm:checked').forEach(cb => perms.push(cb.value));
  document.querySelectorAll('.attendance-dept-cb:checked').forEach(cb => perms.push(parseInt(cb.value)));

  const rawPhone = (document.getElementById('u-phone').value || '').trim();
  const code = document.getElementById('u-country-code').value;
  let formattedPhone = rawPhone;
  if (rawPhone && !rawPhone.startsWith('+') && !/^0?79[\s\-]?[0-9]{6,8}$/.test(rawPhone)) {
    if (code !== 'other') formattedPhone = code + ' ' + rawPhone;
  }

  if (code === '+91' || formattedPhone.startsWith('+91')) {
    let phoneDigits = rawPhone;
    if (phoneDigits.startsWith('+91')) {
      phoneDigits = phoneDigits.substring(3);
    }
    const cleanDigits = phoneDigits.replace(/\D/g, '');
    const isLandline = /^0?79[\s\-]?[0-9]{6,8}$/.test(rawPhone);
    if (!isLandline && cleanDigits.length !== 10) {
      Swal.fire('Invalid Phone', 'Phone number must be exactly 10 digits for India (+91) or 079 landline', 'warning');
      return;
    }
  }

  const payload = {
    user_id: editingUserId,
    name: document.getElementById('u-name').value,
    email: document.getElementById('u-email').value,
    phone: formattedPhone,
    role: document.getElementById('u-role').value,
    branch: document.getElementById('u-branch').value,
    password: document.getElementById('u-password').value,
    permissions: perms,
    visible_cashiers: Array.from(document.querySelectorAll('.visible-cashier-cb:checked')).map(cb => parseInt(cb.value))
  };
  
  if (!payload.name || !payload.role || !payload.phone) {
    Swal.fire('Required', 'Name, Phone and Role are required', 'warning'); return;
  }
  if (!editingUserId && !payload.password) {
    Swal.fire('Required', 'Password is required for new users', 'warning'); return;
  }

  fetch(window.baseUrl + '/' + window.userSlug + '/users', {
    method: 'POST',
    headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken 
    },
    body: JSON.stringify(payload)
  }).then(async r => {
    if (!r.ok && r.status !== 422) throw new Error('Server error: ' + r.status);
    return r.json();
  }).then(d => {
    if (d.success || (d.id && !d.errors)) { 
      Swal.fire('Success', d.message || 'Saved successfully', 'success');
      setTimeout(() => location.reload(), 800); 
    } else {
      let errorMsg = d.message || 'Error';
      if (d.errors) {
        errorMsg = Object.values(d.errors).flat().join('<br>');
      }
      Swal.fire({title: 'Error', html: errorMsg, icon: 'error'});
    }
  }).catch(e => {
    console.error('Save User Error:', e);
    Swal.fire('Error', e.message || 'A server error occurred while saving. Please try again.', 'error');
  });
}

function adminToggleUser(id) {
  const toggleEl = document.getElementById('status-toggle-' + id);

  fetch(window.baseUrl + '/' + window.userSlug + '/users/toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
    body: JSON.stringify({ user_id: id })
  }).then(r => r.json()).then(d => {
    if (d.success) {
      app.toast(d.message);
    } else {
      app.toast(d.message || 'Error', 'error');
      location.reload();
    }
  }).catch(err => {
    app.toast('Network error', 'error');
    if (toggleEl) toggleEl.checked = !toggleEl.checked;
  });
}

function adminDeleteUser(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete user!'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`/admin/users/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': window.csrfToken }
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

function openNotifyModal(userId, userName) {
    Swal.fire({
        title: `Send Notification to ${userName}`,
        html: `
            <div style="text-align:left;">
                <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Title</label>
                <input id="swal-notify-title" class="swal2-input" style="margin:0 0 15px 0; width:100%;" placeholder="Enter title">
                
                <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Message</label>
                <textarea id="swal-notify-message" class="swal2-textarea" style="margin:0 0 15px 0; width:100%; height:100px;" placeholder="Enter message"></textarea>
                
                <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Type</label>
                <select id="swal-notify-type" class="swal2-select" style="margin:0; width:100%;">
                    <option value="info">Info (Blue)</option>
                    <option value="warning">Warning (Yellow)</option>
                    <option value="success">Success (Green)</option>
                    <option value="danger">Danger (Red)</option>
                </select>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Send Notification',
        preConfirm: () => {
            const title = document.getElementById('swal-notify-title').value;
            const message = document.getElementById('swal-notify-message').value;
            const type = document.getElementById('swal-notify-type').value;
            if (!title || !message) {
                Swal.showValidationMessage('Title and message are required');
                return false;
            }
            return { title, message, type };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.baseUrl + '/' + window.userSlug + '/notifications/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({
                    user_id: userId,
                    title: result.value.title,
                    message: result.value.message,
                    type: result.value.type
                })
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    Swal.fire('Sent!', d.message, 'success');
                } else {
                    Swal.fire('Error', d.message || 'Error', 'error');
                }
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
