# CHANGE IMPACT ANALYSIS & VERIFICATION PLAN

## Requested Change
1. Standardize all loading screens, SweetAlert2 popups, custom HTML modals, and form dialogs globally to align 100% with the Pentapure warm brand theme palette (Primary Gold `#F4B400`, Cream `#FFF8EA` / `#FFFFFF`, Dark Brand `#2B241C`).
2. Eliminate hardcoded dark slate (`#0f172a`, `#161b22`, `#30363d`) popups across all modules.

## Business Purpose
- Deliver a unified, high-contrast, state-of-the-art brand aesthetic across all ERP modules and modals.

## 360° Impact Analysis

### 1. Business Logic & Invariants
- Zero business logic changes.
- All inventory calculations, user roles, permission checks, and modal form actions remain 100% functional.
- Attendance logic remains 100% frozen.

### 2. Database
- No database changes.

### 3. Frontend, CSS & JavaScript
- Targets:
  1. `public/js/app.js`: `#global-page-loader` theme styling (Light: `#FFF8EA` card, `#F4B400` spinner, `#2B241C` text, `#DDCFAF` border; Dark mode: `#0F172A` background with `#F4B400` border).
  2. `public/css/style.css`: Global design system rules for `.modal-content`, `.modal-overlay`, `.swal2-popup`, `.swal2-modal`, `.swal2-title`, `.swal2-input`, `.swal2-select`, `.swal2-confirm`, `.swal2-cancel`.
  3. `resources/views/admin/stock.blade.php`: Modal inputs, location breakdown popups, alert limit popups, and export option cards themed to Pentapure palette.

## Safe Implementation Strategy
1. Update `showPageLoader` in `app.js`.
2. Add global SweetAlert2 & Modal styling rules in `style.css`.
3. Update hardcoded inline styles in `stock.blade.php` modal popups.
4. Run full PHPUnit test suite to ensure zero regressions.
