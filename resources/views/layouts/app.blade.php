<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Pentapure Factory Operations</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <!-- Toast Notification Container -->
  <div id="toast-container"></div>

  <!-- Bottom Drawer -->
  <div id="bottom-drawer-overlay" class="bottom-drawer-overlay" onclick="if(event.target.id==='bottom-drawer-overlay') app.closeDrawer()">
    <div class="bottom-drawer" id="bottom-drawer">
      <div class="bottom-drawer-handle"></div>
      <div id="drawer-content"></div>
    </div>
  </div>

  <!-- Global Modal Overlay -->
  <div id="modal-overlay" class="modal-overlay" onclick="if(event.target.id==='modal-overlay') app.closeModal()">
    <div class="modal-content" id="modal-content">
    </div>
  </div>

  <div class="app-container" id="app-root">
    <div id="main-app" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
      
      <!-- Standard Header (Except Admin) -->
      <header id="app-header">
        <div class="user-info">
          @php
              $prefix = request()->segment(1) ?? 'user';
              $segments = [
                  'raw' => ['Amit (Raw)', 'RAW'],
                  'semi' => ['Rahul (Semi)', 'SEMI'],
                  'finished' => ['Vikram (Finished)', 'FINISHED'],
                  'cashier' => ['Sneha (Cashier)', 'CASHIER'],
                  'sales' => ['Raj (Sales)', 'SALES'],
                  'dispatch' => ['Ravi (Dispatch)', 'DISPATCH'],
                  'attendance' => ['Manager (Attendance)', 'ATTENDANCE'],
              ];
              $userName = $segments[$prefix][0] ?? 'User';
              $userRole = $segments[$prefix][1] ?? strtoupper($prefix);
          @endphp
          <span class="user-name" id="current-user-name">{{ $userName }}</span>
          <span class="role-badge" id="current-user-role">{{ $userRole }}</span>
        </div>
        <div>
          <a href="{{ url($prefix . '/profile') }}" style="color:var(--text-muted);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </a>
        </div>
      </header>

      <div class="main-content" id="content-area">
        @yield('content')
      </div>

      <!-- Bottom Navigation -->
      <div class="bottom-nav" id="bottom-nav">
        @php $currentRoute = request()->segment(2) ?? 'home'; @endphp
        
        <a href="{{ url($prefix . '/home') }}" class="nav-item {{ $currentRoute == 'home' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          <span>Home</span>
        </a>
        <a href="{{ url($prefix . '/action') }}" class="nav-item {{ $currentRoute == 'action' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          <span>Action</span>
        </a>
        <a href="{{ url($prefix . '/history') }}" class="nav-item {{ $currentRoute == 'history' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <span>Reports</span>
        </a>
        @if($prefix === 'attendance')
        <a href="{{ url($prefix . '/team') }}" class="nav-item {{ $currentRoute == 'team' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>Team</span>
        </a>
        @endif
        <a href="{{ url($prefix . '/profile') }}" class="nav-item {{ $currentRoute == 'profile' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Profile</span>
        </a>
      </div>
      
    </div>
  </div>

  <script src="{{ asset('js/mockData.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    // ── CSRF for all fetch() calls ─────────────────────────────────────────
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Bridge server data into the existing DB object ─────────────────────
    @if(isset($pageData))
    (function() {
      const serverData = @json($pageData);
      // Merge server data into DB so app.js render functions use real data
      Object.keys(serverData).forEach(key => {
        localStorage.setItem('__server_' + key, JSON.stringify(serverData[key]));
      });
      // Override DB.get to prefer server data when available
      const _originalGet = DB.get.bind(DB);
      DB.get = function(key) {
        const serverKey = '__server_' + key;
        const serverVal = localStorage.getItem(serverKey);
        if (serverVal !== null) {
          try { return JSON.parse(serverVal); } catch(e) {}
        }
        return _originalGet(key);
      };
    })();
    @endif
    
    // Inject Server Data
    const serverData = {!! json_encode($pageData ?? []) !!};
    Object.keys(serverData).forEach(k => DB.set(k, serverData[k]));

    // ── Set auth user ─────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      const role    = "{{ $authUser['role'] ?? '' }}";
      const userId  = {{ $authUser['id'] ?? 'null' }};
      const users   = DB.get('users') || [];
      app.currentUser = users.find(u => u.id == userId || u.role === role) || {
        id: userId, name: '{{ $authUser["name"] ?? "" }}', role: role
      };
      const lang = localStorage.getItem('pentapure_lang') || 'en';
      app.currentLang = lang;
      app.refreshAppTranslatables();
    });
  </script>
</body>
</html>
