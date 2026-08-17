<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="base-url" content="{{ url(request()->segment(1) . '/') }}">
  <script>window.userSlug = '{{ request()->segment(1) }}';</script>
  <title>Pentapure Factory Operations</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/tabulator-custom.css') }}">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    // UX - Maintain Scroll Position Across Reloads
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scroll_pos_' + window.location.pathname, window.scrollY);
    });
    document.addEventListener('DOMContentLoaded', function() {
        let scrollPos = sessionStorage.getItem('scroll_pos_' + window.location.pathname);
        if (scrollPos) {
            window.scrollTo(0, parseInt(scrollPos));
        }
    });
  </script>
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
    <div id="main-app" class="app-main-layout">

      <!-- Navigation (Sidebar on Desktop, Bottom on Mobile) -->
        @php
          $sessUser = session('auth_user') ?? (auth()->user() ? auth()->user()->toArray() : null);
          $role = strtolower($sessUser['role'] ?? 'user');
          if ($role === 'sub_admin' || $role === 'stock_manager') $role = 'admin';
          
          $prefix = request()->segment(1);
          $currentRoute = request()->segment(2) ?? 'home';
        @endphp

        <div class="nav-logo desktop-only">
          <img src="https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png" alt="Logo">
          <span>Penta<span class="text-primary">Pure</span></span>
        </div>

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
        @if($role === 'cashier')
        <a href="{{ url($prefix . '/ledger') }}" class="nav-item {{ $currentRoute == 'ledger' ? 'active' : '' }}" style="text-decoration:none;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          <span>Ledger</span>
        </a>
        @endif
        @if($role === 'attendance')
        <a href="{{ url($prefix . '/team') }}" class="nav-item {{ $currentRoute == 'team' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span>Team</span>
        </a>
        @endif
        <a href="{{ url($prefix . '/profile') }}" class="nav-item {{ $currentRoute == 'profile' ? 'active' : '' }}" style="text-decoration:none;">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span>Profile</span>
        </a>

        <div class="nav-footer desktop-only">
          <button class="logout-btn" onclick="app.logout()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
          </button>
        </div>
      </div>

      <div class="content-wrapper">
        <!-- Standard Header (Except Admin) -->
        <header id="app-header">
          <div class="user-info">
              @php
                $segments = [
                  'raw' => ['Raw', 'RAW MATERIAL'],
                  'semi' => ['Semi', 'SEMI PRODUCT'],
                  'finished' => ['Finished', 'FINISHED GOODS'],
                  'cashier' => ['Cashier', 'FINANCE'],
                  'sales' => ['Sales', 'SALES'],
                  'dispatch' => ['Dispatch', 'DISPATCH'],
                  'attendance' => ['Attendance', 'ATTENDANCE']
                ];
                $userName = $sessUser['name'] ?? ($segments[$role][0] ?? 'User');
                $userRole = $sessUser['role'] ?? ($segments[$role][1] ?? strtoupper($role));
              @endphp
            <span class="user-name" id="current-user-name">{{ $userName }}</span>
            <span class="role-badge" id="current-user-role">{{ $userRole }}</span>
          </div>
          <div class="header-actions" style="display:flex; align-items:center;">
            <div id="notif-bell-container" style="position:relative; cursor:pointer; margin-right:15px; color:var(--text-main);" onclick="app.toggleNotifications()">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              <span id="notif-badge" style="position:absolute; top:-5px; right:-5px; background:var(--danger); color:white; font-size:10px; padding:2px 5px; border-radius:10px; display:none; min-width:16px; text-align:center;">0</span>
            </div>
            <a href="{{ url($prefix . '/profile') }}" class="profile-link" style="color:var(--text-main);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </a>
          </div>
        </header>

        <div class="main-content" id="content-area">
          @php 
            $seg2 = request()->segment(2) ?? 'home';
          @endphp
          @if(!in_array($seg2, ['home', 'dashboard']))
            <div style="margin-bottom: 1rem;">
              <button onclick="history.back()" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:8px; padding:0.4rem 0.8rem; cursor:pointer; color:var(--text-main); display:inline-flex; align-items:center; gap:5px; transition:all 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <line x1="19" y1="12" x2="5" y2="12"></line>
                   <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back
              </button>
            </div>
          @endif
          @yield('content')
        </div>
      </div>

    </div>
  </div>

  <script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
  <script src="{{ asset('js/tabulator-init.js') }}"></script>
  <script src="{{ asset('js/table-sorter.js') }}"></script>
  <script src="{{ asset('js/table-filter.js') }}"></script>
  <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
  <script>
    // ── CSRF for all fetch() calls ─────────────────────────────────────────
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.logoutUrl = "{{ route(request()->segment(1) . '.logout') }}";

    // ── Global Server Page Data ───────────────────────────────────────────
    window.serverPageData = {!! json_encode($pageData ?? []) !!};

    // ── Set auth user ─────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      const role    = "{{ $authUser['role'] ?? '' }}";
      const userId  = {{ $authUser['id'] ?? 'null' }};
      const name    = "{{ $authUser['name'] ?? '' }}";
      app.currentUser = {
        id: userId, name: name, role: role
      };
      const lang = localStorage.getItem('pentapure_lang') || 'en';
      app.currentLang = lang;
      app.refreshAppTranslatables();
    });
  </script>
</body>
</html>
