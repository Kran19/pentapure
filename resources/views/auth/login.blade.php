@extends('layouts.guest')

@section('content')
<style>
  /* Capitalize input fields on login screen */
  #username-input, #password-input {
    text-transform: uppercase !important;
  }
</style>
<div id="login-screen">
  <div class="login-card" style="max-width:380px; margin:0 auto; padding:2rem 1.5rem; text-align:center;">
    <img src="{{ asset('logo.png') }}" alt="Logo" style="width:100px; margin-bottom:1rem; object-fit:contain;">
    <h1 style="margin-bottom: 0.3rem; color: var(--text-main); font-size:1.6rem;"><span style="color: var(--primary-light);">Pentapure</span></h1>
    <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size:0.9rem;">Enter your User ID and Password to login</p>

    <form id="login-form" action="{{ route('global.login.post') }}" method="POST" style="text-align:left;">
      @csrf
      <input type="hidden" id="push_subscription_field" name="push_subscription">
      
      <div class="form-group" style="margin-bottom:1.2rem;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:0.4rem;">User ID</label>
        <input type="text" name="username" id="username-input" placeholder="Enter your User ID" required
          value="{{ old('username') }}" style="width:100%; padding:0.8rem; font-size:1rem; border-radius:8px; border:1px solid var(--border-soft); background:var(--input-bg); color:var(--text-main); font-weight:600;" autofocus>
      </div>

      <div class="form-group" style="margin-bottom:1.5rem;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--text-muted); display:block; margin-bottom:0.4rem;">Password</label>
        <div class="password-wrapper" style="position:relative;">
          <input type="password" name="password" id="password-input" placeholder="Enter your password" required
            style="width:100%; padding:0.8rem; padding-right:2.5rem; font-size:1rem; border-radius:8px; border:1px solid {{ session('error') || $errors->has('password') || $errors->has('username') ? 'var(--danger, #ef4444)' : 'var(--border-soft)' }}; background:var(--input-bg); color:var(--text-main);">
          <button type="button" class="password-toggle" onclick="togglePassword('password-input')" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-muted); cursor:pointer;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="eye-icon">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
        </div>
        @if(session('error') || $errors->has('password') || $errors->has('username'))
          <div id="login-error-msg" style="color: var(--danger, #ef4444); font-size: 0.85rem; font-weight: 600; margin-top: 0.4rem; display: flex; align-items: center; gap: 0.3rem;">
            <span>⚠️</span>
            <span>{{ session('error') ?? $errors->first('password') ?? $errors->first('username') }}</span>
          </div>
        @endif
      </div>

      <button type="submit" class="btn" style="padding:0.9rem; font-size:1rem; font-weight:700; width:100%; border-radius:8px;">
        Login &rarr;
      </button>
    </form>
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
    if (window.app && typeof app.togglePassword === 'function') {
      app.togglePassword(id);
    } else {
      const input = document.getElementById(id);
      if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
      }
    }
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
