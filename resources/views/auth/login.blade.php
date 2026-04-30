@extends('layouts.guest')

@section('content')
<div id="login-screen">
  <div class="login-card">
    <img src="https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png" alt="Logo" style="width:120px; margin-bottom:1rem; object-fit:contain;">
    <h1 style="margin-bottom: 0.3rem; color: var(--text-main);"><span style="color: var(--primary-light);">Pentapure</span></h1>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size:0.9rem;">Select your role to login</p>

    @if(session('error'))
      <div style="background:rgba(239,68,68,0.15); border:1px solid var(--danger); border-radius:8px; padding:0.75rem 1rem; margin-bottom:1rem; color:var(--danger); font-size:0.85rem;">
        {{ session('error') }}
      </div>
    @endif

    {{-- Step 1: Role Selection (shown by default) --}}
    <div id="users-list" style="display:flex; flex-direction:column; gap:0.5rem; text-align:left;">
      @foreach($users as $u)
        <button class="user-btn" onclick="selectUser({{ $u->id }}, '{{ $u->name }}', '{{ $u->role }}')"
          style="display:flex; justify-content:space-between; align-items:center; width:100%; background:none; border:none; cursor:pointer;">
          <div>
            <div style="font-weight:600;">{{ $u->name }}</div>
            <div style="font-size:0.8rem; color:var(--text-muted);">{{ $u->role }}</div>
          </div>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
      @endforeach
    </div>

    {{-- Step 2: Password entry (hidden until a user is selected) --}}
    <div id="password-step" style="display:none; margin-top:1rem;">
      <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:1.2rem; padding:0.8rem; background:rgba(255,255,255,0.05); border-radius:10px;">
        <div id="selected-avatar" style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:bold;"></div>
        <div>
          <div id="selected-name" style="font-weight:600;"></div>
          <div id="selected-role" style="font-size:0.8rem; color:var(--text-muted);"></div>
        </div>
      </div>

      <form id="login-form" action="/login" method="POST">
        @csrf
        <input type="hidden" id="user_id_field" name="user_id">
        <input type="hidden" id="push_subscription_field" name="push_subscription">
        <div class="form-group" style="margin-bottom:1rem;">
          <label style="font-size:0.85rem; color:var(--text-muted);">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password-input" placeholder="Enter your password" required
              style="padding:0.8rem; padding-right:2.5rem; font-size:1rem;" autofocus>
            <button type="button" class="password-toggle" onclick="togglePassword('password-input')">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn" style="padding:1rem; font-size:1rem; width:100%;">
          Login &rarr;
        </button>
      </form>

      <button onclick="backToSelect()" style="margin-top:0.8rem; background:none; border:none; color:var(--text-muted); font-size:0.85rem; cursor:pointer; width:100%;">
        &larr; Back to user list
      </button>
    </div>
  </div>
</div>

{{-- Notification Permission Modal --}}
<div id="notification-modal" class="modal-overlay">
  <div class="modal-content" style="max-width:400px; text-align:center;">
    <div style="font-size:3rem; margin-bottom:1rem;">🔔</div>
    <h2 style="margin-bottom:0.5rem;">Enable Notifications</h2>
    <p style="color:var(--text-muted); margin-bottom:1.5rem; font-size:0.9rem;">
      Pentapure requires notification access to send you real-time operation updates, purchase requests, and system alerts.
      <b>You cannot proceed without allowing notifications.</b>
    </p>
    <div style="display:flex; flex-direction:column; gap:0.8rem;">
      <button class="btn" onclick="handleNotificationPermission(true)" style="padding:0.8rem;">Allow Notifications</button>
      <button class="btn btn-secondary" onclick="handleNotificationPermission(false)" style="padding:0.8rem; background:rgba(239,68,68,0.1); color:var(--danger);">I Deny (Logout)</button>
    </div>
  </div>
</div>

<script>
  let pendingUser = null;

  async function selectUser(id, name, role) {
    pendingUser = { id, name, role };
    
    // Check if notification permission is already granted
    if (Notification.permission === 'granted') {
      const subscription = await app.subscribeUser();
      if (subscription) {
        document.getElementById('push_subscription_field').value = JSON.stringify(subscription);
      }
      showPasswordStep();
    } else {
      document.getElementById('notification-modal').classList.add('active');
    }
  }

  async function handleNotificationPermission(allow) {
    if (allow) {
      const subscription = await app.requestNotificationPermission();
      if (subscription) {
        document.getElementById('push_subscription_field').value = JSON.stringify(subscription);
        document.getElementById('notification-modal').classList.remove('active');
        showPasswordStep();
      } else {
        Swal.fire('Permission Required', 'You must allow notifications in your browser settings to continue.', 'error');
      }
    } else {
      Swal.fire('Access Denied', 'Notifications are mandatory for using the Pentapure system.', 'error');
      document.getElementById('notification-modal').classList.remove('active');
    }
  }

  function showPasswordStep() {
    const { id, name, role } = pendingUser;
    document.getElementById('user_id_field').value = id;
    document.getElementById('selected-name').innerText = name;
    document.getElementById('selected-role').innerText = role;
    document.getElementById('selected-avatar').innerText = name.charAt(0).toUpperCase();
    document.getElementById('users-list').style.display = 'none';
    document.getElementById('password-step').style.display = 'block';
    setTimeout(() => document.getElementById('password-input').focus(), 100);
  }

  function backToSelect() {
    document.getElementById('users-list').style.display = 'flex';
    document.getElementById('password-step').style.display = 'none';
    document.getElementById('password-input').value = '';
  }

  function togglePassword(id) {
    app.togglePassword(id);
  }
</script>
@endsection
