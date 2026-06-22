@extends('layouts.admin')

@section('content')
<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">👥 Users & Hierarchy</h2>
    <button class="btn" onclick="document.getElementById('user-form-card').style.display='block'" style="width:auto; padding:0.6rem 1.2rem;">+ Add User</button>
  </div>

  <!-- Add/Edit Form -->
  <div id="user-form-card" class="card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title">Create New User</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" id="u-name" placeholder="User name">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" id="u-email" placeholder="email@pentapure.com">
      </div>
      <div class="form-group">
        <label>Role *</label>
        <select id="u-role" onchange="toggleRoleFields(this.value)">
          <option value="">-- Select Role --</option>
          @foreach(['RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','ADMIN','SUB_ADMIN'] as $r)
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
    
    <div id="permissions-container" style="display:none; margin-top:1rem; border:1px solid var(--glass-border); padding:1.2rem; border-radius:8px; background:var(--glass-bg);">
      <h4 style="margin-top:0; margin-bottom:1rem; color:var(--primary); font-size:1.1rem; text-transform:none;">Sub-Admin Permissions</h4>
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
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_po" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Purchase Orders</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_logs" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Activity Logs</span></div>
        <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" class="sub-perm" value="module_grades" style="width:16px;height:16px;margin:0;"> <span style="font-size:0.9rem; text-transform:none;">Grades Master</span></div>
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
            <th>Email</th>
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
            <td style="font-size:0.85rem; color:var(--text-muted);">{{ $user['email'] }}</td>
            <td><span class="badge badge-info">{{ $user['role'] }}</span></td>
            <td>
              @if($user['id'] == auth()->id())
                <span class="badge badge-done">You</span>
              @else
                <label class="switch">
                  <input type="checkbox" {{ $user['status'] === 'ACTIVE' ? 'checked' : '' }} 
                    onchange="adminToggleUser({{ $user['id'] }})">
                  <span class="slider"></span>
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

function toggleRoleFields(role) {
  const branchContainer = document.getElementById('branch-field-container');
  const permContainer = document.getElementById('permissions-container');
  const visibilityContainer = document.getElementById('visible-cashiers-container');
  
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
  
  if (role === 'SUB_ADMIN') {
    permContainer.style.display = 'block';
  } else {
    permContainer.style.display = 'none';
  }
}

function adminEditUser(user) {
  editingUserId = user.id;
  document.getElementById('user-form-card').style.display = 'block';
  document.querySelector('#user-form-card .card-title').innerText = 'Edit User';
  document.getElementById('u-name').value = user.name;
  document.getElementById('u-email').value = user.email;
  document.getElementById('u-role').value = user.role;
  document.getElementById('u-branch').value = user.branch || '';
  toggleRoleFields(user.role);
  
  // Set permissions if it's a SUB_ADMIN
  const perms = user.permissions || [];
  document.getElementById('perm-can_manage').checked = perms.includes('can_manage');
  document.querySelectorAll('.sub-perm').forEach(cb => {
      cb.checked = perms.includes(cb.value);
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

  const payload = {
    user_id: editingUserId,
    name: document.getElementById('u-name').value,
    email: document.getElementById('u-email').value,
    role: document.getElementById('u-role').value,
    branch: document.getElementById('u-branch').value,
    password: document.getElementById('u-password').value,
    permissions: perms,
    visible_cashiers: Array.from(document.querySelectorAll('.visible-cashier-cb:checked')).map(cb => parseInt(cb.value))
  };
  
  if (!payload.name || !payload.email || !payload.role) {
    Swal.fire('Required', 'Name, Email and Role are required', 'warning'); return;
  }
  if (!editingUserId && !payload.password) {
    Swal.fire('Required', 'Password is required for new users', 'warning'); return;
  }

  fetch('/admin/users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
    body: JSON.stringify(payload)
  }).then(r => r.json()).then(d => {
    if (d.success) { 
      Swal.fire('Success', d.message, 'success');
      setTimeout(() => location.reload(), 800); 
    } else {
      Swal.fire('Error', d.message || 'Error', 'error');
    }
  }).catch(e => {
    Swal.fire('Error', 'A server error occurred while saving. Please try again.', 'error');
    console.error('Save User Error:', e);
  });
}

function adminToggleUser(id) {
  fetch('/admin/users/toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
    body: JSON.stringify({ user_id: id })
  }).then(r => r.json()).then(d => {
    if (d.success) app.toast(d.message);
    else { app.toast(d.message || 'Error', 'error'); location.reload(); }
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
            fetch('/admin/notifications/send', {
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
