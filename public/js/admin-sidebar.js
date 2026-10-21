/**
 * Admin / Attendance mobile navigation drawer.
 * Handles open/close, backdrop, Escape key, swipe-to-close,
 * auto-close on navigation and on resize to desktop.
 */
(function () {
  var MOBILE_QUERY = '(max-width: 768px)';

  function getSidebar() {
    return document.getElementById('admin-sidebar');
  }

  function getHamburger() {
    return document.getElementById('admin-hamburger');
  }

  function ensureBackdrop() {
    var backdrop = document.getElementById('admin-sidebar-backdrop');
    var sidebar = getSidebar();
    if (!backdrop && sidebar) {
      backdrop = document.createElement('div');
      backdrop.id = 'admin-sidebar-backdrop';
      backdrop.className = 'admin-sidebar-backdrop';
      backdrop.addEventListener('click', closeAdminSidebar);
      // Keep it in the same stacking context as the drawer
      sidebar.insertAdjacentElement('afterend', backdrop);
    }
    return backdrop;
  }

  function openAdminSidebar() {
    var sidebar = getSidebar();
    if (!sidebar) return;
    ensureBackdrop();
    sidebar.classList.add('mobile-open');
    document.body.classList.add('admin-sidebar-open');
    var hb = getHamburger();
    if (hb) hb.setAttribute('aria-expanded', 'true');

    var active = sidebar.querySelector('.nav-item.active');
    if (active && active.scrollIntoView) {
      setTimeout(function () { active.scrollIntoView({ block: 'nearest' }); }, 50);
    }
  }

  function closeAdminSidebar() {
    var sidebar = getSidebar();
    if (sidebar) sidebar.classList.remove('mobile-open');
    document.body.classList.remove('admin-sidebar-open');
    var hb = getHamburger();
    if (hb) hb.setAttribute('aria-expanded', 'false');
  }

  function toggleAdminSidebar() {
    var sidebar = getSidebar();
    if (sidebar && sidebar.classList.contains('mobile-open')) {
      closeAdminSidebar();
    } else {
      openAdminSidebar();
    }
  }

  window.openAdminSidebar = openAdminSidebar;
  window.closeAdminSidebar = closeAdminSidebar;
  window.toggleAdminSidebar = toggleAdminSidebar;

  document.addEventListener('DOMContentLoaded', function () {
    var sidebar = getSidebar();
    if (!sidebar) return;
    ensureBackdrop();

    // Close drawer after tapping a navigation link on mobile
    sidebar.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a.nav-item') : null;
      if (link && window.matchMedia(MOBILE_QUERY).matches) {
        closeAdminSidebar();
      }
    });

    // Swipe left to close
    var startX = null, startY = null;
    sidebar.addEventListener('touchstart', function (e) {
      if (!e.touches || !e.touches.length) return;
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }, { passive: true });
    sidebar.addEventListener('touchend', function (e) {
      if (startX === null || !e.changedTouches || !e.changedTouches.length) return;
      var dx = e.changedTouches[0].clientX - startX;
      var dy = e.changedTouches[0].clientY - startY;
      if (dx < -60 && Math.abs(dx) > Math.abs(dy) * 1.5) {
        closeAdminSidebar();
      }
      startX = startY = null;
    }, { passive: true });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAdminSidebar();
  });

  window.addEventListener('resize', function () {
    if (!window.matchMedia(MOBILE_QUERY).matches) closeAdminSidebar();
  });
})();
