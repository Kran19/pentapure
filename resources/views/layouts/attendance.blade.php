<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="base-url" content="{{ url('/') }}">
  <title>Pentapure Factory - Attendance Management</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/tabulator-custom.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-mode attendance-mode">
  <div id="toast-container"></div>

  <div class="app-container" id="app-root">
    <div id="main-app" style="width:100%;height:100%;display:flex;flex-direction:column;">
      <div id="admin-panel" class="admin-layout-wrapper">

        <div id="admin-hamburger" class="admin-hamburger"
          onclick="document.getElementById('admin-sidebar').classList.add('mobile-open')">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </div>

        <div id="admin-mobile-header" class="admin-mobile-header">Attendance Manager</div>

        <!-- Sidebar -->
        <div id="admin-sidebar" class="admin-sidebar">
          <div style="text-align:center;padding:1rem 1rem 0.5rem;">
            <img src="https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png" alt="Logo"
              style="width:80px;object-fit:contain;margin-bottom:0.5rem;">
          </div>
          <div style="padding:0 1rem 1rem;font-size:1.3rem;font-weight:bold;color:var(--dark-brand);
            border-bottom:1px solid var(--glass-border);display:flex;justify-content:space-between;align-items:center;">
            <div>Attendance<span style="color:var(--primary-light)">System</span></div>
            <svg class="admin-close-btn" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2"
              onclick="document.getElementById('admin-sidebar').classList.remove('mobile-open')">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </div>

          @php $seg = request()->segment(2) ?? 'home'; @endphp

          <a href="{{ url('attendance/home') }}" class="nav-item {{ $seg=='home'?'active':'' }}">
            📊 Dashboard
          </a>
          <a href="{{ url('attendance/departments') }}" class="nav-item {{ $seg=='departments'?'active':'' }}">
            🏢 Departments
          </a>
          <a href="{{ url('attendance/workers') }}" class="nav-item {{ $seg=='workers'?'active':'' }}">
            👷‍♂️ Workers Master
          </a>
          <a href="{{ url('attendance/daily') }}" class="nav-item {{ $seg=='daily'?'active':'' }}">
            📅 Daily Attendance
          </a>
          <a href="{{ url('attendance/reports') }}" class="nav-item {{ $seg=='reports'?'active':'' }}">
            📑 Monthly Reports
          </a>

          <!-- Logout -->
          <div style="margin-top:auto;border-top:1px solid var(--glass-border);padding-top:0.5rem;">
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
              @csrf
              <button type="submit" class="nav-item"
                style="width:100%;background:none;border:none;cursor:pointer;color:var(--danger);display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1.2rem;font-size:1rem;">
                🚪 Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Content -->
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.csrfToken = csrfToken;
    window.logoutUrl = "{{ route('logout') }}";
  </script>
</body>
</html>
