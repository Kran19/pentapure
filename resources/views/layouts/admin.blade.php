<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="base-url" content="{{ url('/') }}">
  <title>Pentapure Factory Operations - Admin</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('css/tabulator-custom.css') }}">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark-mode');
    }
  </script>
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

        <div id="admin-mobile-header" class="admin-mobile-header">
          Pentapure Admin
          <a href="{{ url('admin/notifications') }}" id="notif-bell-container" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; color:inherit; text-decoration:none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span id="notif-badge" style="position:absolute; top:-5px; right:-5px; background:var(--danger); color:white; font-size:10px; padding:2px 5px; border-radius:10px; display:none; min-width:16px; text-align:center;">0</span>
          </a>
        </div>

        <!-- Sidebar -->
        <div id="admin-sidebar" class="admin-sidebar">
          <div style="text-align:center;padding:1rem 1rem 0.5rem;">
            <img src="https://pentapurefoods.com/wp-content/uploads/2025/11/logo.png" alt="Logo"
              style="width:80px;object-fit:contain;margin-bottom:0.5rem;">
          </div>
          <div style="padding:0 1rem 1rem;font-size:1.3rem;font-weight:bold;color:var(--dark-brand);
            border-bottom:1px solid var(--glass-border);display:flex;justify-content:space-between;align-items:center;">
            <div>Admin<span style="color:var(--primary-light)">Panel</span></div>
            <svg class="admin-close-btn" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2"
              onclick="document.getElementById('admin-sidebar').classList.remove('mobile-open')">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </div>

          @php 
            $seg = request()->segment(2) ?? 'dashboard'; 
            $role = $authUser['role'];
            $perms = $authUser['permissions'] ?? [];
            $can = fn($m) => $role === 'ADMIN' || in_array($m, $perms);
          @endphp

          @if($can('module_dashboard'))
          <a href="{{ url('admin/home') }}" class="nav-item {{ $seg=='home' || $seg=='dashboard'?'active':'' }}">
            📊 Dashboard
          </a>
          @endif

          @if($can('module_users'))
          <a href="{{ url('admin/users') }}" class="nav-item {{ $seg=='users'?'active':'' }}">
            👥 Users &amp; Hierarchy
          </a>
          @endif

          @if($can('module_stock'))
          <a href="{{ url('admin/stock') }}" class="nav-item {{ $seg=='stock'?'active':'' }}">
            📦 Live Stock
          </a>
          @endif

          @if($can('module_products'))
          <a href="{{ url('admin/products') }}" class="nav-item {{ $seg=='products'?'active':'' }}">
            🏷️ Products Master
          </a>
          @endif

          @if($can('module_po'))
          <a href="{{ url('admin/po') }}" class="nav-item {{ $seg=='po'?'active':'' }}">
            📋 Purchase Orders
          </a>
          @endif

          @if($can('module_dispatch'))
          <a href="{{ url('admin/dispatch-activity') }}" class="nav-item {{ $seg=='dispatch-activity'?'active':'' }}">
            🚚 Dispatch Activity
          </a>
          @endif

          @if($can('module_cashier'))
          <a href="{{ url('admin/cashier-overview') }}" class="nav-item {{ $seg=='cashier-overview'?'active':'' }}">
            💰 Cashier Overview
          </a>
          @endif

          @if($can('module_categories'))
          <a href="{{ url('admin/categories') }}" class="nav-item {{ $seg=='categories'?'active':'' }}">
            🏷️ Expense Category Master
          </a>
          @endif

          @if($can('module_grades'))
          <a href="{{ url('admin/grades') }}" class="nav-item {{ $seg=='grades'?'active':'' }}">
            ✅ Grades Master
          </a>
          @endif

          @if($can('module_logs'))
          <a href="{{ url('admin/logs') }}" class="nav-item {{ $seg=='logs'?'active':'' }}">
            🕐 Activity Logs
          </a>
          @endif

          <a href="#" onclick="event.preventDefault(); openLocationsAdminModal()" class="nav-item">
            📍 Storage Locations
          </a>

          @if($can('module_notifications'))
          <a href="{{ url('admin/notifications') }}" class="nav-item {{ $seg=='notifications'?'active':'' }}"
             style="display:flex; justify-content:space-between; align-items:center;">
            <span>🔔 Notifications</span>
            <span id="nav-notif-count" class="badge badge-danger" style="display:none; font-size:0.7rem; padding:2px 6px;">0</span>
          </a>
          @endif

          <!-- Attendance Accordion -->
          @if($can('module_attendance'))
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
          @endif

          <!-- Logout via POST to properly clear session -->
          <div style="margin-top:auto;border-top:1px solid var(--glass-border);padding-top:0.5rem;">
            <div class="nav-item" style="cursor:pointer; display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1.2rem; font-size:1rem;" onclick="toggleTheme()">
              <span id="theme-icon">🌙</span> <span id="theme-text">Dark Mode</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
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
  <script src="{{ asset('js/table-filter.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    function toggleTheme() {
      const isDark = document.documentElement.classList.toggle('dark-mode');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      updateThemeUI(isDark);
    }
    function updateThemeUI(isDark) {
      const themeIcon = document.getElementById('theme-icon');
      const themeText = document.getElementById('theme-text');
      if (themeIcon) themeIcon.textContent = isDark ? '☀️' : '🌙';
      if (themeText) themeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    }
    document.addEventListener('DOMContentLoaded', () => {
      updateThemeUI(document.documentElement.classList.contains('dark-mode'));
    });

    window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.logoutUrl = "{{ route('logout') }}";

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
    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char]));
    }

    async function openLocationsAdminModal() {
      try {
        const response = await fetch('/api/locations');
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        
        const locs = data.locations;
        let listHtml = locs.map(loc => `
          <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:8px; margin-bottom:6px;">
            <span style="font-weight:600; color:var(--primary-light);">${escapeHtml(loc.name)}</span>
            <button class="btn btn-danger btn-sm" onclick="deleteLocation(${loc.id})" style="padding:4px 8px; width:auto; font-size:0.75rem;">Delete</button>
          </div>
        `).join('') || '<p style="text-align:center;color:#8b949e;">No locations added yet.</p>';

        app.openModal(`
          <div class="card" style="margin:0; width:100%; max-width:400px; text-align:left;">
            <div class="card-title" style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
              <span>📍 Storage Locations</span>
              <button onclick="app.closeModal()" style="background:none; border:none; color:#8b949e; cursor:pointer; font-size:1.2rem;">&times;</button>
            </div>
            <div class="form-group" style="margin-bottom:1rem; display:flex; gap:8px;">
              <input type="text" id="new-location-name" placeholder="e.g. Warehouse C" style="flex:1; padding:0.6rem 0.8rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
              <button class="btn" onclick="addLocation()" style="width:auto; padding:0.6rem 1rem;">Add</button>
            </div>
            <div style="max-height:250px; overflow-y:auto; padding-right:4px;">
              ${listHtml}
            </div>
          </div>
        `);
      } catch (e) {
        app.toast('Failed to load locations: ' + e.message, 'error');
      }
    }

    async function addLocation() {
      const input = document.getElementById('new-location-name');
      const val = input.value.trim();
      if(!val) return app.toast('Enter location name', 'error');

      try {
        const response = await fetch('/admin/locations', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
          body: JSON.stringify({ name: val })
        });
        const data = await response.json();
        if (data.success) {
          app.toast('Location added');
          openLocationsAdminModal();
        } else {
          app.toast(data.message || 'Failed to add location', 'error');
        }
      } catch(e) {
        app.toast('Network error', 'error');
      }
    }

    function deleteLocation(id) {
      Swal.fire({
        title: 'Delete location?',
        text: 'This will permanently remove the location and any associated stock constraints.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete'
      }).then(async res => {
        if(res.isConfirmed) {
          try {
            const response = await fetch(`/admin/locations/${id}`, {
              method: 'DELETE',
              headers: { 'X-CSRF-TOKEN': window.csrfToken }
            });
            const data = await response.json();
            if (data.success) {
              app.toast('Location deleted');
              openLocationsAdminModal();
            } else {
              app.toast(data.message || 'Failed to delete location', 'error');
            }
          } catch(e) {
            app.toast('Network error', 'error');
          }
        }
      });
    }
  </script>
</body>
</html>
