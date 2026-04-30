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
        <select id="u-role">
          <option value="">-- Select Role --</option>
          @foreach(['RAW','SEMI','FINISHED','CASHIER','SALES','DISPATCH','ATTENDANCE','ADMIN'] as $r)
            <option value="{{ $r }}">{{ $r }}</option>
          @endforeach
        </select>
      </div>
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

function adminEditUser(user) {
  editingUserId = user.id;
  document.getElementById('user-form-card').style.display = 'block';
  document.querySelector('#user-form-card .card-title').innerText = 'Edit User';
  document.getElementById('u-name').value = user.name;
  document.getElementById('u-email').value = user.email;
  document.getElementById('u-role').value = user.role;
  document.getElementById('u-password').value = ''; 
  document.getElementById('u-password').placeholder = '(Leave blank to keep current)';
  document.getElementById('user-form-card').scrollIntoView({ behavior: 'smooth' });
}

function adminSaveUser() {
  const payload = {
    user_id: editingUserId,
    name: document.getElementById('u-name').value,
    email: document.getElementById('u-email').value,
    role: document.getElementById('u-role').value,
    password: document.getElementById('u-password').value,
  };
  
  if (!payload.name || !payload.email || !payload.role) {
    Swal.fire('Required', 'Name, Email and Role are required', 'warning'); return;
  }
  if (!editingUserId && !payload.password) {
    Swal.fire('Required', 'Password is required for new users', 'warning'); return;
  }

  fetch('/admin/users', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(payload)
  }).then(r => r.json()).then(d => {
    if (d.success) { 
      Swal.fire('Success', d.message, 'success');
      setTimeout(() => location.reload(), 800); 
    } else {
      Swal.fire('Error', d.message || 'Error', 'error');
    }
  });
}

function adminToggleUser(id) {
  fetch('/admin/users/toggle', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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
</script>
@endsection
