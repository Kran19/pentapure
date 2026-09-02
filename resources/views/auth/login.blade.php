@extends('layouts.guest')

@section('content')
<style>
  /* Disable auto-capitalization on the login screen */
  #login-screen, #login-screen * {
    text-transform: none !important;
  }
</style>
<div id="login-screen">
  <div class="login-card">
    <img src="{{ asset('logo.png') }}" alt="Logo" style="width:120px; margin-bottom:1rem; object-fit:contain;">
    <h1 style="margin-bottom: 0.3rem; color: var(--text-main);"><span style="color: var(--primary-light);">Pentapure</span></h1>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size:0.9rem;">Select your role to login</p>

    @if(session('error'))
      <div style="background:rgba(239,68,68,0.15); border:1px solid var(--danger); border-radius:8px; padding:0.75rem 1rem; margin-bottom:1rem; color:var(--danger); font-size:0.85rem;">
        {{ session('error') }}
      </div>
    @endif

    {{-- Step 1: Role Selection --}}
    <div id="users-list" style="display:{{ $selectedUser ? 'none' : 'flex' }}; flex-direction:column; gap:0.5rem; text-align:left;">
      @foreach($users as $u)
        <a href="{{ url($u->login_slug . '/login') }}" class="user-btn" style="text-decoration:none; color:inherit; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-weight:600;">{{ $u->name }}</div>
            <div style="font-size:0.8rem; color:var(--text-muted);">{{ $u->role }}</div>
          </div>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
      @endforeach
    </div>

    {{-- Step 2: Password entry --}}
    <div id="password-step" style="display:{{ $selectedUser ? 'block' : 'none' }}; margin-top:1rem;">
      <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:1.2rem; padding:0.8rem; background:rgba(255,255,255,0.05); border-radius:10px;">
        <div id="selected-avatar" style="width:40px; height:40px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:bold;">
          {{ $selectedUser ? strtoupper(substr($selectedUser->name, 0, 1)) : '' }}
        </div>
        <div>
          <div id="selected-name" style="font-weight:600;">{{ $selectedUser ? $selectedUser->name : '' }}</div>
          <div id="selected-role" style="font-size:0.8rem; color:var(--text-muted);">{{ $selectedUser ? $selectedUser->role : '' }}</div>
        </div>
      </div>

      <form id="login-form" action="{{ url()->current() }}" method="POST">
        @csrf
        <input type="hidden" id="user_id_field" name="user_id" value="{{ $selectedUser ? $selectedUser->id : '' }}">
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

      <a href="{{ url('login') }}" style="display:block; margin-top:0.8rem; background:none; border:none; color:var(--text-muted); font-size:0.85rem; cursor:pointer; width:100%; text-decoration:none;">
        &larr; Back to user list
      </a>
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
  // Request notification permission if required in the future
  async function handleNotificationPermission(allow) {
    document.getElementById('notification-modal').classList.remove('active');
    if (allow) {
      if (window.app && typeof app.toast === 'function') {
        app.toast('Please check your browser address bar to allow notifications.', 'info');
      }
      try {
        const subscription = await app.requestNotificationPermission();
        if (subscription) {
          document.getElementById('push_subscription_field').value = JSON.stringify(subscription);
        }
      } catch (e) {
        console.warn('Notification permission error:', e);
      }
    } else {
      if (window.app && typeof app.toast === 'function') {
        app.toast('Warning: You will not receive real-time updates.', 'warning');
      }
    }
  }

  function togglePassword(id) {
    app.togglePassword(id);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
      loginForm.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) {
          btn.style.opacity = '0.85';
          btn.innerHTML = `<svg class="spin" style="width:18px;height:18px;margin-right:8px;vertical-align:middle;display:inline-block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> Logging in...`;
        }
      });
    }
  });
</script>
@endsection
