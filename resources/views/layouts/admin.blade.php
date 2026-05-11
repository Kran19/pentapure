<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Pentapure Factory Operations - Admin</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/tabulator-custom.css') }}">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-mode">
  <div id="toast-container"></div>

  <div id="modal-overlay" class="modal-overlay" onclick="if(event.target.id==='modal-overlay') app.closeModal()">
    <div class="modal-content" id="modal-content"></div>
  </div>

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

        <div id="admin-mobile-header" class="admin-mobile-header">Pentapure Admin</div>

        <!-- Sidebar -->
        <div id="admin-sidebar" class="admin-sidebar">
          <div style="text-align:center;padding:1rem 1rem 0.5rem;">
            <img src="https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png" alt="Logo"
              style="width:80px;object-fit:contain;margin-bottom:0.5rem;">
          </div>
          <div style="padding:0 1rem 1rem;font-size:1.3rem;font-weight:bold;color:white;
            border-bottom:1px solid var(--glass-border);display:flex;justify-content:space-between;align-items:center;">
            <div>Admin<span style="color:var(--primary-light)">Panel</span></div>
            <svg class="admin-close-btn" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2"
              onclick="document.getElementById('admin-sidebar').classList.remove('mobile-open')">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </div>

          @php $seg = request()->segment(2) ?? 'dashboard'; @endphp

          <a href="{{ url('admin/dashboard') }}" class="nav-item {{ $seg=='dashboard'?'active':'' }}">
            📊 Dashboard
          </a>
          <a href="{{ url('admin/users') }}" class="nav-item {{ $seg=='users'?'active':'' }}">
            👥 Users &amp; Hierarchy
          </a>
          <a href="{{ url('admin/products') }}" class="nav-item {{ $seg=='products'?'active':'' }}">
            🏷️ Products Master
          </a>
          <a href="{{ url('admin/stock') }}" class="nav-item {{ $seg=='stock'?'active':'' }}">
            📦 Live Stock
          </a>
          <a href="{{ url('admin/po') }}" class="nav-item {{ $seg=='po'?'active':'' }}">
            📋 Purchase Orders
          </a>
          <a href="{{ url('admin/logs') }}" class="nav-item {{ $seg=='logs'?'active':'' }}">
            🕐 Activity Logs
          </a>
          <a href="{{ url('admin/grades') }}" class="nav-item {{ $seg=='grades'?'active':'' }}">
            ✅ Grades Master
          </a>

          <!-- Attendance Accordion -->
          @php $attSegs = ['attendance-dash','attendance-depts','attendance-workers','attendance-daily','attendance-reports']; @endphp
          <div>
            <div class="nav-item" id="att-toggle" onclick="toggleAttMenu()"
              style="cursor:pointer; display:flex; justify-content:space-between; align-items:center;">
              <span>🧑‍💼 Attendance & HR</span>
              <svg id="att-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                style="transition:transform 0.3s;"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div id="att-submenu" style="display:none; padding-left:1rem; border-left:2px solid var(--primary);">
              <a href="{{ url('admin/attendance/dashboard') }}" class="nav-item" style="font-size:0.9rem; padding:0.6rem 1rem;">
                📊 Dashboard
              </a>
              <a href="{{ url('admin/attendance/departments') }}" class="nav-item" style="font-size:0.9rem; padding:0.6rem 1rem;">
                🏢 Departments
              </a>
              <a href="{{ url('admin/attendance/workers') }}" class="nav-item" style="font-size:0.9rem; padding:0.6rem 1rem;">
                👷 Workers List
              </a>
              <a href="{{ url('admin/attendance/daily') }}" class="nav-item" style="font-size:0.9rem; padding:0.6rem 1rem;">
                @if($authUser['role'] === 'ATTENDANCE') 📝 Daily Entry @else 🔍 Daily Review @endif
              </a>
              <a href="{{ url('admin/attendance/reports') }}" class="nav-item" style="font-size:0.9rem; padding:0.6rem 1rem;">
                📑 Monthly Reports
              </a>
            </div>
          </div>

          <!-- Logout via POST to properly clear session -->
          <div style="margin-top:auto;border-top:1px solid var(--glass-border);padding-top:0.5rem;">
            <form method="POST" action="/logout" style="margin:0;">
              @csrf
              <button type="submit" class="nav-item"
                style="width:100%;background:none;border:none;cursor:pointer;color:var(--danger);display:flex;align-items:center;gap:0.75rem;padding:0.85rem 1.2rem;font-size:1rem;">
                🚪 Logout
              </button>
            </form>
          </div>
        </div>

        <!-- Content area fully rendered by Blade -->
        <div class="main-content" id="content-area">
          @yield('content')
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
  <script src="{{ asset('js/tabulator-init.js') }}"></script>
  <script src="{{ asset('js/table-sorter.js') }}"></script>
  <script src="{{ asset('js/mockData.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    @if(isset($pageData))
    window.serverPageData = @json($pageData);
    @endif

    document.addEventListener('DOMContentLoaded', () => {
      app.currentUser = {
        id:   {{ $authUser['id'] ?? 'null' }},
        name: '{{ addslashes($authUser["name"] ?? "Admin") }}',
        role: 'ADMIN'
      };
      app.currentLang = localStorage.getItem('pentapure_lang') || 'en';
    });
    function toggleAttMenu() {
      const menu = document.getElementById('att-submenu');
      const arrow = document.getElementById('att-arrow');
      const isOpen = menu.style.display !== 'none';
      menu.style.display = isOpen ? 'none' : 'block';
      arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }
    // Auto-open if on an attendance sub-page
    if (window.location.pathname.includes('/admin/attendance')) {
      document.addEventListener('DOMContentLoaded', () => {
        const menu = document.getElementById('att-submenu');
        const arrow = document.getElementById('att-arrow');
        if (menu) { menu.style.display = 'block'; arrow.style.transform = 'rotate(180deg)'; }
      });
    }
  </script>
</body>
</html>
