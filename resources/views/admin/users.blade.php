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
  <div id="user-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
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
          <input type="text" id="u-country-code" value="+91" placeholder="+91" oninput="onUserCountryCodeInput()" style="width:75px; padding:0.6rem 0.4rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; text-align:center; flex-shrink:0;">
          <input type="text" id="u-phone" placeholder="10-digit mobile or landline" oninput="onUserPhoneInput(this)" maxlength="10" style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
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
      @php
    $permissionGroups = [
        'Admin Panel' => [
            ['key' => 'admin_dashboard', 'name' => 'Admin Dashboard', 'url' => '/admin/dashboard'],
            ['key' => 'admin_users', 'name' => 'Users & Hierarchy', 'url' => '/admin/users'],
            ['key' => 'admin_products', 'name' => 'Products Master', 'url' => '/admin/products'],
            ['key' => 'admin_stock', 'name' => 'Live Stock', 'url' => '/admin/stock'],
            ['key' => 'admin_po', 'name' => 'Purchase Requests', 'url' => '/admin/po'],
            ['key' => 'admin_logs', 'name' => 'Activity Logs', 'url' => '/admin/logs'],
            ['key' => 'admin_grades', 'name' => 'Grades Master', 'url' => '/admin/grades'],
            ['key' => 'admin_locations', 'name' => 'Storage Location', 'url' => '/admin/locations'],
            ['key' => 'admin_categories', 'name' => 'Expense Categories', 'url' => '/admin/categories'],
            ['key' => 'admin_dispatch_activity', 'name' => 'Dispatch Activity', 'url' => '/admin/dispatch-activity'],
            ['key' => 'admin_cashier_overview', 'name' => 'Cashier Overview', 'url' => '/admin/cashier-overview'],
            ['key' => 'admin_notifications', 'name' => 'Notifications', 'url' => '/admin/notifications'],
        ],
        'Cashier Panel' => [
            ['key' => 'cashier_action', 'name' => 'Cashier Action / Entry', 'url' => '/cashier2/action'],
            ['key' => 'cashier_history', 'name' => 'Cashier History', 'url' => '/cashier2/history'],
            ['key' => 'cashier_ledger', 'name' => 'Cashier Ledger', 'url' => '/cashier2/ledger'],
        ],
        'Sales Panel' => [
            ['key' => 'sales_home', 'name' => 'Sales Dashboard', 'url' => '/sales/home'],
            ['key' => 'sales_action', 'name' => 'Sales Action / Orders', 'url' => '/sales/action'],
            ['key' => 'sales_history', 'name' => 'Sales History', 'url' => '/sales/history'],
        ],
        'Dispatch Panel' => [
            ['key' => 'dispatch_home', 'name' => 'Dispatch Dashboard', 'url' => '/dispatch/home'],
            ['key' => 'dispatch_action', 'name' => 'Dispatch Action / Entry', 'url' => '/dispatch/action'],
            ['key' => 'dispatch_history', 'name' => 'Dispatch History', 'url' => '/dispatch/history'],
        ],
        'Stock Manager Panel' => [
            ['key' => 'stock_manager_home', 'name' => 'Stock Manager Home', 'url' => '/stock_manager/home'],
            ['key' => 'stock_manager_action', 'name' => 'Stock Outward Action', 'url' => '/stock_manager/action'],
            ['key' => 'stock_manager_stock', 'name' => 'Live Stock View', 'url' => '/stock_manager/stock'],
            ['key' => 'stock_manager_po', 'name' => 'Stock Purchase Orders', 'url' => '/stock_manager/po'],
            ['key' => 'stock_manager_history', 'name' => 'Stock Manager History', 'url' => '/stock_manager/history'],
        ],
        'Attendance & HR Panel' => [
            ['key' => 'attendance_dashboard', 'name' => 'Attendance Dashboard', 'url' => '/attendance/dashboard'],
            ['key' => 'attendance_departments', 'name' => 'Departments Master', 'url' => '/attendance/departments'],
            ['key' => 'attendance_workers', 'name' => 'Workers Master', 'url' => '/attendance/workers'],
            ['key' => 'attendance_daily', 'name' => 'Daily Attendance Entry', 'url' => '/attendance/daily'],
            ['key' => 'attendance_reports', 'name' => 'Reports & Payroll', 'url' => '/attendance/reports'],
        ],
    ];
    @endphp

    <div id="permissions-container" style="display:none; margin-top:1rem; border:1px solid #cbd5e1; padding:1.2rem; border-radius:10px; background:#f8fafc;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem; padding-bottom:0.8rem; border-bottom:1px solid #cbd5e1;">
        <div>
          <h4 style="margin:0; color:#1e293b; font-size:1.1rem; font-weight:700;">Sub-Admin / Stock Manager Page Permissions</h4>
          <div style="font-size:0.85rem; color:#64748b; margin-top:2px;">Configure View (Read) & Edit (Write) permissions for each page across all panels.</div>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <button type="button" onclick="toggleAllPerms('view', true)" style="padding:0.35rem 0.8rem; font-size:0.8rem; font-weight:600; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer;">Select All View</button>
          <button type="button" onclick="toggleAllPerms('edit', true)" style="padding:0.35rem 0.8rem; font-size:0.8rem; font-weight:600; background:#d97706; color:#fff; border:none; border-radius:6px; cursor:pointer;">Select All Edit</button>
          <button type="button" onclick="toggleAllPerms('all', false)" style="padding:0.35rem 0.8rem; font-size:0.8rem; font-weight:600; background:#64748b; color:#fff; border:none; border-radius:6px; cursor:pointer;">Clear All</button>
        </div>
      </div>

      @foreach($permissionGroups as $groupName => $modules)
        <div style="margin-bottom:1.2rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
          <div style="background:#f1f5f9; padding:0.6rem 1rem; font-weight:700; color:#334155; font-size:0.95rem; border-bottom:1px solid #e2e8f0;">
            {{ $groupName }}
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:0.6rem; padding:0.8rem;">
            @foreach($modules as $m)
              <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0.8rem; border:1px solid #f1f5f9; border-radius:6px; background:#ffffff;">
                <div>
                  <div style="font-weight:600; font-size:0.85rem; color:#1e293b;">{{ $m['name'] }}</div>
                  <div style="font-size:0.75rem; color:#94a3b8; font-family:monospace;">{{ $m['url'] }}</div>
                </div>
                <div style="display:flex; align-items:center; gap:0.6rem; flex-shrink:0;">
                  <label style="display:flex; align-items:center; gap:4px; margin:0; font-size:0.8rem; cursor:pointer; background:#eff6ff; color:#1e40af; padding:3px 8px; border-radius:4px; border:1px solid #bfdbfe; font-weight:600;">
                    <input type="checkbox" class="perm-view-cb" data-module="{{ $m['key'] }}" value="view_{{ $m['key'] }}" onchange="onPermViewToggle(this)" style="margin:0; width:14px; height:14px; cursor:pointer;">
                    View
                  </label>
                  <label style="display:flex; align-items:center; gap:4px; margin:0; font-size:0.8rem; cursor:pointer; background:#fef3c7; color:#92400e; padding:3px 8px; border-radius:4px; border:1px solid #fde68a; font-weight:600;">
                    <input type="checkbox" class="perm-edit-cb" data-module="{{ $m['key'] }}" value="edit_{{ $m['key'] }}" onchange="onPermEditToggle(this)" style="margin:0; width:14px; height:14px; cursor:pointer;">
                    Edit
                  </label>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
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
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 2 2h14a2 2 0 0 2 2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
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

function onPermViewToggle(viewCb) {
  const modKey = viewCb.dataset.module;
  const editCb = document.querySelector(`.perm-edit-cb[data-module="${modKey}"]`);
  if (!viewCb.checked && editCb) {
    editCb.checked = false;
  }
}

function onPermEditToggle(editCb) {
  const modKey = editCb.dataset.module;
  const viewCb = document.querySelector(`.perm-view-cb[data-module="${modKey}"]`);
  if (editCb.checked && viewCb) {
    viewCb.checked = true;
  }
}

function toggleAllPerms(type, check) {
  if (type === 'view') {
    document.querySelectorAll('.perm-view-cb').forEach(cb => {
      cb.checked = check;
      if (!check) {
        const modKey = cb.dataset.module;
        const editCb = document.querySelector(`.perm-edit-cb[data-module="${modKey}"]`);
        if (editCb) editCb.checked = false;
      }
    });
  } else if (type === 'edit') {
    document.querySelectorAll('.perm-edit-cb').forEach(cb => {
      cb.checked = check;
      if (check) {
        const modKey = cb.dataset.module;
        const viewCb = document.querySelector(`.perm-view-cb[data-module="${modKey}"]`);
        if (viewCb) viewCb.checked = true;
      }
    });
  } else if (type === 'all') {
    document.querySelectorAll('.perm-view-cb, .perm-edit-cb').forEach(cb => cb.checked = false);
  }
}

function onUserPhoneInput(el) {
  const code = (document.getElementById('u-country-code').value || '').trim();
  if (code === '+91') {
    el.value = el.value.replace(/\D/g, '').slice(0, 10);
  }
}

function onUserCountryCodeInput() {
  const codeEl = document.getElementById('u-country-code');
  const phoneEl = document.getElementById('u-phone');
  if (!codeEl || !phoneEl) return;
  if (codeEl.value.trim() === '+91') {
    phoneEl.setAttribute('maxlength', '10');
    onUserPhoneInput(phoneEl);
  } else {
    phoneEl.removeAttribute('maxlength');
  }
}

function resetUserForm() {
  editingUserId = null;
  document.querySelector('#user-form-card .card-title').innerText = 'Create New User';
  document.getElementById('u-name').value = '';
  document.getElementById('u-email').value = '';
  document.getElementById('u-country-code').value = '+91';
  document.getElementById('u-phone').value = '';
  onUserCountryCodeInput();
  document.getElementById('u-role').value = '';
  document.getElementById('u-branch').value = '';
  document.getElementById('u-password').value = '';
  document.getElementById('u-password').placeholder = 'Set password';
  
  document.querySelectorAll('.perm-view-cb, .perm-edit-cb').forEach(cb => cb.checked = false);
  document.querySelectorAll('.visible-cashier-cb').forEach(cb => {
      cb.checked = false;
      cb.parentElement.style.display = 'flex';
  });
  document.querySelectorAll('.attendance-dept-cb').forEach(cb => cb.checked = false);
  
  toggleRoleFields('');
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

function adminEditUser(user) {
  editingUserId = user.id;
  document.getElementById('user-form-card').style.display = 'block';
  document.querySelector('#user-form-card .card-title').innerText = 'Edit User';
  document.getElementById('u-name').value = user.name;
  document.getElementById('u-email').value = user.email || '';
  
  const rawUserPhone = (user.phone || '').trim();
  const phoneMatch = rawUserPhone.match(/^(\+\d{1,4})\s*(.*)$/);
  if (phoneMatch) {
    document.getElementById('u-country-code').value = phoneMatch[1];
    document.getElementById('u-phone').value = phoneMatch[2];
  } else {
    document.getElementById('u-country-code').value = '+91';
    document.getElementById('u-phone').value = rawUserPhone;
  }
  onUserCountryCodeInput();
  
  document.getElementById('u-role').value = user.role;
  document.getElementById('u-branch').value = user.branch || '';
  toggleRoleFields(user.role);
  
  // Set permissions if it's a SUB_ADMIN or STOCK_MANAGER or ATTENDANCE
  const perms = user.permissions || [];
  document.querySelectorAll('.perm-view-cb').forEach(cb => {
    const modKey = cb.dataset.module;
    cb.checked = perms.includes(cb.value) || perms.includes('view_' + modKey) || perms.includes('module_' + modKey) || perms.includes(modKey) || perms.includes('edit_' + modKey);
  });
  document.querySelectorAll('.perm-edit-cb').forEach(cb => {
    const modKey = cb.dataset.module;
    cb.checked = perms.includes(cb.value) || perms.includes('edit_' + modKey) || perms.includes('can_manage') || perms.includes('edit_module_' + modKey);
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
  document.querySelectorAll('.perm-view-cb:checked').forEach(cb => perms.push(cb.value));
  document.querySelectorAll('.perm-edit-cb:checked').forEach(cb => perms.push(cb.value));
  document.querySelectorAll('.attendance-dept-cb:checked').forEach(cb => perms.push(parseInt(cb.value)));

  const code = (document.getElementById('u-country-code').value || '').trim();
  const phone = (document.getElementById('u-phone').value || '').trim();
  const fullPhone = code ? (code + ' ' + phone) : phone;

  if (code === '+91') {
    const cleanDigits = phone.replace(/\D/g, '');
    const isLandline = /^0?79[\s\-]?[0-9]{6,8}$/.test(phone);
    if (!isLandline && cleanDigits.length !== 10) {
      Swal.fire('Invalid Phone', 'Phone number must be exactly 10 digits for +91', 'warning');
      return;
    }
  }

  const payload = {
    user_id: editingUserId,
    name: document.getElementById('u-name').value,
    email: document.getElementById('u-email').value,
    phone: fullPhone,
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
