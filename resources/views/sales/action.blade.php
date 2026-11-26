@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Remove number input spinner arrows (increase/decrease) */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button,
.no-spinners::-webkit-outer-spin-button,
.no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}

input[type="number"],
.no-spinners {
  -moz-appearance: textfield !important;
}

/* Full Width Order Product Rows */
#order-products {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.order-product-row {
  width: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
  background: transparent !important;
  padding: 0 !important;
  border: none !important;
  margin: 0 !important;
}

.order-product-row select.o-prod-id,
.order-product-row select.o-prod-grade {
  width: 100% !important;
  display: block !important;
  padding: 0.75rem !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  font-size: 0.9rem !important;
  box-sizing: border-box !important;
}

/* Select2 Container & Selection Styling for Order Products */
.order-product-row .select2-container {
  width: 100% !important;
  max-width: 100% !important;
  display: block !important;
}

.order-product-row .select2-container .select2-selection--single {
  height: 44px !important;
  min-height: 44px !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  display: flex !important;
  align-items: center !important;
  padding: 0 0.5rem !important;
  box-sizing: border-box !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.order-product-row .select2-container--default.select2-container--focus .select2-selection--single,
.order-product-row .select2-container--default.select2-container--open .select2-selection--single {
  border-color: var(--primary, #D88A00) !important;
  box-shadow: 0 0 0 2px rgba(216, 138, 0, 0.25) !important;
  outline: none !important;
}

.order-product-row .select2-container .select2-selection--single .select2-selection__rendered {
  color: var(--text-main, #333) !important;
  font-size: 0.9rem !important;
  font-weight: 600 !important;
  line-height: 42px !important;
  padding-left: 0.35rem !important;
  padding-right: 2rem !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.order-product-row .select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: var(--text-muted, #94a3b8) !important;
  font-weight: 500 !important;
}

.order-product-row .select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 42px !important;
  right: 8px !important;
  top: 1px !important;
}

/* Dropdown Menu Styling */
.order-prod-select2-dropdown.select2-dropdown {
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.18) !important;
  z-index: 999999 !important;
  background: #ffffff !important;
  overflow: hidden !important;
  box-sizing: border-box !important;
  min-width: min(340px, calc(100vw - 24px)) !important;
}

.order-prod-select2-dropdown .select2-search--dropdown {
  padding: 8px !important;
  background: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
}

.order-prod-select2-dropdown .select2-search--dropdown .select2-search__field {
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 6px !important;
  padding: 0.45rem 0.65rem !important;
  font-size: 0.85rem !important;
  outline: none !important;
  background: #ffffff !important;
  color: #0f172a !important;
  width: 100% !important;
  box-sizing: border-box !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}

.order-prod-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25) !important;
}

.order-prod-select2-dropdown .select2-results__group {
  padding: 6px 10px !important;
  font-size: 0.72rem !important;
  font-weight: 700 !important;
  color: var(--primary, #D88A00) !important;
  background: rgba(216, 138, 0, 0.08) !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}

.order-prod-select2-dropdown .select2-results__options {
  max-height: 260px !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 4px 0 !important;
}

.order-prod-select2-dropdown .select2-results__option {
  font-size: 0.84rem !important;
  padding: 0.55rem 0.8rem !important;
  color: #0f172a !important;
  line-height: 1.35 !important;
  border-bottom: 1px solid #f1f5f9 !important;
  cursor: pointer !important;
  background-color: #ffffff !important;
  transition: background-color 0.1s ease, color 0.1s ease !important;
}

.order-prod-select2-dropdown .select2-results__option:last-child {
  border-bottom: none !important;
}

.order-prod-select2-dropdown .select2-results__option[aria-selected="true"] {
  background-color: #fef9c3 !important;
  border-left: 3px solid #eab308 !important;
}

.order-prod-select2-dropdown .select2-results__option[aria-selected="true"] .prod-name {
  color: #854d0e !important;
  font-weight: 700 !important;
}

.order-prod-select2-dropdown .select2-results__option--highlighted,
.order-prod-select2-dropdown .select2-results__option--highlighted[aria-selected],
.order-prod-select2-dropdown .select2-results__option--highlighted[aria-selected="true"],
.order-prod-select2-dropdown .select2-results__option--highlighted[aria-selected="false"],
.order-prod-select2-dropdown .select2-results__option:hover {
  background-color: #fef08a !important;
  color: #000000 !important;
  border-left: 3px solid #ca8a04 !important;
}

.order-prod-select2-dropdown .select2-results__option--highlighted .prod-name,
.order-prod-select2-dropdown .select2-results__option:hover .prod-name {
  color: #000000 !important;
  font-weight: 700 !important;
}

.order-prod-select2-dropdown .select2-results__option--highlighted .prod-stage-badge,
.order-prod-select2-dropdown .select2-results__option:hover .prod-stage-badge {
  border-color: rgba(0, 0, 0, 0.25) !important;
  background-color: #ffffff !important;
  font-weight: 700 !important;
}

/* Dark mode support */
html.dark-mode .order-product-row .select2-container .select2-selection--single {
  background: var(--input-bg, #1e293b) !important;
  border-color: var(--border-soft, #475569) !important;
  color: var(--text-main, #f8fafc) !important;
}

html.dark-mode .order-product-row .select2-container .select2-selection--single .select2-selection__rendered {
  color: var(--text-main, #f8fafc) !important;
}

html.dark-mode .order-prod-select2-dropdown.select2-dropdown {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-search--dropdown {
  background: #0f172a !important;
  border-color: #334155 !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-search--dropdown .select2-search__field {
  background: #1e293b !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__group {
  background: rgba(251, 191, 36, 0.12) !important;
  color: #fbbf24 !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__option {
  background-color: #1e293b !important;
  color: #f8fafc !important;
  border-color: #334155 !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__option[aria-selected="true"] {
  background-color: #3b3518 !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__option[aria-selected="true"] .prod-name {
  color: #fef08a !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__option--highlighted,
html.dark-mode .order-prod-select2-dropdown .select2-results__option:hover {
  background-color: #fef08a !important;
  color: #000000 !important;
}

html.dark-mode .order-prod-select2-dropdown .select2-results__option--highlighted .prod-name,
html.dark-mode .order-prod-select2-dropdown .select2-results__option:hover .prod-name {
  color: #000000 !important;
}

.order-product-row .order-product-inputs {
  width: 100% !important;
  display: flex !important;
  gap: 10px !important;
  align-items: center !important;
}

.order-product-row input.o-prod-qty,
.order-product-row input.o-prod-price {
  width: 100% !important;
  padding: 0.75rem !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  font-size: 0.9rem !important;
  box-sizing: border-box !important;
}

.btn-add-product {
  display: inline-flex !important;
  align-items: center !important;
  padding: 0.45rem 1rem !important;
  font-size: 0.85rem !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}

.btn-add-product:hover {
  background: var(--primary, #F4B400) !important;
  color: #000 !important;
}

.order-product-row .btn-remove-prod {
  flex: 0 0 42px !important;
  width: 42px !important;
  height: 42px !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 8px !important;
  background: #e11d48 !important;
  color: #fff !important;
  border: none !important;
  cursor: pointer !important;
}
.info-preview-box {
  margin-top: 8px;
  padding: 0.8rem 1rem;
  background: #bdbdbd !important;
  color: #1a1a1a !important;
  border-radius: 6px;
  font-size: 0.85rem;
  line-height: 1.5;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.info-preview-box .info-label {
  color: #784d00 !important;
  font-weight: 700 !important;
  margin-right: 4px;
}

html.dark-mode .info-preview-box {
  background: #334155 !important;
  color: #f8fafc !important;
  border: 1px solid #475569;
}

html.dark-mode .info-preview-box .info-label {
  color: #fbbf24 !important;
}

/* ==========================================================================
   Responsive Action Tables (Company & Transport)
   ========================================================================== */
.sales-action-table-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  background: var(--card-bg, #ffffff);
  border-radius: 12px;
  border: 1px solid var(--border-soft, #DDCFAF);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}

.sales-action-header-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 12px;
}

.sales-action-table-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.15rem;
  color: var(--primary, #D88A00);
  display: flex;
  align-items: center;
  gap: 8px;
}

.sales-action-count-pill {
  font-size: 0.8rem;
  font-weight: 700;
  background: rgba(216, 138, 0, 0.12);
  color: var(--primary, #D88A00);
  padding: 2px 9px;
  border-radius: 12px;
  border: 1px solid rgba(216, 138, 0, 0.28);
}

.sales-action-search-box {
  width: 250px;
  padding: 0.48rem 0.85rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1.5px solid var(--border-soft, #DDCFAF);
  background: var(--input-bg, #ffffff);
  color: var(--text-main, #333333);
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.sales-action-search-box:focus {
  border-color: var(--primary, #D88A00);
  box-shadow: 0 0 0 3px rgba(216, 138, 0, 0.15);
}

.sales-action-btn-add {
  padding: 0.48rem 1rem;
  font-size: 0.85rem;
  font-weight: 700;
  white-space: nowrap;
  border-radius: 8px;
  background: var(--primary, #D88A00);
  color: #ffffff !important;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 2px 4px rgba(216, 138, 0, 0.2);
  transition: all 0.15s ease;
}

.sales-action-btn-add:hover {
  background: var(--primary-dark, #b57400);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(216, 138, 0, 0.3);
}

.sales-data-table-container {
  width: 100% !important;
  max-width: 100% !important;
  overflow-x: auto !important;
  -webkit-overflow-scrolling: touch !important;
  border: 1px solid var(--border-soft, #DDCFAF);
  border-radius: 8px;
  background: var(--card-bg, #ffffff);
}

.sales-data-table {
  width: 100% !important;
  min-width: 1020px !important;
  table-layout: fixed !important;
  border-collapse: collapse !important;
  font-size: 0.86rem;
  text-align: left;
}

.sales-data-table thead tr {
  background: rgba(216, 138, 0, 0.05);
  border-bottom: 2px solid var(--border-soft, #DDCFAF);
}

.sales-data-table th {
  padding: 12px 14px !important;
  font-size: 0.74rem !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  color: var(--text-muted, #78716c) !important;
  white-space: nowrap !important;
  vertical-align: middle !important;
  box-sizing: border-box !important;
}

.sales-data-table tbody tr {
  border-bottom: 1px solid var(--border-soft, rgba(0, 0, 0, 0.06));
  transition: background 0.15s ease;
}

.sales-data-table tbody tr:hover {
  background: rgba(216, 138, 0, 0.04);
}

.sales-data-table td {
  padding: 11px 14px !important;
  vertical-align: middle !important;
  color: var(--text-main, #1e293b);
  box-sizing: border-box !important;
  overflow: hidden !important;
}

/* Transporter table columns */
.sales-data-table .col-idx {
  width: 50px !important;
  text-align: center !important;
  color: var(--text-muted, #94a3b8);
  font-weight: 600;
  white-space: nowrap !important;
}

.sales-data-table .col-name,
.sales-data-table .action-trans-name {
  width: 250px !important;
  font-weight: 700;
  color: var(--text-main, #0f172a);
  white-space: normal !important;
  word-break: break-word !important;
  overflow-wrap: anywhere !important;
  line-height: 1.35 !important;
}

.sales-data-table .col-gst {
  width: 140px !important;
  font-family: monospace;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--primary-light, #d97706);
  white-space: nowrap !important;
}

.sales-data-table .col-contact {
  width: 150px !important;
  font-weight: 600;
  white-space: nowrap !important;
}

.sales-data-table .col-vehicles {
  width: 240px !important;
  white-space: normal !important;
  word-break: break-word !important;
  overflow-wrap: anywhere !important;
  line-height: 1.4 !important;
  font-size: 0.84rem !important;
}

.sales-data-table .col-action {
  width: 190px !important;
  min-width: 190px !important;
  max-width: 190px !important;
  text-align: center !important;
  white-space: nowrap !important;
  vertical-align: middle !important;
}

/* Company table specific columns */
.sales-data-table .col-comp-name,
.sales-data-table .action-comp-name {
  width: 230px !important;
  font-weight: 700;
  color: var(--text-main, #0f172a);
  white-space: normal !important;
  word-break: break-word !important;
  overflow-wrap: anywhere !important;
  line-height: 1.35 !important;
}

.sales-data-table .col-comp-gst {
  width: 140px !important;
  font-family: monospace;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--primary-light, #d97706);
  white-space: nowrap !important;
}

.sales-data-table .col-comp-pin {
  width: 90px !important;
  font-weight: 600;
  white-space: nowrap !important;
}

.sales-data-table .col-comp-contact {
  width: 140px !important;
  font-weight: 600;
  white-space: nowrap !important;
}

.sales-data-table .col-comp-address {
  width: 230px !important;
  white-space: normal !important;
  word-break: break-word !important;
  overflow-wrap: anywhere !important;
  color: var(--text-muted, #64748b);
  line-height: 1.35 !important;
}

.sales-data-table .col-comp-action {
  width: 190px !important;
  min-width: 190px !important;
  max-width: 190px !important;
  text-align: center !important;
  white-space: nowrap !important;
  vertical-align: middle !important;
}

.sales-action-btn-group {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  white-space: nowrap !important;
  vertical-align: middle !important;
}

.sales-btn-edit {
  padding: 5px 12px !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  background: #f59e0b !important;
  color: #000000 !important;
  border: none !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  text-transform: uppercase !important;
  letter-spacing: 0.3px !important;
  box-shadow: 0 1px 2px rgba(0,0,0,0.06) !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
}

.sales-btn-edit:hover {
  background: #d97706 !important;
  color: #ffffff !important;
  transform: translateY(-1px) !important;
}

.sales-btn-delete {
  padding: 5px 12px !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  background: #ef4444 !important;
  color: #ffffff !important;
  border: none !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  text-transform: uppercase !important;
  letter-spacing: 0.3px !important;
  box-shadow: 0 1px 2px rgba(0,0,0,0.06) !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
}

.sales-btn-delete:hover {
  background: #dc2626 !important;
  transform: translateY(-1px) !important;
}

.sales-btn-delete-disabled {
  padding: 5px 12px !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
  background: rgba(0, 0, 0, 0.05) !important;
  color: #94a3b8 !important;
  border: 1px solid rgba(0, 0, 0, 0.1) !important;
  border-radius: 6px !important;
  cursor: not-allowed !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  text-transform: uppercase !important;
  letter-spacing: 0.3px !important;
  opacity: 0.75 !important;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
}

.sales-in-use-badge {
  display: inline-block !important;
  font-size: 0.65rem !important;
  font-weight: 800 !important;
  background: rgba(216, 138, 0, 0.12) !important;
  color: var(--primary, #D88A00) !important;
  border: 1px solid rgba(216, 138, 0, 0.3) !important;
  border-radius: 4px !important;
  padding: 1px 6px !important;
  margin-left: 6px !important;
  vertical-align: middle !important;
  letter-spacing: 0.3px !important;
  white-space: nowrap !important;
}

/* Dark mode overrides */
html.dark-mode .sales-action-table-card {
  background: #1e293b;
  border-color: #334155;
}

html.dark-mode .sales-action-search-box {
  background: #0f172a;
  border-color: #334155;
  color: #f8fafc;
}

html.dark-mode .sales-data-table-container {
  background: #1e293b;
  border-color: #334155;
}

html.dark-mode .sales-data-table thead tr {
  background: #0f172a;
  border-color: #334155;
}

html.dark-mode .sales-data-table th {
  color: #94a3b8 !important;
}

html.dark-mode .sales-data-table tbody tr {
  border-color: #334155;
}

html.dark-mode .sales-data-table tbody tr:hover {
  background: rgba(216, 138, 0, 0.08);
}

html.dark-mode .sales-data-table td {
  color: #f1f5f9 !important;
}

html.dark-mode .sales-data-table .col-name,
html.dark-mode .sales-data-table .action-trans-name,
html.dark-mode .sales-data-table .col-comp-name,
html.dark-mode .sales-data-table .action-comp-name {
  color: #ffffff !important;
}

html.dark-mode .sales-btn-delete-disabled {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #64748b !important;
}
</style>
<div class="tabs">
  <button class="tab-btn active" onclick="switchSalesTab('order', this)">Create Order</button>
  <button class="tab-btn" onclick="switchSalesTab('company', this)">Company</button>
  <button class="tab-btn" onclick="switchSalesTab('transport', this)">Transport</button>
</div>

<div style="margin-top:1.5rem;">
  <!-- Tab 1: Create Order -->
  <div id="sales-tab-order" class="sales-tab-content animation-fadeIn" style="display:block;">
    <div class="card">
      <div class="card-title">📦 New Sales Order</div>
      
      @if(!empty($pageData['editOrder']))
        <input type="hidden" id="edit-order-id" value="{{ $pageData['editOrder']->id }}">
        <div style="margin-bottom:1rem; padding:0.5rem 1rem; background:rgba(255,165,0,0.15); border-left:4px solid var(--warning); border-radius:4px; font-size:0.9rem;">
          ✏️ Editing Order #<strong>{{ $pageData['editOrder']->id }}</strong>
        </div>
      @endif

      <div class="form-group">
        <label>Select Company *</label>
        <select id="order-company" onchange="app.onSalesCompanySelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>-- Select Company --</option>
          @foreach($pageData['companies'] as $c)
            @php
              $gstClean = trim((string)($c['gst'] ?? ''));
              $hasGst = $gstClean !== '' && !in_array(strtoupper($gstClean), ['N/A', 'NA']);
            @endphp
            <option value="{{ $c['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->company_id == $c['id']) ? 'selected' : '' }}>
              {{ $c['name'] }}{{ $hasGst ? ' ('.$gstClean.')' : '' }}
            </option>
          @endforeach
        </select>
        <div id="company-details" class="info-preview-box" style="display:none;"></div>
      </div>

      <div class="form-group">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
          <label style="margin-bottom:0;">Select Transport</label>
          @if(empty($isReadOnly))
          <button type="button" class="btn btn-sm" onclick="app.openAddTransportModal()" style="padding:0.35rem 0.8rem; font-size:0.78rem; font-weight:700; width:auto; border-radius:6px; letter-spacing:0.3px;">+ ADD TRANSPORT</button>
          @endif
        </div>
        <select id="order-transport" onchange="app.onSalesTransportSelect(this.value)">
          <option value="" {{ (empty($pageData['editOrder']) || empty($pageData['editOrder']->transporter_id)) ? 'selected' : '' }}>NA</option>
          @foreach($pageData['transportCompanies'] as $t)
            <option value="{{ $t['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->transporter_id == $t['id']) ? 'selected' : '' }}>
              {{ $t['name'] }}
            </option>
          @endforeach
        </select>
        <div id="transport-details" class="info-preview-box" style="display:none;"></div>
      </div>

      <div class="form-group" style="margin-top:1rem;">
        <label>Dispatch Date</label>
        <input type="date" id="order-due-date" value="{{ !empty($pageData['editOrder']) && $pageData['editOrder']->due_date ? \Carbon\Carbon::parse($pageData['editOrder']->due_date)->format('Y-m-d') : '' }}" style="width:100%; padding:0.65rem 0.8rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>

      <!-- Product Items -->
      <div style="margin-top:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:8px;">
          <h4 style="margin:0;">Products / Goods</h4>
          @if(empty($isReadOnly))
          <button type="button" class="btn btn-sm" onclick="app.addOrderProductRow()" style="width:auto; padding:0.4rem 0.8rem; font-weight:bold;">+ Add Product</button>
          @endif
        </div>

        <div id="order-products"></div>
      </div>

      <div class="form-group" style="margin-top:1.5rem;">
        <label>Notes / Special Instructions <span style="color:#ef4444; font-weight:bold;">*</span></label>
        <textarea id="order-notes" rows="2" placeholder="Write instructions or notes here (mandatory)..." required oninput="this.style.borderColor=''">{{ !empty($pageData['editOrder']) ? $pageData['editOrder']->notes : '' }}</textarea>
      </div>

      @if(empty($isReadOnly))
      <button class="btn" onclick="app.submitOrder()" style="padding:1rem; font-size:1.1rem; margin-top:1rem;">
        {{ !empty($pageData['editOrder']) ? 'Update Sales Order' : 'Generate Sales Order' }}
      </button>
      @endif
    </div>
  </div>

  <!-- Tab 2: Company -->
  <div id="sales-tab-company" class="sales-tab-content animation-fadeIn" style="display:none;">
    <!-- Registered Companies List Table -->
    <div class="card sales-action-table-card">
      <div class="sales-action-header-toolbar">
        <div class="sales-action-table-title">
          <span>🏢 Registered Companies</span>
          <span class="sales-action-count-pill">{{ count($pageData['companies']) }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <input type="text" class="sales-action-search-box" placeholder="Search company, GST, city..." oninput="filterActionCompaniesTable(this)">
          @if(empty($isReadOnly))
          <button type="button" class="sales-action-btn-add" onclick="openCompanyModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            ADD COMPANY
          </button>
          @endif
        </div>
      </div>
      <div class="sales-data-table-container">
        <table class="sales-data-table">
          <colgroup>
            <col style="width: 50px;">
            <col style="width: 230px;">
            <col style="width: 140px;">
            <col style="width: 90px;">
            <col style="width: 140px;">
            <col style="width: 230px;">
            <col style="width: 190px;">
          </colgroup>
          <thead>
            <tr>
              <th class="col-idx">#</th>
              <th class="col-comp-name">Company Name</th>
              <th class="col-comp-gst">GST No.</th>
              <th class="col-comp-pin">Pincode</th>
              <th class="col-comp-contact">Contact</th>
              <th class="col-comp-address">Address</th>
              <th class="col-comp-action">Action</th>
            </tr>
          </thead>
          <tbody id="action-companies-tbody">
            @forelse($pageData['companies'] as $idx => $comp)
              @php
                $isCompUsed = !empty($comp['is_used']);
              @endphp
              <tr>
                <td class="col-idx">{{ $idx + 1 }}</td>
                <td class="col-comp-name action-comp-name">
                  {{ $comp['name'] }}
                  @if($isCompUsed)
                    <span class="sales-in-use-badge" title="Linked to existing orders or dispatches">IN USE</span>
                  @endif
                </td>
                <td class="col-comp-gst">{{ $comp['gst'] ?: 'N/A' }}</td>
                <td class="col-comp-pin">{{ $comp['pincode'] ?: '—' }}</td>
                <td class="col-comp-contact">{{ $comp['contact'] ?: '—' }}</td>
                <td class="col-comp-address" title="{{ $comp['address'] }}">{{ $comp['address'] ?: '—' }}</td>
                <td class="col-comp-action">
                  @if(empty($isReadOnly))
                  <div class="sales-action-btn-group">
                    <button type="button" class="sales-btn-edit" onclick="openCompanyModal({{ json_encode($comp) }})" title="Edit company details">
                      ✏️ Edit
                    </button>
                    @if($isCompUsed)
                      <button type="button" class="sales-btn-delete-disabled" disabled title="Cannot delete: this company is linked to existing orders/dispatches (Only Edit allowed)">
                        🗑️ Delete
                      </button>
                    @else
                      <button type="button" class="sales-btn-delete" onclick="deleteCompany({{ $comp['id'] }}, '{{ addslashes($comp['name']) }}')" title="Delete company">
                        🗑️ Delete
                      </button>
                    @endif
                  </div>
                  @else
                    <span style="font-size:0.8rem; color:var(--text-muted);">View Only</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.9rem;">No companies registered yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab 3: Transport -->
  <div id="sales-tab-transport" class="sales-tab-content animation-fadeIn" style="display:none;">
    <!-- Registered Transporters List Table -->
    <div class="card sales-action-table-card">
      <div class="sales-action-header-toolbar">
        <div class="sales-action-table-title">
          <span>🚚 Registered Transporters</span>
          <span class="sales-action-count-pill">{{ count($pageData['transportCompanies']) }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <input type="text" class="sales-action-search-box" placeholder="Search transporter, GST..." oninput="filterActionTransportersTable(this)">
          @if(empty($isReadOnly))
          <button type="button" class="sales-action-btn-add" onclick="app.openAddTransportModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            ADD TRANSPORT
          </button>
          @endif
        </div>
      </div>
      <div class="sales-data-table-container">
        <table class="sales-data-table">
          <colgroup>
            <col style="width: 50px;">
            <col style="width: 250px;">
            <col style="width: 140px;">
            <col style="width: 150px;">
            <col style="width: 240px;">
            <col style="width: 190px;">
          </colgroup>
          <thead>
            <tr>
              <th class="col-idx">#</th>
              <th class="col-name">Transporter Name</th>
              <th class="col-gst">GST No.</th>
              <th class="col-contact">Contact</th>
              <th class="col-vehicles">Vehicles</th>
              <th class="col-action">Action</th>
            </tr>
          </thead>
          <tbody id="action-transporters-tbody">
            @forelse($pageData['transportCompanies'] as $idx => $trans)
              @php
                $isTransUsed = !empty($trans['is_used']);
              @endphp
              <tr>
                <td class="col-idx">{{ $idx + 1 }}</td>
                <td class="col-name action-trans-name">
                  {{ $trans['name'] }}
                  @if($isTransUsed)
                    <span class="sales-in-use-badge" title="Linked to existing orders or dispatches">IN USE</span>
                  @endif
                </td>
                <td class="col-gst">{{ $trans['gst'] ?: 'N/A' }}</td>
                <td class="col-contact">{{ $trans['contact'] ?: '—' }}</td>
                <td class="col-vehicles">{{ $trans['vehicles'] ?: '—' }}</td>
                <td class="col-action">
                  @if(empty($isReadOnly))
                  <div class="sales-action-btn-group">
                    <button type="button" class="sales-btn-edit" onclick="app.editTransporterPrompt({{ json_encode($trans) }})" title="Edit transporter details">
                      ✏️ Edit
                    </button>
                    @if($isTransUsed)
                      <button type="button" class="sales-btn-delete-disabled" disabled title="Cannot delete: this transporter is linked to existing orders/dispatches (Only Edit allowed)">
                        🗑️ Delete
                      </button>
                    @else
                      <button type="button" class="sales-btn-delete" onclick="deleteTransporter({{ $trans['id'] }}, '{{ addslashes($trans['name']) }}')" title="Delete transporter">
                        🗑️ Delete
                      </button>
                    @endif
                  </div>
                  @else
                    <span style="font-size:0.8rem; color:var(--text-muted);">View Only</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.9rem;">No transporters registered yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  window.serverPageData = @json($pageData);
  window.currentFinProds = @json($pageData['products']);

  function switchSalesTab(tab, btnEl) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    btnEl.classList.add('active');

    document.querySelectorAll('.sales-tab-content').forEach(content => content.style.display = 'none');
    document.getElementById('sales-tab-' + tab).style.display = 'block';
  }

  function filterActionCompaniesTable(input) {
    const q = (input.value || '').trim().toUpperCase();
    const rows = document.querySelectorAll('#action-companies-tbody tr');
    rows.forEach(tr => {
      const text = tr.innerText.toUpperCase();
      tr.style.display = (!q || text.indexOf(q) > -1) ? '' : 'none';
    });
  }

  function filterActionTransportersTable(input) {
    const q = (input.value || '').trim().toUpperCase();
    const rows = document.querySelectorAll('#action-transporters-tbody tr');
    rows.forEach(tr => {
      const text = tr.innerText.toUpperCase();
      tr.style.display = (!q || text.indexOf(q) > -1) ? '' : 'none';
    });
  }

  function openCompanyModal(comp = null) {
    const isEdit = comp && comp.id;
    const title = isEdit ? '✏️ Edit Company' : '🏢 Add New Company';
    const compName = comp ? (comp.name || '') : '';
    const compGst = comp ? (comp.gst || '') : '';
    const isUnregistered = comp && (!comp.gst || comp.gst.toUpperCase() === 'N/A');
    const compAddress = comp ? (comp.address || '') : '';
    const compPincode = comp ? (comp.pincode || '') : '';
    
    let rawContact = comp ? (comp.contact || '') : '';
    let countryCode = '+91';
    let phoneNum = rawContact;
    if (rawContact) {
      const match = rawContact.match(/^(\+\d{1,4})\s*(.*)$/);
      if (match) {
        countryCode = match[1];
        phoneNum = match[2];
      }
    }

    Swal.fire({
      title: `<span style="font-size:1.2rem; font-weight:700; color:var(--text-main, #333);">${title}</span>`,
      html: `
        <div style="text-align:left; font-size:0.88rem; max-height:70vh; overflow-y:auto; padding:4px 2px;">
          ${isEdit ? `<input type="hidden" id="swal-comp-id" value="${comp.id}">` : ''}
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Company Name *</label>
            <input id="swal-comp-name" class="swal2-input" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${compName}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Company Type *</label>
            <select id="swal-comp-type" onchange="document.getElementById('swal-comp-gst-group').style.display = (this.value==='unregistered' ? 'none' : 'block')" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px; border:1px solid #d1d5db; background:#fff; color:#333;">
              <option value="registered" ${!isUnregistered ? 'selected' : ''}>Registered Company</option>
              <option value="unregistered" ${isUnregistered ? 'selected' : ''}>Un-Registered Company</option>
            </select>
          </div>
          <div id="swal-comp-gst-group" style="margin-bottom:12px; display:${isUnregistered ? 'none' : 'block'};">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">GST Number *</label>
            <input id="swal-comp-gst" class="swal2-input" maxlength="15" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; text-transform:uppercase; box-sizing:border-radius:6px;" value="${compGst}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Address *</label>
            <textarea id="swal-comp-address" class="swal2-textarea" rows="2" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;">${compAddress}</textarea>
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Pincode *</label>
            <input id="swal-comp-pincode" class="swal2-input" maxlength="6" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${compPincode}">
          </div>
          <div style="margin-bottom:8px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Contact / Mobile No *</label>
            <div style="display:flex; gap:8px;">
              <input id="swal-comp-code" class="swal2-input" value="${countryCode}" oninput="if(this.value.trim()==='+91'){ document.getElementById('swal-comp-phone').setAttribute('maxlength','10'); document.getElementById('swal-comp-phone').value = document.getElementById('swal-comp-phone').value.replace(/\\D/g,'').slice(0,10); } else { document.getElementById('swal-comp-phone').removeAttribute('maxlength'); }" style="width:75px; margin:0; padding:0.6rem 0.4rem; font-size:0.9rem; font-weight:600; text-align:center; box-sizing:border-box; border-radius:6px; flex-shrink:0;">
              <input id="swal-comp-phone" class="swal2-input" oninput="if(document.getElementById('swal-comp-code').value.trim()==='+91'){ this.value = this.value.replace(/\\D/g,'').slice(0,10); }" ${countryCode === '+91' ? 'maxlength="10"' : ''} style="flex:1; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${phoneNum}">
            </div>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: isEdit ? 'Update Company' : 'Save Company',
      confirmButtonColor: '#f59e0b',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      preConfirm: () => {
        const name = (document.getElementById('swal-comp-name').value || '').trim();
        const type = document.getElementById('swal-comp-type').value;
        const gst = (document.getElementById('swal-comp-gst').value || '').trim().toUpperCase();
        const address = (document.getElementById('swal-comp-address').value || '').trim();
        const pincode = (document.getElementById('swal-comp-pincode').value || '').trim();
        const code = (document.getElementById('swal-comp-code').value || '').trim();
        const rawPhone = (document.getElementById('swal-comp-phone').value || '').trim();

        if (!name) { Swal.showValidationMessage('Company Name is required'); return false; }
        if (type === 'registered') {
          if (!gst) { Swal.showValidationMessage('GST Number is mandatory for Registered Company'); return false; }
          if (!/^[A-Za-z0-9]{15}$/.test(gst)) { Swal.showValidationMessage('GST must be exactly 15 alphanumeric characters'); return false; }
        }
        if (!address) { Swal.showValidationMessage('Address is required'); return false; }
        if (!pincode || !/^[0-9]{6}$/.test(pincode)) { Swal.showValidationMessage('Pincode must be exactly 6 digits'); return false; }
        if (!rawPhone) { Swal.showValidationMessage('Contact / Mobile number is required'); return false; }
        if (code === '+91') {
          const cleanDigits = rawPhone.replace(/\D/g, '');
          const isLandline = /^0?79[\s\-]?[0-9]{6,8}$/.test(rawPhone);
          if (!isLandline && cleanDigits.length !== 10) {
            Swal.showValidationMessage('Contact number must be exactly 10 digits for +91');
            return false;
          }
        }

        const contact = code ? (code + ' ' + rawPhone) : rawPhone;
        return { name, gst: type === 'unregistered' ? 'N/A' : gst, address, pincode, contact, isEdit };
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        if (window.isReadOnly) return app.toast('You have View-Only permission. Saving company is disabled.', 'error');
        
        const payload = result.value;
        const isEditId = payload.isEdit ? comp.id : null;
        const url = isEditId ? `${app.getSalesPrefix()}/company/${isEditId}` : `${app.getSalesPrefix()}/company`;

        fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken || csrfToken
          },
          body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            app.toast(d.message || 'Company saved!');
            sessionStorage.setItem('activeSalesTab', 'company');
            setTimeout(() => location.reload(), 600);
          } else {
            app.toast(d.message || 'Failed to save company', 'error');
          }
        })
        .catch(() => app.toast('Network error saving company', 'error'));
      }
    });
  }

  function deleteCompany(id, name) {
    if (window.isReadOnly) return app.toast('You have View-Only permission. Deleting company is disabled.', 'error');

    Swal.fire({
      title: 'Delete Company?',
      text: `Are you sure you want to delete company "${name}"?`,
      icon: 'warning',
      background: 'var(--dark-panel, #1e293b)',
      color: 'var(--text-main, #fff)',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#30363d',
    }).then(result => {
      if (!result.isConfirmed) return;

      const url = `${app.getSalesPrefix()}/company/${id}`;
      fetch(url, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window.csrfToken || csrfToken
        }
      })
      .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (r.ok && d.success) {
          app.toast(d.message || 'Company deleted!');
          sessionStorage.setItem('activeSalesTab', 'company');
          setTimeout(() => location.reload(), 600);
        } else {
          Swal.fire({
            title: 'Cannot Delete Company',
            text: d.message || 'Company cannot be deleted because it is linked to existing orders or dispatches.',
            icon: 'warning',
            background: 'var(--dark-panel, #1e293b)',
            color: 'var(--text-main, #fff)',
            confirmButtonColor: 'var(--primary, #D88A00)'
          });
        }
      })
      .catch(() => app.toast('Network error deleting company', 'error'));
    });
  }

  function deleteTransporter(id, name) {
    if (window.isReadOnly) return app.toast('You have View-Only permission. Deleting transporter is disabled.', 'error');

    Swal.fire({
      title: 'Delete Transporter?',
      text: `Are you sure you want to delete transporter "${name}"?`,
      icon: 'warning',
      background: 'var(--dark-panel, #1e293b)',
      color: 'var(--text-main, #fff)',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#30363d',
    }).then(result => {
      if (!result.isConfirmed) return;

      const url = `${app.getSalesPrefix()}/transport/${id}`;
      fetch(url, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': window.csrfToken || csrfToken
        }
      })
      .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (r.ok && d.success) {
          app.toast(d.message || 'Transporter deleted!');
          sessionStorage.setItem('activeSalesTab', 'transport');
          setTimeout(() => location.reload(), 600);
        } else {
          Swal.fire({
            title: 'Cannot Delete Transporter',
            text: d.message || 'Transporter cannot be deleted because it is linked to existing orders or dispatches.',
            icon: 'warning',
            background: 'var(--dark-panel, #1e293b)',
            color: 'var(--text-main, #fff)',
            confirmButtonColor: 'var(--primary, #D88A00)'
          });
        }
      })
      .catch(() => app.toast('Network error deleting transporter', 'error'));
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const savedTab = sessionStorage.getItem('activeSalesTab');
    if (savedTab) {
      sessionStorage.removeItem('activeSalesTab');
      const tabBtns = document.querySelectorAll('.tab-btn');
      if (savedTab === 'company' && tabBtns[1]) switchSalesTab('company', tabBtns[1]);
      if (savedTab === 'transport' && tabBtns[2]) switchSalesTab('transport', tabBtns[2]);
    }

    @if(!empty($pageData['editCompany']))
      const btn = document.querySelector('.tab-btn:nth-child(2)');
      if (btn) switchSalesTab('company', btn);
      openCompanyModal(@json($pageData['editCompany']));
    @endif

    @if(empty($pageData['editOrder']))
      window.currentOrderType = 'ALL';
      window.currentFinProds = (window.serverPageData && window.serverPageData.products) || [];
      const prodList = document.getElementById('order-products');
      if (prodList && prodList.children.length === 0) {
        app.addOrderProductRow();
      }
    @endif
  });
</script>

@if(!empty($pageData['editOrder']))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const compVal = "{{ $pageData['editOrder']->company_id }}";
    const transVal = "{{ $pageData['editOrder']->transporter_id }}";
    if (compVal) {
      document.getElementById('order-company').value = compVal;
      app.onSalesCompanySelect(compVal);
    }
    if (transVal) {
      document.getElementById('order-transport').value = transVal;
      app.onSalesTransportSelect(transVal);
    }

    const prodList = document.getElementById('order-products');
    if (prodList) {
      prodList.innerHTML = '';
      const editOrder = (window.serverPageData && window.serverPageData.editOrder) || {};
      if (editOrder && editOrder.items) {
        editOrder.items.forEach(item => {
          app.addOrderProductRow(item);
        });
      }
    }
  });
</script>
@endif
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const prodList = document.getElementById('order-products');
    if (prodList && prodList.children.length === 0) {
      app.addOrderProductRow();
    }
  });
</script>
@endsection