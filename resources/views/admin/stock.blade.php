@extends('layouts.admin')

@section('content')

@php
  $typeFilter = request('type') ? strtoupper(request('type')) : null;

  $sortStockByLow = function($collection) {
      return $collection->sort(function($a, $b) {
          $hasQtyA = (float) ($a->quantity ?? 0) > 0;
          $alertA = (float) ($a->alert_limit ?? 0);
          $isLowA = $alertA > 0 && (float) ($a->quantity ?? 0) <= $alertA;
          $prioA = ($isLowA && $hasQtyA) ? 0 : ($isLowA ? 1 : 2);

          $hasQtyB = (float) ($b->quantity ?? 0) > 0;
          $alertB = (float) ($b->alert_limit ?? 0);
          $isLowB = $alertB > 0 && (float) ($b->quantity ?? 0) <= $alertB;
          $prioB = ($isLowB && $hasQtyB) ? 0 : ($isLowB ? 1 : 2);

          if ($prioA !== $prioB) {
              return $prioA <=> $prioB;
          }

          $sortA = isset($a->sort_order) ? (int)$a->sort_order : 9999;
          $sortB = isset($b->sort_order) ? (int)$b->sort_order : 9999;
          if ($sortA !== $sortB) {
              return $sortA <=> $sortB;
          }

          return strcasecmp($a->name ?? '', $b->name ?? '');
      })->values();
  };

  $rawItems       = $sortStockByLow(collect($pageData['allStock'])->where('stage', 'RAW'));
  $semiItems      = $sortStockByLow(collect($pageData['allStock'])->where('stage', 'SEMI'));
  $finishedItems  = $sortStockByLow(collect($pageData['allStock'])->where('stage', 'FINISHED'));
  $packagingItems = $sortStockByLow(collect($pageData['allStock'])->where('stage', 'PACKAGING'));
  $adminAllGrades = \App\Models\Grade::orderBy('id')->get();
@endphp

<div style="padding:0.25rem 0 1rem 0;">
  <!-- Modern Redesigned Stock Header -->
  <div class="stock-page-header">
    <div class="stock-header-left">
      <div class="stock-breadcrumb">
        <span>INVENTORY</span>
        <span class="stock-bc-sep">/</span>
        <span class="stock-bc-current">
          @if(!$typeFilter)
            ALL STAGES
          @elseif($typeFilter === 'FINISHED' || $typeFilter === 'FG')
            FINISHED GOODS (FG)
          @elseif($typeFilter === 'RAW')
            RAW MATERIALS (RAW)
          @elseif($typeFilter === 'SEMI')
            SEMI-FINISHED (SEMI)
          @elseif($typeFilter === 'PACKAGING' || $typeFilter === 'PKG')
            PACKAGING MATERIAL (PKG)
          @endif
        </span>
      </div>
      <div class="stock-header-title-wrap">
        <h2 class="stock-header-title">
          @if($typeFilter === 'FINISHED' || $typeFilter === 'FG')
            ✅ Finished Goods Stock
          @elseif($typeFilter === 'RAW')
            🌿 Raw Materials Stock
          @elseif($typeFilter === 'SEMI')
            ⚗️ Semi-Finished Stock
          @elseif($typeFilter === 'PACKAGING' || $typeFilter === 'PKG')
            📦 Packaging Materials Stock
          @else
            📦 Live Stock Overview
          @endif
        </h2>
        <span class="live-status-pill">
          <span class="pulse-dot"></span> Live
        </span>
      </div>
    </div>
    <div class="stock-header-actions">
      <button type="button" class="btn btn-secondary stock-action-btn-secondary stock-btn-csv" onclick="adminExportStockCsv()" title="Export CSV report for stock" style="background:#10b981 !important; color:#ffffff !important; border-color:#059669 !important; font-weight:700 !important; display:inline-flex; align-items:center; gap:6px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Export CSV
      </button>
      <button type="button" class="btn btn-secondary stock-action-btn-secondary" onclick="adminExportStockPdf()" title="Export PDF report for selected stages">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        Generate PDF Report
      </button>
      <button type="button" class="btn stock-action-btn-primary" onclick="toggleStockFormCard()" title="Add or record incoming stock">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        + Add Stock
      </button>
    </div>
  </div>

  <!-- In-Page Add / Adjust Stock Card (Hidden by Default) -->
  <div id="stock-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
      <span style="font-size:1.15rem; font-weight:700;">📦 Add Stock Entry</span>
      <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('stock-form-card').style.display='none'" style="width:auto; padding:0.3rem 0.8rem;">✕ Close</button>
    </div>

    <div id="stock-rows-wrapper">
        <div class="bulk-stock-row" id="single-stock-row" style="padding: 1rem; margin-bottom: 1rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;">
        <div class="bulk-stock-fields">
            
            <div class="form-group bs-col-date">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">📅 Date *</label>
                <input type="date" class="form-control form-control-sm bs-date" value="{{ date('Y-m-d') }}" style="height:1.8rem; padding:0.1rem 0.4rem; font-size:0.8rem; font-weight:500; width:100%;">
            </div>

            <div class="form-group bs-col-stage">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Stock Type *</label>
                <select class="form-control form-control-sm bs-stage" onchange="onBsStageChange(this)" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; width:100%;">
                    <option value="ALL" selected>ALL</option>
                    <option value="RAW">RAW</option>
                    <option value="SEMI">SEMI</option>
                    <option value="FINISHED">FG</option>
                    <option value="PACKAGING">PACKAGING</option>
                </select>
            </div>
            
            <div class="form-group bs-col-product">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Product *</label>
                <select class="form-control form-control-sm bs-product" onchange="onBsProductChange(this)" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; font-weight:600; width:100%; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#333;">
                    <option value="" disabled selected>SELECT PRODUCT...</option>
                </select>
            </div>

            <div class="bs-location-row bs-col-location" style="display:flex; gap:0.4rem; align-items:flex-end;">
                <div class="form-group" style="margin:0; flex:1.6; min-width:0; position:relative;">
                    <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Storage Location *</label>
                    <div class="custom-location-dropdown" style="width: 100%; position: relative;">
                        <button class="btn" type="button" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'" style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center; background:#fff; border: 1px solid #d1d5db; height:1.8rem; padding: 0.1rem 0.5rem; font-size:0.8rem; color:#333; cursor:pointer;">
                            <span class="loc-dropdown-text" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Main Warehouse</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <ul class="dropdown-menu p-2 shadow" style="display:none; position:absolute; top:100%; left:0; z-index:1000; width: 220px; max-height:250px; overflow-y:auto; background:#fff; border:1px solid #d1d5db; border-radius:0.25rem; list-style:none; margin-top:0.125rem;">
                            @php $allLocs = \App\Models\Location::orderBy('name')->get(); @endphp
                            
                            <li style="margin-bottom:0.5rem; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; font-weight:600; color:var(--primary-dark);">
                                <span style="padding-left: 0.2rem;">MAIN WAREHOUSE</span>
                                <input type="number" min="0" step="0.001" class="form-control form-control-sm loc-qty-input no-spinners" data-loc="Main Warehouse" style="width: 60px; text-align:center; padding: 0.1rem; height:1.6rem; font-size:0.8rem;" value="0">
                            </li>
                            
                            @foreach($allLocs as $loc)
                                @if($loc->name !== 'Main Warehouse')
                                <li style="margin-bottom:0.5rem; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:#333;">
                                    <span style="padding-left: 0.2rem;">{{ strtoupper($loc->name) }}</span>
                                    <input type="number" min="0" step="0.001" class="form-control form-control-sm loc-qty-input no-spinners" data-loc="{{ $loc->name }}" style="width: 60px; text-align:center; padding: 0.1rem; height:1.6rem; font-size:0.8rem;" value="0">
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="form-group" style="margin:0; flex:1; min-width:65px;">
                    <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Qty *</label>
                    <input type="number" min="0.001" step="0.001" class="form-control form-control-sm bs-loc-qty no-spinners" placeholder="0.00" readonly style="background-color: #f9fafb; height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; width:100%;">
                </div>
            </div>

            <div class="form-group bs-col-minqty">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">MIN.QTY</label>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm bs-min-qty no-spinners" placeholder="0.00" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; width:100%;">
            </div>

            <div class="form-group bs-col-rate">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Rate</label>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm bs-rate no-spinners" placeholder="0.00" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; width:100%;">
            </div>

            <div class="form-group bs-col-note">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Note</label>
                <input type="text" class="form-control form-control-sm bs-note" placeholder="Optional" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem; width:100%;">
            </div>

            <div class="row-actions form-group bs-col-actions" style="margin:0;"></div>
        </div>
    </div>
    </div> <!-- end wrapper -->

    <div style="display:flex; gap:1rem; margin-top:1.5rem; justify-content: space-between; align-items: center;">
      <div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStockRow()" style="width:auto; padding:0.4rem 1rem; border: 1px solid var(--primary); color: var(--primary); background: transparent; font-weight: 600;">+ Add Another Product</button>
      </div>
      <div style="display:flex; gap:1rem;">
        <button class="btn" id="btn-save-stock-card" onclick="adminSaveBulkStock()" style="width:auto; padding:0.6rem 1.8rem;">Save Stock</button>
        <button class="btn btn-secondary" onclick="document.getElementById('stock-form-card').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
      </div>
    </div>
  </div>

  <template id="bulk-stock-location-template">
    <div class="bs-location-row" style="display:flex; gap:0.5rem; align-items:flex-end; margin-bottom:0.5rem;">
        <div class="form-group" style="flex:2; margin:0;">
            <label style="font-size: 0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Location</label>
            <select class="form-control form-control-sm bs-loc-name" style="width:100%; height: 1.8rem; padding: 0.1rem 0.5rem; font-size: 0.8rem;">
                <option value="Main Warehouse" selected>Main Warehouse</option>
                @php $allLocs = \App\Models\Location::orderBy('name')->get(); @endphp
                @foreach($allLocs as $loc)
                    @if($loc->name !== 'Main Warehouse')
                        <option value="{{ $loc->name }}">{{ $loc->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group" style="flex:1; margin:0;">
            <label style="font-size: 0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Qty *</label>
            <input type="number" min="0.001" step="0.001" class="form-control form-control-sm bs-loc-qty no-spinners" placeholder="0.00" oninput="recalcBsTotal(this)" style="height: 1.8rem; padding: 0.1rem 0.5rem; font-size: 0.8rem;">
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeBsLocation(this)" style="padding:0.2rem 0.5rem; height: 1.8rem; background: #dc3545; color:#fff; border:none; font-size: 0.8rem;">X</button>
    </div>
  </template>

  <script>
    const adminAllGrades = {!! json_encode($adminAllGrades) !!};
  </script>
  <style>
    /* Low Stock Styling */
    tbody tr.low-stock-row,
    tbody tr.low-stock-row:hover,
    tbody tr.low-stock-row td,
    tbody tr.low-stock-row:hover td {
        background-color: #dc3545 !important;
    }
    
    tbody tr.low-stock-row td,
    tbody tr.low-stock-row td *,
    tbody tr.low-stock-row td span,
    tbody tr.low-stock-row td div,
    tbody tr.low-stock-row button {
        color: #ffffff !important;
    }
    
    tbody tr.low-stock-row button.btn-icon svg {
        stroke: #ffffff !important;
        fill: none !important;
    }
    
    tbody tr.low-stock-row .btn.btn-sm {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border-color: transparent !important;
    }
    
    tbody tr.low-stock-row .btn.btn-sm:hover {
        background-color: rgba(255, 255, 255, 0.4) !important;
    }

    /* Hide spin arrows on number inputs globally in this context */
    input[type=number].no-spinners::-webkit-outer-spin-button,
    input[type=number].no-spinners::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type=number].no-spinners {
      -moz-appearance: textfield;
    }
    
    /* Stock Table Uniform Column Alignment */
    .table-container table.stock-table {
      width: 100% !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
    }
    .table-container table.stock-table th,
    .table-container table.stock-table td {
      padding: 0.65rem 0.5rem !important;
      vertical-align: middle !important;
      box-sizing: border-box !important;
    }
    .table-container table.stock-table th:nth-child(1),
    .table-container table.stock-table td:nth-child(1) { width: 46% !important; text-align: left !important; }
    .table-container table.stock-table th:nth-child(2),
    .table-container table.stock-table td:nth-child(2) { width: 10% !important; text-align: right !important; }
    .table-container table.stock-table th:nth-child(3),
    .table-container table.stock-table td:nth-child(3) { width: 5% !important; text-align: left !important; }
    .table-container table.stock-table th:nth-child(4),
    .table-container table.stock-table td:nth-child(4) { width: 12% !important; text-align: right !important; }
    .table-container table.stock-table th:nth-child(5),
    .table-container table.stock-table td:nth-child(5) { width: 10% !important; text-align: right !important; }
    .table-container table.stock-table th:nth-child(6),
    .table-container table.stock-table td:nth-child(6) { width: 11% !important; text-align: center !important; }
    .table-container table.stock-table th:nth-child(7),
    .table-container table.stock-table td:nth-child(7) { width: 6% !important; text-align: right !important; }

    .table-container table.stock-table td:nth-child(7) > div {
      justify-content: flex-end !important;
    }

    .btn-icon:disabled,
    .btn-icon.is-disabled {
      opacity: 0.35 !important;
      cursor: not-allowed !important;
      background: rgba(255, 255, 255, 0.03) !important;
      border-color: rgba(255, 255, 255, 0.08) !important;
      color: #6b7280 !important;
      pointer-events: auto !important;
    }
    .btn-icon:disabled:hover,
    .btn-icon.is-disabled:hover {
      background: rgba(255, 255, 255, 0.03) !important;
      border-color: rgba(255, 255, 255, 0.08) !important;
      color: #6b7280 !important;
    }

    tbody tr.low-stock-row button.btn-icon:disabled,
    tbody tr.low-stock-row button.btn-icon.is-disabled {
      opacity: 0.45 !important;
      color: rgba(255, 255, 255, 0.5) !important;
      background: rgba(0, 0, 0, 0.15) !important;
      cursor: not-allowed !important;
    }
    tbody tr.low-stock-row button.btn-icon:disabled svg,
    tbody tr.low-stock-row button.btn-icon.is-disabled svg {
      stroke: rgba(255, 255, 255, 0.5) !important;
    }

    /* Select2 Smart Search Custom Styling - Single Line Product Names */
    .bs-col-product {
      position: relative !important;
      min-width: 0 !important;
      width: 100% !important;
    }
    .bs-col-product .select2-container {
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      display: block !important;
    }
    .bs-col-product .select2-container .select2-selection--single {
      background-color: #ffffff !important;
      border: 1px solid #d1d5db !important;
      height: 1.8rem !important;
      min-height: 1.8rem !important;
      border-radius: 6px !important;
      display: flex !important;
      align-items: center !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      overflow: hidden !important;
      box-sizing: border-box !important;
      transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    }
    .bs-col-product .select2-container--default.select2-container--focus .select2-selection--single,
    .bs-col-product .select2-container--default.select2-container--open .select2-selection--single {
      border-color: #f59e0b !important;
      box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
      outline: none !important;
    }
    .bs-col-product .select2-container .select2-selection--single .select2-selection__rendered {
      color: #111827 !important;
      font-weight: 600 !important;
      font-size: 0.8rem !important;
      line-height: 1.8rem !important;
      padding-left: 0.5rem !important;
      padding-right: 1.5rem !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      display: block !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .bs-col-product .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: #9ca3af !important;
      font-weight: 500 !important;
      font-size: 0.8rem !important;
      white-space: nowrap !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
    }
    .bs-col-product .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 1.8rem !important;
      right: 6px !important;
      top: 0 !important;
      display: flex !important;
      align-items: center !important;
    }
    .bs-col-product .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: #6b7280 transparent transparent transparent !important;
      border-width: 5px 4px 0 4px !important;
    }
    .bs-col-product .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent #6b7280 transparent !important;
      border-width: 0 4px 5px 4px !important;
    }

    /* Select2 Dropdown Styling */
    .bs-product-select2-dropdown.select2-dropdown,
    .select2-dropdown {
      border: 1px solid #d1d5db !important;
      border-radius: 8px !important;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18) !important;
      z-index: 999999 !important;
      background: #ffffff !important;
      overflow: hidden !important;
      box-sizing: border-box !important;
    }
    .bs-product-select2-dropdown.select2-dropdown {
      min-width: min(340px, calc(100vw - 24px)) !important;
      max-width: min(580px, calc(100vw - 24px)) !important;
    }
    .bs-product-select2-dropdown .select2-search--dropdown {
      padding: 8px !important;
      background: #f9fafb !important;
      border-bottom: 1px solid #e5e7eb !important;
    }
    .bs-product-select2-dropdown .select2-search--dropdown .select2-search__field {
      border: 1px solid #d1d5db !important;
      border-radius: 6px !important;
      padding: 0.4rem 0.65rem !important;
      font-size: 0.82rem !important;
      outline: none !important;
      background: #ffffff !important;
      color: #111827 !important;
      width: 100% !important;
      box-sizing: border-box !important;
      transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    }
    .bs-product-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
      border-color: #f59e0b !important;
      box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25) !important;
    }
    .bs-product-select2-dropdown .select2-results__options {
      max-height: 250px !important;
      overflow-y: auto !important;
      overflow-x: hidden !important;
      padding: 4px 0 !important;
    }
    .bs-product-select2-dropdown .select2-results__option {
      font-size: 0.82rem !important;
      padding: 0.5rem 0.75rem !important;
      color: #111827 !important;
      line-height: 1.35 !important;
      border-bottom: 1px solid #f3f4f6 !important;
      cursor: pointer !important;
      background-color: #ffffff !important;
      transition: background-color 0.1s ease, color 0.1s ease !important;
    }
    .bs-product-select2-dropdown .select2-results__option:last-child {
      border-bottom: none !important;
    }

    /* Selected state when not hovered */
    .bs-product-select2-dropdown .select2-results__option[aria-selected="true"] {
      background-color: #fef9c3 !important;
      border-left: 3px solid #eab308 !important;
    }
    .bs-product-select2-dropdown .select2-results__option[aria-selected="true"] .prod-name {
      color: #854d0e !important;
      font-weight: 700 !important;
    }

    /* Hovered / Highlighted state (always sharp and high contrast!) */
    .bs-product-select2-dropdown .select2-results__option--highlighted,
    .bs-product-select2-dropdown .select2-results__option--highlighted[aria-selected],
    .bs-product-select2-dropdown .select2-results__option--highlighted[aria-selected="true"],
    .bs-product-select2-dropdown .select2-results__option--highlighted[aria-selected="false"],
    .bs-product-select2-dropdown .select2-results__option:hover {
      background-color: #fef08a !important;
      color: #000000 !important;
      border-left: 3px solid #ca8a04 !important;
    }
    .bs-product-select2-dropdown .select2-results__option--highlighted .prod-name,
    .bs-product-select2-dropdown .select2-results__option:hover .prod-name {
      color: #000000 !important;
      font-weight: 700 !important;
      background: transparent !important;
    }
    .bs-product-select2-dropdown .select2-results__option--highlighted .prod-unit,
    .bs-product-select2-dropdown .select2-results__option:hover .prod-unit {
      color: #1f2937 !important;
      font-weight: 600 !important;
    }
    .bs-product-select2-dropdown .select2-results__option--highlighted .prod-grade-badge,
    .bs-product-select2-dropdown .select2-results__option:hover .prod-grade-badge {
      background-color: #ffffff !important;
      color: #3730a3 !important;
      border: 1px solid #c7d2fe !important;
      font-weight: 700 !important;
    }
    .bs-product-select2-dropdown .select2-results__option--highlighted .prod-stage-badge,
    .bs-product-select2-dropdown .select2-results__option:hover .prod-stage-badge {
      border-color: rgba(0, 0, 0, 0.25) !important;
      background-color: #ffffff !important;
      font-weight: 700 !important;
    }

    /* Responsive Bulk Stock Fields Grid */
    .bulk-stock-fields {
      display: grid;
      grid-template-columns: 120px 85px minmax(200px, 2.5fr) minmax(220px, 1.8fr) 75px 75px minmax(95px, 1fr) auto;
      grid-template-areas: "date stage product location minqty rate note actions";
      gap: 0.5rem;
      align-items: flex-end;
      width: 100%;
      box-sizing: border-box;
    }

    .bulk-stock-fields > .form-group,
    .bulk-stock-fields > .bs-location-row {
      margin: 0 !important;
      min-width: 0 !important;
    }

    .bs-col-date     { grid-area: date; }
    .bs-col-stage    { grid-area: stage; }
    .bs-col-product  { grid-area: product; min-width: 0 !important; }
    .bs-col-location { grid-area: location; min-width: 0 !important; }
    .bs-col-minqty   { grid-area: minqty; }
    .bs-col-rate     { grid-area: rate; }
    .bs-col-note     { grid-area: note; min-width: 0 !important; }
    .bs-col-actions  { grid-area: actions; display: flex; align-items: flex-end; }

    .bulk-stock-row {
      position: relative;
    }

    /* Tablet & Smaller Desktop Screens (<= 1280px) */
    @media (max-width: 1280px) and (min-width: 641px) {
      .bulk-stock-fields {
        grid-template-columns: 130px 100px 1fr 1fr 85px 85px 1fr auto;
        grid-template-areas:
          "date stage product product product product product product"
          "location location minqty rate note note note actions";
        gap: 0.65rem 0.5rem;
      }
    }

    /* Mobile Screens (<= 640px) */
    @media (max-width: 640px) {
      .bulk-stock-fields {
        grid-template-columns: 1fr 1fr;
        grid-template-areas:
          "date stage"
          "product product"
          "location location"
          "minqty rate"
          "note note"
          "actions actions";
        gap: 0.5rem;
      }
    }

    /* All Product Names in 1 line in tables */
    .stock-table td:first-child,
    .stock-table th:first-child,
    .stock-table tbody td:first-child div {
      white-space: nowrap !important;
    }

    /* ── REDESIGNED STOCK UI STYLES ─────────────────────────────────────── */
    .stock-page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }
    .stock-breadcrumb {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      color: var(--text-muted, #64748b);
      margin-bottom: 4px;
    }
    .stock-bc-sep {
      color: var(--border-color, #cbd5e1);
    }
    .stock-bc-current {
      color: var(--primary-light, #8a5a00);
    }
    .stock-header-title-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .stock-header-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-main, #0f172a);
      margin: 0;
      letter-spacing: -0.02em;
    }
    .live-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
    }
    .pulse-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
      animation: pulseLiveDot 2s infinite ease-in-out;
    }
    @keyframes pulseLiveDot {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.3); opacity: 0.6; }
    }
    .stock-header-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .stock-action-btn-secondary {
      width: auto !important;
      padding: 0.6rem 1.15rem !important;
      border-radius: 10px !important;
      font-size: 0.85rem !important;
      font-weight: 600 !important;
      border: 1px solid #DDCFAF !important;
      background: #ffffff !important;
      color: #5A4A2A !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
      transition: all 0.15s ease !important;
    }
    .stock-action-btn-secondary:hover {
      background: #F8F6F1 !important;
      transform: translateY(-1px) !important;
    }
    .stock-action-btn-primary {
      width: auto !important;
      padding: 0.6rem 1.25rem !important;
      border-radius: 10px !important;
      font-size: 0.85rem !important;
      font-weight: 700 !important;
      background: var(--primary, #f59e0b) !important;
      color: var(--dark-brand, #2b241c) !important;
      border: none !important;
      box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25) !important;
      transition: all 0.15s ease !important;
    }
    .stock-action-btn-primary:hover {
      background: var(--primary-hover, #d97706) !important;
      transform: translateY(-1px) !important;
      box-shadow: 0 4px 10px rgba(245, 158, 11, 0.35) !important;
    }

    /* ── SEGMENTED NAVIGATION PILLS BAR ────────────────────────────────── */
    .stock-nav-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
      width: 100%;
    }
    .stock-tabs-container {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px;
      background: var(--bg-hover, #f1f5f9);
      border: 1px solid var(--border-soft, #e2e8f0);
      border-radius: 12px;
      max-width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .stock-tabs-container::-webkit-scrollbar { display: none; }

    .stock-tab {
      display: inline-flex !important;
      align-items: center !important;
      gap: 7px !important;
      padding: 7px 15px !important;
      font-size: 0.82rem !important;
      font-weight: 700 !important;
      color: var(--text-secondary, #475569) !important;
      background: transparent !important;
      border-radius: 8px !important;
      text-decoration: none !important;
      white-space: nowrap !important;
      width: auto !important;
      flex: 0 0 auto !important;
      border: 1px solid transparent !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      user-select: none !important;
    }
    .stock-tab:hover {
      background: #ffffff !important;
      color: var(--text-main, #0f172a) !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important;
    }
    .stock-tab .stock-tab-badge {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      padding: 1px 7px !important;
      border-radius: 999px !important;
      font-size: 0.72rem !important;
      font-weight: 700 !important;
      background: #ffffff !important;
      color: #64748b !important;
      border: 1px solid #cbd5e1 !important;
      line-height: 1.25 !important;
    }

    /* Active Tab Themes */
    .stock-tab.active {
      color: #ffffff !important;
    }
    .stock-tab.active .stock-tab-badge {
      background: rgba(255, 255, 255, 0.25) !important;
      color: #ffffff !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
    }
    .stock-tab.active.is-all {
      background: #1e293b !important;
      box-shadow: 0 2px 6px rgba(30, 41, 59, 0.35) !important;
    }
    .stock-tab.active.is-raw {
      background: #059669 !important;
      box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35) !important;
    }
    .stock-tab.active.is-semi {
      background: #d97706 !important;
      box-shadow: 0 2px 8px rgba(217, 119, 6, 0.35) !important;
    }
    .stock-tab.active.is-fg {
      background: #2563eb !important;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4) !important;
    }
    .stock-tab.active.is-pkg {
      background: #0284c7 !important;
      box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35) !important;
    }

    /* Quick Action Button for Low Stock */
    .stock-quick-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .stock-low-toggle-btn {
      display: inline-flex !important;
      align-items: center !important;
      gap: 7px !important;
      padding: 0.55rem 1rem !important;
      font-size: 0.82rem !important;
      font-weight: 700 !important;
      border-radius: 8px !important;
      border: 1.5px solid #dc2626 !important;
      background: #ffffff !important;
      color: #dc2626 !important;
      cursor: pointer !important;
      width: auto !important;
      flex: 0 0 auto !important;
      white-space: nowrap !important;
      transition: all 0.15s ease !important;
      box-shadow: 0 1px 3px rgba(220, 38, 38, 0.08) !important;
      user-select: none !important;
    }
    .stock-low-toggle-btn:hover {
      background: #fef2f2 !important;
      border-color: #b91c1c !important;
      color: #b91c1c !important;
    }
    .stock-low-toggle-btn.is-active {
      background: #dc2626 !important;
      color: #ffffff !important;
      border-color: #b91c1c !important;
      box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35) !important;
    }
    .stock-low-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #dc2626;
      color: #ffffff;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 1px 7px;
      border-radius: 999px;
      min-width: 18px;
    }
    .stock-low-toggle-btn.is-active .stock-low-badge {
      background: #ffffff;
      color: #dc2626;
    }

    /* ── INTEGRATED SEARCH & INFO TOOLBAR ──────────────────────────────── */
    .stock-toolbar-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.85rem;
      flex-wrap: wrap;
      padding: 0.65rem 0.85rem;
      margin-bottom: 1.25rem;
      background: var(--bg-card, #ffffff);
      border: 1px solid var(--border-soft, #e2e8f0);
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .stock-search-wrap {
      position: relative;
      flex: 1;
      display: flex;
      align-items: center;
      min-width: 240px;
    }
    .stock-search-icon {
      position: absolute;
      left: 12px;
      color: var(--text-muted, #94a3b8);
      pointer-events: none;
    }
    .stock-search-wrap input {
      width: 100% !important;
      padding: 0.55rem 2.2rem 0.55rem 2.4rem !important;
      border-radius: 8px !important;
      font-size: 0.87rem !important;
      border: 1px solid var(--border-soft, #d1d5db) !important;
      background: var(--bg-hover, #f8fafc) !important;
      color: var(--text-main, #1e293b) !important;
      outline: none !important;
      transition: all 0.15s ease !important;
    }
    .stock-search-wrap input:focus {
      border-color: #2563eb !important;
      background: #ffffff !important;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
    }
    .stock-search-clear-btn {
      position: absolute;
      right: 10px;
      background: transparent;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      display: none;
    }
    .stock-search-clear-btn:hover {
      color: #dc2626;
      background: #fee2e2;
    }
    .stock-toolbar-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-shrink: 0;
    }
    .stock-count-chip {
      font-size: 0.8rem;
      color: var(--text-secondary, #64748b);
      background: var(--bg-hover, #f1f5f9);
      padding: 4px 10px;
      border-radius: 6px;
      border: 1px solid var(--border-soft, #e2e8f0);
    }
    .stock-count-chip strong {
      color: var(--text-main, #0f172a);
    }

    /* ── STOCK TABLE CARD & HEADERS ────────────────────────────────────── */
    .stock-table-card {
      background: var(--bg-card, #ffffff) !important;
      border: 1px solid var(--border-soft, #e2e8f0) !important;
      border-radius: 12px !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
      padding: 1.25rem !important;
      margin-bottom: 1.25rem !important;
    }
    .stock-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border-soft, #f1f5f9);
    }
    .stock-card-title-group {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .stock-stage-pill {
      font-size: 0.72rem;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 6px;
      letter-spacing: 0.5px;
    }
    .stock-stage-pill.is-raw  { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .stock-stage-pill.is-semi { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .stock-stage-pill.is-fg   { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .stock-stage-pill.is-pkg  { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }

    .stock-card-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-main, #0f172a);
      margin: 0;
    }
    .stock-card-count-pill {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted, #64748b);
      background: var(--bg-hover, #f1f5f9);
      padding: 2px 8px;
      border-radius: 999px;
      border: 1px solid var(--border-soft, #e2e8f0);
    }
    .stock-low-alert-banner {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      background: #dc2626;
      color: #ffffff;
      box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
    }

    .stock-empty-state {
      text-align: center;
      padding: 2.5rem 1rem;
      color: var(--text-muted, #64748b);
    }
    .stock-empty-state .empty-icon {
      font-size: 2.2rem;
      display: block;
      margin-bottom: 0.5rem;
      opacity: 0.7;
    }

    /* Location Column Hover & Chip */
    .table-container table.stock-table td.location-col {
      cursor: pointer;
      color: var(--primary-light, #8a5a00) !important;
      font-weight: 600;
      transition: all 0.15s ease;
    }
    .table-container table.stock-table td.location-col:hover {
      color: #2563eb !important;
      text-decoration: underline;
    }

    /* Dark Mode Adjustments */
    html.dark-mode .stock-header-title { color: #f8fafc; }
    html.dark-mode .stock-tabs-container { background: #0f172a; border-color: #334155; }
    html.dark-mode .stock-tab { color: #94a3b8 !important; }
    html.dark-mode .stock-tab:hover { background: #1e293b !important; color: #f8fafc !important; }
    html.dark-mode .stock-tab .stock-tab-badge { background: #1e293b !important; color: #94a3b8 !important; border-color: #334155 !important; }
    html.dark-mode .stock-toolbar-card { background: #1e293b; border-color: #334155; }
    html.dark-mode .stock-search-wrap input { background: #0f172a !important; border-color: #334155 !important; color: #f8fafc !important; }
    html.dark-mode .stock-count-chip { background: #0f172a; border-color: #334155; color: #94a3b8; }
    html.dark-mode .stock-count-chip strong { color: #f8fafc; }
    html.dark-mode .stock-table-card { background: #1e293b !important; border-color: #334155 !important; }
    html.dark-mode .stock-card-header { border-bottom-color: #334155; }
    html.dark-mode .stock-card-title { color: #f8fafc; }
    html.dark-mode .stock-card-count-pill { background: #0f172a; border-color: #334155; color: #94a3b8; }
    html.dark-mode .stock-action-btn-secondary { background: #1e293b !important; border-color: #334155 !important; color: #f8fafc !important; }
    html.dark-mode .stock-action-btn-secondary:hover { background: #334155 !important; }
    html.dark-mode .stock-low-toggle-btn { background: #1e293b !important; border-color: #ef4444 !important; color: #ef4444 !important; }
    html.dark-mode .stock-low-toggle-btn:hover { background: #2d1820 !important; }
    html.dark-mode .stock-low-toggle-btn.is-active { background: #dc2626 !important; color: #ffffff !important; }
  </style>

  <!-- Modern Segmented Navigation & Filter Toolbar -->
  <div class="stock-nav-bar">
    <div class="stock-tabs-container">
      <a href="{{ route(request()->segment(1) . '.stock') }}" class="stock-tab {{ !$typeFilter ? 'active is-all' : '' }}">
        <span class="stock-tab-icon">🌐</span>
        <span class="stock-tab-label">ALL</span>
        <span class="stock-tab-badge">{{ count($pageData['allStock']) }}</span>
      </a>
      <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'raw']) }}" class="stock-tab {{ $typeFilter === 'RAW' ? 'active is-raw' : '' }}">
        <span class="stock-tab-icon">🌿</span>
        <span class="stock-tab-label">RAW</span>
        <span class="stock-tab-badge">{{ $rawItems->count() }}</span>
      </a>
      <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'semi']) }}" class="stock-tab {{ $typeFilter === 'SEMI' ? 'active is-semi' : '' }}">
        <span class="stock-tab-icon">⚗️</span>
        <span class="stock-tab-label">SEMI</span>
        <span class="stock-tab-badge">{{ $semiItems->count() }}</span>
      </a>
      <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'finished']) }}" class="stock-tab {{ ($typeFilter === 'FINISHED' || $typeFilter === 'FG') ? 'active is-fg' : '' }}">
        <span class="stock-tab-icon">✅</span>
        <span class="stock-tab-label">FG</span>
        <span class="stock-tab-badge">{{ $finishedItems->count() }}</span>
      </a>
      <a href="{{ route(request()->segment(1) . '.stock', ['type' => 'packaging']) }}" class="stock-tab {{ ($typeFilter === 'PACKAGING' || $typeFilter === 'PKG') ? 'active is-pkg' : '' }}">
        <span class="stock-tab-icon">📦</span>
        <span class="stock-tab-label">PACKAGING</span>
        <span class="stock-tab-badge">{{ $packagingItems->count() }}</span>
      </a>
    </div>

    @php
      $relevantForLow = match($typeFilter) {
        'RAW' => $rawItems,
        'SEMI' => $semiItems,
        'FINISHED', 'FG' => $finishedItems,
        'PACKAGING', 'PKG' => $packagingItems,
        default => $rawItems->concat($semiItems)->concat($finishedItems)->concat($packagingItems)
      };
      $currentLowStockCount = $relevantForLow->filter(fn($s) => (float)($s->alert_limit ?? 0) > 0 && (float)($s->quantity ?? 0) <= (float)($s->alert_limit ?? 0) && (float)($s->quantity ?? 0) > 0)->count();
    @endphp

    <div class="stock-quick-actions">
      <!-- Low Stock Only Quick Filter Toggle -->
      <button type="button" id="toggle-low-stock-btn" onclick="toggleLowStockOnly()" class="stock-low-toggle-btn" title="Filter to only products that need replenishment">
        <span class="low-stock-icon">⚠</span>
        <span id="toggle-low-stock-label">Show Low Stock Only</span>
        @if($currentLowStockCount > 0)
          <span class="stock-low-badge" id="stock-low-badge-count">{{ $currentLowStockCount }}</span>
        @endif
      </button>
    </div>
  </div>

  <!-- Common Product Instant Search Bar -->
  <div class="stock-toolbar-card">
    <div class="stock-search-wrap">
      <svg class="stock-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <input type="text" id="global-product-search" placeholder="Search product name in {{ $typeFilter ? ($typeFilter === 'FINISHED' ? 'FG' : $typeFilter) : 'all stock' }}..." oninput="onGlobalProductSearch(this.value)">
      <button type="button" id="stock-search-clear-btn" class="stock-search-clear-btn" onclick="clearStockSearch()" title="Clear search">✕</button>
    </div>
    <div class="stock-toolbar-info">
      <span class="stock-count-chip">
        @if(!$typeFilter)
          Showing <strong>{{ count($pageData['allStock']) }}</strong> Total Products
        @elseif($typeFilter === 'FINISHED' || $typeFilter === 'FG')
          Showing <strong>{{ $finishedItems->count() }}</strong> Finished Goods (FG)
        @elseif($typeFilter === 'RAW')
          Showing <strong>{{ $rawItems->count() }}</strong> Raw Materials
        @elseif($typeFilter === 'SEMI')
          Showing <strong>{{ $semiItems->count() }}</strong> Semi-Finished Items
        @elseif($typeFilter === 'PACKAGING' || $typeFilter === 'PKG')
          Showing <strong>{{ $packagingItems->count() }}</strong> Packaging Items
        @endif
      </span>
    </div>
  </div>

  @if(!$typeFilter || $typeFilter === 'RAW')
  <!-- RAW Stock -->
  <div class="card stock-table-card">
    @php
      $rawLowCount = $rawItems->filter(fn($s) => (float)($s->alert_limit ?? 0) > 0 && (float)($s->quantity ?? 0) <= (float)($s->alert_limit ?? 0) && (float)($s->quantity ?? 0) > 0)->count();
    @endphp
    <div class="stock-card-header">
      <div class="stock-card-title-group">
        <span class="stock-stage-pill is-raw">RAW</span>
        <h3 class="stock-card-title">🌿 Raw Material Stock</h3>
        <span class="stock-card-count-pill">{{ $rawItems->count() }} items</span>
      </div>
      @if($rawLowCount > 0)
        <div class="stock-low-alert-banner">
          <span class="alert-icon">⚠</span>
          <span><strong>{{ $rawLowCount }}</strong> Low Stock Ranked on Top</span>
        </div>
      @endif
    </div>
    @if($rawItems->isEmpty())
      <div class="stock-empty-state">
        <span class="empty-icon">🌿</span>
        <p>No raw stock recorded yet.</p>
      </div>
    @else
    <div class="table-container">
      <table class="stock-table">
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="raw-stock-tbody">
@foreach($rawItems as $s)
          @php 
            $hasQty = (float) $s->quantity > 0;
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow && $hasQty) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && !in_array(strtoupper(trim($s->grade)), ['NONE', 'N/A', 'NA', 'N / A'], true))_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(RAW)</span>
              </div>
            </td>
            <td style="font-weight:bold; color:var(--secondary);">{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td style="font-weight:bold; color:var(--text-color);">
              {{ number_format($s->alert_limit, 2) }}
              <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Edit Min Qty" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="RAW" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }})" title="Adjust Stock">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if($hasQty)
                  <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Stock has quantity ({{ number_format($s->quantity, 2) }} {{ $s->unit }}). Quantity must be 0 to delete.">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @else
                  <button class="btn-icon delete" onclick="adminDeleteStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" title="Delete Stock Entry">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
  @endif

  @if(!$typeFilter || $typeFilter === 'SEMI')
  <!-- SEMI Stock -->
  <div class="card stock-table-card">
    @php
      $semiLowCount = $semiItems->filter(fn($s) => (float)($s->alert_limit ?? 0) > 0 && (float)($s->quantity ?? 0) <= (float)($s->alert_limit ?? 0) && (float)($s->quantity ?? 0) > 0)->count();
    @endphp
    <div class="stock-card-header">
      <div class="stock-card-title-group">
        <span class="stock-stage-pill is-semi">SEMI</span>
        <h3 class="stock-card-title">⚗️ Semi-Finished Stock</h3>
        <span class="stock-card-count-pill">{{ $semiItems->count() }} items</span>
      </div>
      @if($semiLowCount > 0)
        <div class="stock-low-alert-banner">
          <span class="alert-icon">⚠</span>
          <span><strong>{{ $semiLowCount }}</strong> Low Stock Ranked on Top</span>
        </div>
      @endif
    </div>
    @if($semiItems->isEmpty())
      <div class="stock-empty-state">
        <span class="empty-icon">⚗️</span>
        <p>No semi-finished stock recorded yet.</p>
      </div>
    @else
    <div class="table-container">
      <table class="stock-table">
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="semi-stock-tbody">
          @foreach($semiItems as $s)
          @php 
            $hasQty = (float) $s->quantity > 0;
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow && $hasQty) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && !in_array(strtoupper(trim($s->grade)), ['NONE', 'N/A', 'NA', 'N / A'], true))_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(SEMI)</span>
              </div>
            </td>
            <td style="font-weight:bold; color:var(--warning);">{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td style="font-weight:bold; color:var(--text-color);">
              {{ number_format($s->alert_limit, 2) }}
              <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Edit Min Qty" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="SEMI" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }})" title="Adjust Stock">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if($hasQty)
                  <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Stock has quantity ({{ number_format($s->quantity, 2) }} {{ $s->unit }}). Quantity must be 0 to delete.">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @else
                  <button class="btn-icon delete" onclick="adminDeleteStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" title="Delete Stock Entry">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
  @endif

  @if(!$typeFilter || $typeFilter === 'FINISHED' || $typeFilter === 'FG')
  <!-- Finished Stock -->
  <div class="card stock-table-card">
    @php
      $finishedLowCount = $finishedItems->filter(fn($s) => (float)($s->alert_limit ?? 0) > 0 && (float)($s->quantity ?? 0) <= (float)($s->alert_limit ?? 0) && (float)($s->quantity ?? 0) > 0)->count();
    @endphp
    <div class="stock-card-header">
      <div class="stock-card-title-group">
        <span class="stock-stage-pill is-fg">FG</span>
        <h3 class="stock-card-title">✅ Finished Goods (FG) Stock</h3>
        <span class="stock-card-count-pill">{{ $finishedItems->count() }} items</span>
      </div>
      @if($finishedLowCount > 0)
        <div class="stock-low-alert-banner">
          <span class="alert-icon">⚠</span>
          <span><strong>{{ $finishedLowCount }}</strong> Low Stock Ranked on Top</span>
        </div>
      @endif
    </div>
    @if($finishedItems->isEmpty())
      <div class="stock-empty-state">
        <span class="empty-icon">✅</span>
        <p>No Finished Goods (FG) stock recorded yet.</p>
      </div>
    @else
    <div class="table-container">
      <table class="stock-table">
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="finished-stock-tbody">
          @foreach($finishedItems as $s)
          @php 
            $hasQty = (float) $s->quantity > 0;
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow && $hasQty) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && !in_array(strtoupper(trim($s->grade)), ['NONE', 'N/A', 'NA', 'N / A'], true))_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(FG)</span>
              </div>
            </td>
            <td style="font-weight:bold; color:var(--secondary);">{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td style="font-weight:bold; color:var(--text-color);">
              {{ number_format($s->alert_limit, 2) }}
              <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Edit Min Qty" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="FINISHED" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }})" title="Adjust Stock">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                @if($hasQty)
                  <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Stock has quantity ({{ number_format($s->quantity, 2) }} {{ $s->unit }}). Quantity must be 0 to delete.">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @else
                  <button class="btn-icon delete" onclick="adminDeleteStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" title="Delete Stock Entry">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
  @endif

  @if(!$typeFilter || $typeFilter === 'PACKAGING' || $typeFilter === 'PKG')
  <!-- Packaging Stock -->
  <div class="card stock-table-card" style="margin-top:1.25rem;">
    @php
      $packagingLowCount = $packagingItems->filter(fn($s) => (float)($s->alert_limit ?? 0) > 0 && (float)($s->quantity ?? 0) <= (float)($s->alert_limit ?? 0) && (float)($s->quantity ?? 0) > 0)->count();
    @endphp
    <div class="stock-card-header">
      <div class="stock-card-title-group">
        <span class="stock-stage-pill is-pkg">PKG</span>
        <h3 class="stock-card-title">📦 Packaging Material Stock</h3>
        <span class="stock-card-count-pill">{{ $packagingItems->count() }} items</span>
      </div>
      @if($packagingLowCount > 0)
        <div class="stock-low-alert-banner">
          <span class="alert-icon">⚠</span>
          <span><strong>{{ $packagingLowCount }}</strong> Low Stock Ranked on Top</span>
        </div>
      @endif
    </div>
    @if($packagingItems->isEmpty())
      <div class="stock-empty-state">
        <span class="empty-icon">📦</span>
        <p>No packaging stock recorded yet.</p>
      </div>
    @else
    <div class="table-container">
      <table class="stock-table">
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="packaging-stock-tbody">
          @foreach($packagingItems as $s)
          @php 
            $hasQty = (float) $s->quantity > 0;
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow && $hasQty) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && !in_array(strtoupper(trim($s->grade)), ['NONE', 'N/A', 'NA', 'N / A'], true))_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(PKG)</span>
              </div>
            </td>
            <td style="font-weight:bold; color:var(--secondary);">{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td style="font-weight:bold; color:var(--text-color);">
              {{ number_format($s->alert_limit, 2) }}
              <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Edit Min Qty" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="PACKAGING" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                 <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }})" title="Adjust Stock">
                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                 </button>
                 @if($hasQty)
                   <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Stock has quantity ({{ number_format($s->quantity, 2) }} {{ $s->unit }}). Quantity must be 0 to delete.">
                     <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                   </button>
                 @else
                   <button class="btn-icon delete" onclick="adminDeleteStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" title="Delete Stock Entry">
                     <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                   </button>
                 @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
  @endif
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
const adminStockProducts = @json($pageData['allProducts']);
const adminStockLogsByKey = @json($pageData['stockLogsByKey']);
let locationMappings = @json($pageData['locationMappings'] ?? (object)[]);

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[char]));
}

function adminDeleteStock(productId, stage, grade, productName = '') {
  Swal.fire({
    title: 'Delete Stock Entry?',
    text: `Are you sure you want to delete the stock entry for ${productName} (${stage})?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(window.baseUrl + '/' + window.userSlug + '/stock/delete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ product_id: productId, stage, grade })
      })
      .then(async r => {
        const data = await r.json().catch(() => null);
        if (!data) {
          if (r.status === 419) throw new Error('Session expired. Please refresh the page.');
          throw new Error(`Server returned status ${r.status}`);
        }
        return data;
      })
      .then(d => {
        if (d.success) {
          Swal.fire('Deleted!', d.message || 'Stock entry deleted successfully.', 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Could not delete stock.', 'error');
        }
      })
      .catch(e => {
        Swal.fire('Error!', 'Failed to delete: ' + e.message, 'error');
      });
    }
  });
}

function adminAddStock() {
  Swal.fire({
    title: 'Add Stock',
    html: `
      <div style="text-align:left;">
        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">📅 Date *</label>
        <input id="add-stock-date" type="date" value="${new Date().toISOString().split('T')[0]}" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333; font-weight:600;">

        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">Stock Type</label>
        <select id="add-stock-stage" onchange="onStockStageChange()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
          <option value="ALL">ALL</option>
          <option value="RAW">RAW</option>
          <option value="SEMI">SEMI</option>
          <option value="FINISHED">FG</option>
          <option value="PACKAGING">PACKAGING</option>
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">Product</label>
        <select id="add-stock-product" onchange="onStockProductChange()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
          <!-- Populated dynamically -->
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">Grade</label>
        <div id="add-stock-grade-container">
          <!-- Populated dynamically -->
        </div>

        <label style="font-size:0.82rem; font-weight:600; color:#6b7280; margin-top:0.85rem; display:block;">Quantity</label>
        <input id="add-stock-qty" type="number" min="0.001" step="0.001" placeholder="e.g. 200" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">

        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">Note</label>
        <textarea id="add-stock-note" rows="2" placeholder="Optional details" style="width:100%; padding:0.65rem; margin-top:0.35rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333; resize:vertical;"></textarea>
      </div>
    `,
    background: '#ffffff',
    color: '#333333',
    customClass: {
      popup: 'swal-stock-popup'
    },
    showCancelButton: true,
    confirmButtonText: 'Add Stock',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    didOpen: () => {
      window.onStockStageChange = function() {
        const stage = document.getElementById('add-stock-stage').value;
        const productSelect = document.getElementById('add-stock-product');
        let filteredProducts = [];
        const targetStage = (stage === 'FG' ? 'FINISHED' : stage);
        if (targetStage === 'ALL') {
          filteredProducts = adminStockProducts.filter(p => p.is_active);
        } else {
          filteredProducts = adminStockProducts.filter(p => (p.type === targetStage || (targetStage === 'FINISHED' && p.type === 'FG')) && p.is_active);
        }
        
        productSelect.innerHTML = filteredProducts.map(p => {
          let t = (p.type === 'FINISHED' || p.type === 'FG') ? 'FG' : p.type;
          if (p.grades && p.grades.length > 0) {
            return p.grades.map(g => {
                let isDef = !g.name || ['NONE', 'N/A', 'NA', 'N / A'].includes(g.name.trim().toUpperCase());
                let gradeText = !isDef ? `_${escapeHtml(g.name)}` : '';
                return `<option value="${p.id}|${g.name}" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)}${gradeText} (${t})</option>`;
            }).join('');
          } else {
            return `<option value="${p.id}|NONE" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)} (${t})</option>`;
          }
        }).join('');
        
        if (typeof $ !== 'undefined' && $.fn.select2) {
          if ($(productSelect).hasClass('select2-hidden-accessible')) {
            $(productSelect).select2('destroy');
          }
          $(productSelect).select2({
            dropdownParent: Swal.getPopup(),
            width: '100%',
            dropdownAutoWidth: false
          });
        }
        
        onStockProductChange();
      };

      window.onStockProductChange = function() {
        const gradeContainer = document.getElementById('add-stock-grade-container');
        if (gradeContainer) {
            gradeContainer.innerHTML = '';
            gradeContainer.style.display = 'none';
        }
      };

      onStockStageChange();
    },
    preConfirm: () => {
      const val = document.getElementById('add-stock-product').value;
      const [productId, gradeVal] = val ? val.split('|') : ['', 'NONE'];
      let stage = document.getElementById('add-stock-stage').value;
      if (stage === 'ALL') {
        const prod = adminStockProducts.find(p => String(p.id) === String(productId));
        stage = prod ? prod.type : 'RAW';
      }
      let grade = gradeVal || 'NONE';
      const quantity = parseFloat(document.getElementById('add-stock-qty').value);
      const reason = document.getElementById('add-stock-note').value.trim();

      if (!productId) {
        Swal.showValidationMessage('Please select a product.');
        return false;
      }
      if (isNaN(quantity) || quantity <= 0) {
        Swal.showValidationMessage('Please enter a quantity greater than 0.');
        return false;
      }
      const dateVal = document.getElementById('add-stock-date') ? document.getElementById('add-stock-date').value : '';
      return { product_id: productId, stage, grade, quantity, date: dateVal, adjust_type: 'add', reason };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    fetch(window.baseUrl + '/' + window.userSlug + '/stock/adjust', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(result.value)
    })
    .then(async r => {
      const data = await r.json().catch(() => null);
      if (!data) {
        if (r.status === 419) throw new Error('Session expired. Please refresh the page.');
        throw new Error(`Server returned status ${r.status}`);
      }
      return data;
    })
    .then(d => {
      if (d.success) {
        Swal.fire('Saved', d.message || 'Stock added.', 'success').then(() => location.reload());
      } else {
        Swal.fire('Error', d.message || 'Could not add stock.', 'error');
      }
    })
    .catch(e => {
      Swal.fire('Error', 'Failed to communicate with server: ' + e.message, 'error');
    });
  });
}

function adminAdjustStock(productId, stage, grade, productName = '', currentQty = 0) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG', PACKAGING: '📦 Packaging' }[stage] || stage;
  const isDefGrade = !grade || ['NONE', 'N/A', 'NA', 'N / A'].includes(grade.trim().toUpperCase());
  const displayGrade = !isDefGrade ? ` &nbsp;·&nbsp; Grade: <strong style="color:#333;">${grade}</strong>` : '';

  // Get locations breakdown instantly from serverPageData locationMappings
  const key = `${productId}_${grade}_${stage}`;
  const locMap = (locationMappings && locationMappings[key]) ? locationMappings[key] : {};
  const locBreakdown = [];
  for (const [locName, qty] of Object.entries(locMap)) {
    locBreakdown.push({ name: locName, quantity: parseFloat(qty) || 0 });
  }

  const masterLocNames = (window._swalAllLocations && window._swalAllLocations.length)
    ? window._swalAllLocations.map(l => l.name)
    : ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];

  window._swalLocBreakdown = locBreakdown;
  window._swalAllMasterLocs = masterLocNames.map(n => ({ name: n }));

  // Background fetch to refresh fresh location breakdown silently if needed
  fetch(`/api/stock/locations?product_id=${productId}&stage=${stage}&grade=${encodeURIComponent(grade)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.breakdown) {
        window._swalLocBreakdown = data.breakdown;
        renderSwalLocationDropdownMenu();
      }
    }).catch(() => {});

  Swal.fire({
    title: 'Adjust Stock',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280; background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:10px 12px;">
        <strong style="color:var(--primary); font-size:1.05rem;">${escapeHtml(productName)} (${stage})</strong><br>
        <span style="font-size:0.85rem;">${stageLabel}${displayGrade}</span>
        <div style="margin-top:4px; font-size:0.85rem; color:#333;">Total Current Stock: <strong id="swal-total-stock-badge" style="color:var(--secondary);">${currentQty.toFixed(2)} kg</strong></div>
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        📅 Adjustment Date *
      </label>
      <input type="date" id="swal-adj-date" value="${new Date().toISOString().split('T')[0]}" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:0.95rem; margin-bottom:1rem; outline:none; font-weight:600; box-sizing:border-box;
      ">

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Adjustment Type
      </label>
      <select id="swal-adj-type" onchange="onSwalAdjTypeChange()" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:0.95rem; margin-bottom:1rem; outline:none; font-weight:600;
      ">
        <option value="set">🎯 Set — Override to exact quantity</option>
        <option value="add" selected>➕ Add — Increase current stock</option>
        <option value="subtract">➖ Subtract — Decrease current stock</option>
      </select>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">
        <label style="font-size:0.82rem; font-weight:700; color:#374151; margin:0;">STORAGE LOCATION *</label>
      </div>

      <div style="display:flex; gap:0.5rem; align-items:flex-start; width:100%; margin-bottom:1rem;">
        <div style="flex:3; min-width:240px; position:relative;">
          <div class="custom-location-dropdown" id="swal-custom-loc-dd" style="width:100%; position:relative;">
            <button type="button" onclick="event.stopPropagation(); this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';" style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center; background:#fff; border: 1px solid #d1d5db; padding: 0.6rem 0.85rem; font-size:0.9rem; font-weight:600; color:#111827; border-radius:8px; cursor:pointer;">
              <span class="loc-dropdown-text">Select Storage Location</span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <ul class="dropdown-menu shadow" style="display:none; position:absolute; top:100%; left:0; z-index:999999 !important; width:360px; max-height:260px; overflow-y:auto; background:#ffffff !important; border:1px solid #d1d5db; border-radius:8px; list-style:none; margin-top:0.25rem; padding:0.6rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;">
              <!-- Dynamically rendered -->
            </ul>
          </div>
        </div>

        <div style="flex:1; min-width:110px;">
          <input type="number" min="0" step="0.001" id="swal-main-qty-input" class="no-spinners" placeholder="0" value="0" oninput="onSwalMainQtyInput(this)" style="height:2.6rem; padding:0.4rem 0.6rem; font-size:1.05rem; font-weight:700; text-align:center; width:100%; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;">
        </div>
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Reason / Note <span style="font-weight:400;">(optional)</span>
      </label>
      <textarea id="swal-reason" rows="2" placeholder="e.g. Physical count correction, spillage, etc." style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:0.9rem; resize:vertical; outline:none; box-sizing:border-box; margin-bottom:1.5rem;
      "></textarea>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; border-top:1px solid #f3f4f6; padding-top:1rem; position:relative; z-index:1;">
        <button type="button" class="btn" onclick="Swal.clickConfirm()" style="padding:0.65rem 1.4rem; font-weight:700; background:#f59e0b; color:#ffffff; border:none; border-radius:8px; cursor:pointer;">Apply Adjustment</button>
        <button type="button" class="btn btn-secondary" onclick="Swal.close()" style="padding:0.65rem 1.4rem; font-weight:600; background:#f3f4f6; color:#4b5563; border:none; border-radius:8px; cursor:pointer;">Cancel</button>
      </div>
    `,
    background: '#ffffff',
    color: '#333333',
    showConfirmButton: false,
    showCancelButton: false,
    width: '600px',
    didOpen: (popup) => {
      if (popup) {
        popup.style.setProperty('overflow', 'visible', 'important');
        const htmlContainer = popup.querySelector('.swal2-html-container');
        if (htmlContainer) {
          htmlContainer.style.setProperty('overflow', 'visible', 'important');
        }
      }
      renderSwalLocationDropdownMenu();
    },
    preConfirm: () => {
      const type = document.getElementById('swal-adj-type').value;
      const reason = document.getElementById('swal-reason').value.trim();
      const locInputs = document.querySelectorAll('#swal-custom-loc-dd .inner-qty-input');
      const splits = [];
      let totalQtyEntered = 0;

      locInputs.forEach(inp => {
        const loc = inp.getAttribute('data-loc');
        const avail = parseFloat(inp.getAttribute('data-avail') || 0);
        const val = parseFloat(inp.value) || 0;

        if (type === 'set') {
          splits.push({ location: loc, quantity: val });
        } else if (val > 0) {
          if (type === 'subtract' && val > avail) {
            Swal.showValidationMessage(`⚠️ Cannot subtract ${val} kg from '${loc}' — only ${avail} kg available.`);
            return false;
          }
          splits.push({ location: loc, quantity: val });
          totalQtyEntered += val;
        }
      });

      if (splits.length === 0 && type !== 'set') {
        const mainVal = parseFloat(document.getElementById('swal-main-qty-input').value) || 0;
        if (mainVal <= 0) {
          Swal.showValidationMessage('⚠️ Please enter a quantity for at least one storage location.');
          return false;
        }
        splits.push({ location: 'Main Warehouse', quantity: mainVal });
      }

      const adjDate = document.getElementById('swal-adj-date') ? document.getElementById('swal-adj-date').value : '';
      return { type, reason, splits, mainQty: parseFloat(document.getElementById('swal-main-qty-input').value) || 0, date: adjDate };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    const { type, reason, splits, mainQty, date } = result.value;

    Swal.fire({
      title: 'Applying…',
      text: 'Updating stock record.',
      allowOutsideClick: false,
      background: '#ffffff',
      color: '#333333',
      didOpen: () => Swal.showLoading()
    });

    const payload = {
      product_id: productId,
      stage,
      grade,
      date,
      adjust_type: type,
      reason,
      location_splits: splits,
      quantity: mainQty,
      location: splits.length ? splits[0].location : 'Main Warehouse'
    };

    fetch(window.baseUrl + '/' + window.userSlug + '/stock/adjust', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(payload)
    })
    .then(async r => {
      const data = await r.json().catch(() => null);
      if (!data) {
        if (r.status === 419) throw new Error('Session expired. Please refresh the page.');
        throw new Error(`Server returned status ${r.status}`);
      }
      return data;
    })
    .then(d => {
      if (d.success) {
        Swal.fire({
          icon: 'success',
          title: 'Stock Updated',
          text: d.message || 'Adjustment applied successfully.',
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
          timer: 1500,
          showConfirmButton: false
        }).then(() => fetchLiveStock());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: d.message || 'Something went wrong.',
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
        });
      }
    })
    .catch(e => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to communicate with server: ' + e.message,
        background: '#ffffff',
        color: '#333333',
        confirmButtonColor: '#f59e0b',
      });
    });
  });
}

function renderSwalLocationDropdownMenu() {
  const dropdownMenu = document.querySelector('#swal-custom-loc-dd .dropdown-menu');
  if (!dropdownMenu) return;

  const locMap = {};
  (window._swalLocBreakdown || []).forEach(l => { locMap[l.name.trim().toLowerCase()] = l.quantity; });
  const locs = (window._swalAllMasterLocs || []).map(l => l.name);

  const type = document.getElementById('swal-adj-type')?.value || 'add';

  let html = '';
  locs.forEach(locName => {
    const key = locName.trim().toLowerCase();
    const avail = locMap[key] !== undefined ? locMap[key] : 0;
    const initialVal = type === 'set' ? avail : 0;
    html += `
      <li style="margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:#333; gap:8px;">
        <span style="padding-left:0.2rem; font-weight:600;">${escapeHtml(locName.toUpperCase())} (${avail.toFixed(2)} KG)</span>
        <input type="number" min="0" step="0.001" class="form-control form-control-sm inner-qty-input no-spinners" data-loc="${escapeHtml(locName)}" data-avail="${avail}" oninput="recalcSwalDropdownTotals()" style="width:75px; text-align:center; padding:0.2rem; height:1.8rem; font-size:0.8rem; border:1px solid #d1d5db; border-radius:4px;" value="${initialVal}">
      </li>
    `;
  });

  dropdownMenu.innerHTML = html;
  recalcSwalDropdownTotals();
}

function onSwalAdjTypeChange() {
  renderSwalLocationDropdownMenu();
}

function recalcSwalDropdownTotals() {
  const inputs = document.querySelectorAll('#swal-custom-loc-dd .inner-qty-input');
  let sumEnteredQty = 0;
  const activeLocSummary = [];
  const type = document.getElementById('swal-adj-type')?.value || 'add';

  const totalExistingAvail = (window._swalLocBreakdown || []).reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);

  inputs.forEach(inp => {
    const qty = parseFloat(inp.value) || 0;
    const locName = inp.getAttribute('data-loc');
    const avail = parseFloat(inp.getAttribute('data-avail') || 0);

    if (type === 'set') {
      activeLocSummary.push(`${locName.toUpperCase()} (${qty} KG)`);
      sumEnteredQty += qty;
    } else if (qty > 0) {
      sumEnteredQty += qty;
      const previewQty = type === 'add' ? (avail + qty) : Math.max(0, avail - qty);
      activeLocSummary.push(`${locName.toUpperCase()} (${previewQty} KG)`);
    }
  });

  const btnText = document.querySelector('#swal-custom-loc-dd .loc-dropdown-text');
  if (btnText) {
    if (activeLocSummary.length > 0) {
      btnText.textContent = activeLocSummary.join(', ');
    } else {
      btnText.textContent = 'Select Storage Location';
    }
  }

  const mainQtyInput = document.getElementById('swal-main-qty-input');
  if (mainQtyInput) {
    mainQtyInput.value = sumEnteredQty > 0 ? sumEnteredQty : 0;
  }

  let grandTotal = 0;
  if (type === 'set') {
    grandTotal = sumEnteredQty;
  } else if (type === 'add') {
    grandTotal = totalExistingAvail + sumEnteredQty;
  } else { // subtract
    grandTotal = Math.max(0, totalExistingAvail - sumEnteredQty);
  }

  const badge = document.getElementById('swal-total-stock-badge');
  if (badge) {
    badge.innerText = `${grandTotal.toLocaleString('en-IN', { maximumFractionDigits: 3 })} kg`;
  }
}

function onSwalMainQtyInput(mainInput) {
  const mainVal = parseFloat(mainInput.value) || 0;
  const totalExistingAvail = (window._swalLocBreakdown || []).reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);
  const type = document.getElementById('swal-adj-type')?.value || 'add';

  let grandTotal = 0;
  if (type === 'set') {
    grandTotal = mainVal;
  } else if (type === 'add') {
    grandTotal = totalExistingAvail + mainVal;
  } else {
    grandTotal = Math.max(0, totalExistingAvail - mainVal);
  }

  const badge = document.getElementById('swal-total-stock-badge');
  if (badge) {
    badge.innerText = `${grandTotal.toLocaleString('en-IN', { maximumFractionDigits: 3 })} kg`;
  }
}

function adminUpdateRate(productId, currentRate, name) {
  Swal.fire({
    title: 'Edit Rate',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        <strong style="color:#333;">${name}</strong>
      </div>
      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        New Rate (₹)
      </label>
      <input id="swal-rate-val" type="number" min="0" step="0.01" value="${currentRate}" style="width:100%; padding:0.65rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#333;">
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'Save',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    preConfirm: () => {
      const val = document.getElementById('swal-rate-val').value;
      if (!val || val < 0) {
        Swal.showValidationMessage('Enter a valid rate');
        return false;
      }
      return val;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(window.baseUrl + '/' + window.userSlug + '/stock/rate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          product_id: productId,
          rate: result.value
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire({ 
            icon: 'success', 
            title: 'Updated!', 
            text: data.message,
            background: '#ffffff',
            color: '#333333',
            confirmButtonColor: '#f59e0b',
            timer: 1500, 
            showConfirmButton: false 
          }).then(() => fetchLiveStock());
        } else {
          Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            text: data.message || data.error || 'Failed to update rate',
            background: '#ffffff',
            color: '#333333',
            confirmButtonColor: '#f59e0b',
          });
        }
      })
      .catch(err => {
        console.error(err);
        Swal.fire({ 
          icon: 'error', 
          title: 'Error', 
          text: 'Network error',
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
        });
      });
    }
  });
}

function adminSetLimit(productId, stage, grade, currentLimit, productName = '') {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG', PACKAGING: '📦 Packaging' }[stage] || stage;
  const isDefGrade = !grade || ['NONE', 'N/A', 'NA', 'N / A'].includes(grade.trim().toUpperCase());
  const displayGrade = !isDefGrade ? ` &nbsp;·&nbsp; Grade: <strong style="color:#333;">${grade}</strong>` : '';

  Swal.fire({
    title: 'Set Alert Limit',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        <strong style="color:var(--primary); font-size:1.05rem;">${productName} (${stage})</strong><br>
        <span style="font-size:0.85rem;">${stageLabel}${displayGrade}</span>
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Alert Limit (kg)
      </label>
      <input id="swal-limit-qty" type="number" min="0" step="0.01" value="${currentLimit}" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">
      <p style="text-align:left; font-size:0.8rem; color:#6b7280;">Set to 0 to disable alerts for this item.</p>
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'Save Limit',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    focusConfirm: false,
    width: '460px',
    customClass: {
      popup: 'swal-stock-popup',
      confirmButton: 'swal-confirm-btn',
      cancelButton: 'swal-cancel-btn',
    },
    preConfirm: () => {
      const limit = parseFloat(document.getElementById('swal-limit-qty').value);
      if (isNaN(limit) || limit < 0) {
        Swal.showValidationMessage('⚠️ Please enter a valid limit (≥ 0).');
        return false;
      }
      return limit;
    }
  }).then(result => {
    if (!result.isConfirmed) return;

    fetch(window.baseUrl + '/' + window.userSlug + '/stock/limit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage,
        grade,
        alert_limit: result.value
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        Swal.fire({
          icon: 'success',
          title: 'Limit Saved',
          text: d.message,
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
          timer: 1500,
          showConfirmButton: false
        }).then(() => fetchLiveStock());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: d.message || 'Something went wrong.',
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
        });
      }
    });
  });
}

function fetchLiveStock() {
  fetch(window.baseUrl + '/' + window.userSlug + '/stock/live?_t=' + new Date().getTime(), {
    headers: { 
      'Accept': 'application/json',
      'Cache-Control': 'no-cache' 
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success && data.data) {
      if (data.locationMappings) {
        locationMappings = data.locationMappings;
      }
      updateStockTables(data.data);
      updateAllLocationLabels();
    }
  })
  .catch(err => console.error('Polling error:', err));
}

// AJAX Polling every 30 seconds
setInterval(fetchLiveStock, 30000);

function updateStockTables(stockData) {
  const stages = { 'RAW': 'raw-stock-tbody', 'SEMI': 'semi-stock-tbody', 'FINISHED': 'finished-stock-tbody', 'PACKAGING': 'packaging-stock-tbody' };
  
  // Group data by stage
  const grouped = { 'RAW': [], 'SEMI': [], 'FINISHED': [], 'PACKAGING': [] };
  stockData.forEach(s => {
    if (grouped[s.stage]) grouped[s.stage].push(s);
  });

  for (const [stage, tbodyId] of Object.entries(stages)) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) continue;

    const items = grouped[stage];
    
    // Sort items so low stock always appears at the top
    items.sort((a, b) => {
      const limitA = parseFloat(a.alert_limit) || 0;
      const qtyA = parseFloat(a.quantity) || 0;
      const hasQtyA = qtyA > 0;
      const isLowA = (limitA > 0 && qtyA <= limitA && hasQtyA) ? 0 : ((limitA > 0 && qtyA <= limitA) ? 1 : 2);

      const limitB = parseFloat(b.alert_limit) || 0;
      const qtyB = parseFloat(b.quantity) || 0;
      const hasQtyB = qtyB > 0;
      const isLowB = (limitB > 0 && qtyB <= limitB && hasQtyB) ? 0 : ((limitB > 0 && qtyB <= limitB) ? 1 : 2);

      if (isLowA !== isLowB) return isLowA - isLowB;
      const sortA = a.sort_order !== undefined ? parseInt(a.sort_order) : 9999;
      const sortB = b.sort_order !== undefined ? parseInt(b.sort_order) : 9999;
      if (sortA !== sortB) return sortA - sortB;
      return (a.name || '').localeCompare(b.name || '');
    });

    if (items.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No stock recorded yet.</td></tr>`;
      continue;
    }

    let html = '';
    items.forEach(s => {
      const limit = parseFloat(s.alert_limit) || 0;
      const qty = parseFloat(s.quantity);
      const hasQty = qty > 0;
      const isLow = limit > 0 && qty <= limit;
      const rowClass = (isLow && hasQty) ? 'low-stock-row' : '';
      const titleAttr = (isLow && hasQty) ? `title="Low Stock! min_qty is ${limit}"` : '';

      const formattedQty = qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      let qtyColor = 'var(--text-color)';
      if (stage === 'RAW') qtyColor = 'var(--secondary)';
      if (stage === 'SEMI') qtyColor = 'var(--warning)';
      if (stage === 'FINISHED') qtyColor = 'var(--secondary)';

      html += `
        <tr class="${rowClass}" ${titleAttr}>
          <td>
            <div style="font-weight:normal; color:var(--text-color);">
              @php @endphp${s.name}${(s.grade && !['NONE', 'N/A', 'NA', 'N / A'].includes(s.grade.trim().toUpperCase())) ? '_<strong>' + escapeHtml(s.grade) + '</strong>' : ''} <span style="font-weight:bold;">(${s.stage === 'FINISHED' || s.stage === 'FG' ? 'FG' : s.stage})</span>
            </div>
          </td>
          <td style="font-weight:bold; color:${qtyColor};">${formattedQty}</td>
          <td>${s.unit || ''}</td>
          <td style="font-weight:bold;">
            ₹${window.number_format(s.rate ?? 0, 2)}
            <button class="btn-icon edit" onclick="adminUpdateRate('${s.productId}', '${s.rate ?? 0}', '${escapeHtml(s.name)}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
            </button>
          </td>
          <td style="font-weight:bold; color:var(--text-color);">
            ${limit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
            <button class="btn-icon edit" onclick="adminSetLimit('${s.productId}', '${s.stage}', '${s.grade}', '${limit}', '${escapeHtml(s.name)}')" title="Edit Min Qty" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
            </button>
          </td>
          <td class="location-col" data-product="${s.productId}" data-grade="${s.grade}" data-stage="${stage}" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
          <td>
            <div style="display:flex; align-items:center; gap:0.4rem;">
              <button class="btn-icon edit" onclick="adminAdjustStock('${s.productId}', '${s.stage}', '${s.grade}', '${escapeHtml(s.name)}', ${s.quantity})" title="Adjust Stock">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
              ${hasQty ? `
                <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Stock has quantity (${formattedQty} ${escapeHtml(s.unit || '')}). Quantity must be 0 to delete.">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              ` : `
                <button class="btn-icon delete" onclick="adminDeleteStock('${s.productId}', '${s.stage}', '${s.grade}', '${escapeHtml(s.name)}')" title="Delete Stock Entry">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              `}
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  }
  updateAllLocationLabels();
  if (lowStockOnlyActive) {
    applyLowStockFilter();
  }
  const searchInput = document.getElementById('global-product-search');
  if (searchInput && searchInput.value) {
    onGlobalProductSearch(searchInput.value);
  }
}

let lowStockOnlyActive = false;
function toggleLowStockOnly() {
  lowStockOnlyActive = !lowStockOnlyActive;
  const btn = document.getElementById('toggle-low-stock-btn');
  const label = document.getElementById('toggle-low-stock-label');
  if (btn && label) {
    if (lowStockOnlyActive) {
      btn.classList.add('is-active');
      btn.style.background = '#dc2626';
      btn.style.color = '#ffffff';
      btn.style.borderColor = '#b91c1c';
      label.textContent = 'Showing Low Stock (Click for All)';
    } else {
      btn.classList.remove('is-active');
      btn.style.background = '#ffffff';
      btn.style.color = '#dc2626';
      btn.style.borderColor = '#dc2626';
      label.textContent = 'Show Low Stock Only';
    }
  }
  applyLowStockFilter();
}

function clearStockSearch() {
  const searchInput = document.getElementById('global-product-search');
  const clearBtn = document.getElementById('stock-search-clear-btn');
  if (searchInput) {
    searchInput.value = '';
    onGlobalProductSearch('');
    searchInput.focus();
  }
  if (clearBtn) {
    clearBtn.style.display = 'none';
  }
}

function applyLowStockFilter() {
  ['raw-stock-tbody', 'semi-stock-tbody', 'finished-stock-tbody', 'packaging-stock-tbody'].forEach(tbodyId => {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.querySelectorAll('tr').forEach(tr => {
      if (tr.children.length <= 1) return;
      if (lowStockOnlyActive) {
        if (tr.classList.contains('low-stock-row')) {
          tr.style.display = '';
        } else {
          tr.style.display = 'none';
        }
      } else {
        tr.style.display = '';
      }
    });
  });

  const searchInput = document.getElementById('global-product-search');
  if (searchInput && searchInput.value) {
    onGlobalProductSearch(searchInput.value);
  }
}

function onGlobalProductSearch(val) {
  const clearBtn = document.getElementById('stock-search-clear-btn');
  if (clearBtn) {
    clearBtn.style.display = (val && val.trim().length > 0) ? 'inline-flex' : 'none';
  }
  const q = (val || '').trim().toUpperCase();
  const tokens = q.split(/\s+/).filter(Boolean);
  ['raw-stock-tbody', 'semi-stock-tbody', 'finished-stock-tbody', 'packaging-stock-tbody'].forEach(tbodyId => {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(tr => {
      if (!tr.children || tr.children.length === 0 || tr.children.length <= 1) return;
      if (lowStockOnlyActive && !tr.classList.contains('low-stock-row')) {
        tr.style.display = 'none';
        return;
      }
      const text = (tr.children[0]?.textContent || tr.children[0]?.innerText || '').toUpperCase();
      if (tokens.length === 0 || tokens.every(token => text.indexOf(token) > -1)) {
        tr.style.display = '';
      } else {
        tr.style.display = 'none';
      }
    });
  });
}

function getStoredLocationMappings() {
  return locationMappings;
}

function parseStockNumber(value) {
  return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function getAvailableStockForLocationCell(el) {
  const row = el.closest('tr');
  return row ? parseStockNumber(row.children[1]?.textContent) : 0;
}

function updateAllLocationLabels() {
  document.querySelectorAll('.location-col').forEach(td => {
    const pId = td.getAttribute('data-product');
    const grade = td.getAttribute('data-grade') || 'NONE';
    const stage = td.getAttribute('data-stage');
    const key = `${pId}_${grade}_${stage}`;
    const locMap = locationMappings[key] || {};
    const count = Object.keys(locMap).length;
    if(count === 0) {
      td.innerHTML = `📍 <span style="font-size:0.75rem; color:#6b7280;">Not Set</span>`;
    } else if(count === 1) {
      td.innerHTML = `📍 <span style="font-weight:600; color:var(--secondary);">${escapeHtml(Object.keys(locMap)[0])}</span>`;
    } else {
      td.innerHTML = `📍 <span class="badge badge-info" style="cursor:pointer; font-size:0.75rem;">${count} Locations</span>`;
    }
  });
}

window._swalAllLocations = [];
window._swalCurrentLocMap = {};

window.onTransferFromChange = function(fromSelectEl) {
  const selectedFrom = (fromSelectEl ? fromSelectEl.value : '').trim().toLowerCase();
  const toSelect = document.getElementById('swal-transfer-to');
  if (!toSelect) return;
  
  const currentToVal = (toSelect.value || '').trim().toLowerCase();
  
  let toHtml = '<option value="" disabled selected>To Location</option>';
  (window._swalAllLocations || []).forEach(loc => {
    const locName = (loc.name || '').trim();
    if (locName && locName.toLowerCase() !== selectedFrom) {
      let qty = 0;
      if (window._swalCurrentLocMap) {
        for (const [k, v] of Object.entries(window._swalCurrentLocMap)) {
          if (k.trim().toLowerCase() === locName.toLowerCase()) {
            qty = v;
            break;
          }
        }
      }
      const isSelected = (locName.toLowerCase() === currentToVal) ? 'selected' : '';
      toHtml += `<option value="${escapeHtml(locName)}" ${isSelected}>${escapeHtml(locName)} (${qty.toFixed(2)} kg)</option>`;
    }
  });
  toSelect.innerHTML = toHtml;
  
  if (currentToVal && currentToVal === selectedFrom) {
    toSelect.value = '';
  }
};

async function showLocationBreakdown(el) {
  const pId = el.getAttribute('data-product');
  const grade = el.getAttribute('data-grade') || 'NONE';
  const stage = el.getAttribute('data-stage');
  const key = `${pId}_${grade}_${stage}`;

  const row = el.closest('tr');
  const prodDetail = row && row.cells[0] ? row.cells[0].innerText.trim() : '';

  // Fetch available locations from DB
  let allLocations = [];
  try {
    const locRes = await fetch(window.baseUrl + '/' + window.userSlug + '/api/locations');
    const locData = await locRes.json();
    if (locData.success && locData.locations) {
      allLocations = locData.locations;
    }
  } catch (e) {
    console.error('Failed to load locations', e);
  }

  const defaultLocNames = ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
  const existingNames = new Set((allLocations || []).map(l => (l.name || '').trim().toLowerCase()));
  defaultLocNames.forEach((name, idx) => {
    if (!existingNames.has(name.toLowerCase())) {
      allLocations.push({ id: 1000 + idx, name });
    }
  });
  
  const mappings = getStoredLocationMappings();
  const totalStockQty = getAvailableStockForLocationCell(el);
  const locMap = mappings[key] || {};

  window._swalAllLocations = allLocations;
  window._swalCurrentLocMap = locMap;

  let locationsListHtml = Object.entries(locMap).map(([loc, qty]) => `
    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:#f9fafb; border-radius:8px; margin-bottom:6px;">
      <span style="font-weight:600; color:#333;">📍 ${escapeHtml(loc)}</span>
      <span style="font-weight:bold; color:var(--secondary);">${qty.toFixed(2)} kg</span>
    </div>
  `).join('') || '<p style="text-align:center; color:#6b7280; margin: 1rem 0;">No locations linked yet.</p>';

  const fromEntries = Object.entries(locMap).filter(([loc, qty]) => qty > 0);

  let transferHtml = '';
  if (fromEntries.length > 0) {
    const defaultFromLoc = fromEntries[0][0];
    const defaultFromLower = defaultFromLoc.trim().toLowerCase();

    let initialToOptionsHtml = allLocations
      .filter(loc => (loc.name || '').trim().toLowerCase() !== defaultFromLower)
      .map(loc => {
        const locName = (loc.name || '').trim();
        let qty = 0;
        for (const [k, v] of Object.entries(locMap)) {
          if (k.trim().toLowerCase() === locName.toLowerCase()) {
            qty = v;
            break;
          }
        }
        return `<option value="${escapeHtml(locName)}">${escapeHtml(locName)} (${qty.toFixed(2)} kg)</option>`;
      }).join('');

    transferHtml = `
      <div style="border-top:1px dashed var(--border-soft); padding-top:1rem; margin-top:1rem; text-align:left;">
        <label style="display:block; font-size:0.8rem; color:#6b7280; margin-bottom:0.5rem;">Transfer Stock</label>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
          <select id="swal-transfer-from" onchange="onTransferFromChange(this)" style="flex:1; min-width:100px; padding:0.45rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:6px; font-size:0.8rem;">
            ${fromEntries.map(([loc, qty], idx) => `<option value="${escapeHtml(loc)}" ${idx === 0 ? 'selected' : ''}>${escapeHtml(loc)} (${qty.toFixed(2)} kg)</option>`).join('')}
          </select>
          <span style="color:#6b7280; font-size:0.8rem;">➡</span>
          <select id="swal-transfer-to" style="flex:1; min-width:100px; padding:0.45rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:6px; font-size:0.8rem;">
            <option value="" disabled selected>To Location</option>
            ${initialToOptionsHtml}
          </select>
          <input type="number" id="swal-transfer-qty" min="0.01" step="0.01" placeholder="Qty (kg)" style="width:80px; padding:0.45rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:6px; font-size:0.8rem;">
          <button class="btn btn-sm" onclick="transferLocationMapping('${pId}', '${stage}', '${grade}', this)" style="padding:0.45rem 0.8rem;">Transfer</button>
        </div>
      </div>
    `;
  }

  Swal.fire({
    title: '📍 Stock Storage Locations',
    html: `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; font-size:0.85rem; color:#333; background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:10px 14px; flex-wrap:wrap; gap:0.5rem;">
        <div style="text-align:left;">
          <span style="color:#6b7280; font-size:0.75rem; display:block; text-transform:uppercase; font-weight:600;">Product</span>
          <strong style="color:var(--text-color); font-size:0.95rem;">${escapeHtml(prodDetail)}</strong>
        </div>
        <div style="text-align:right;">
          <span style="color:#6b7280; font-size:0.75rem; display:block; text-transform:uppercase; font-weight:600;">Total Stock</span>
          <strong style="color:var(--secondary); font-size:0.95rem;">${totalStockQty.toFixed(2)} kg</strong>
        </div>
      </div>
      <div style="margin-bottom:1rem; max-height:200px; overflow-y:auto;">
        ${locationsListHtml}
      </div>
      ${transferHtml}
    `,
    showConfirmButton: false,
    showCancelButton: true,
    cancelButtonText: 'Close',
    customClass: {
      popup: 'swal-stock-popup'
    }
  });
}

window.addLocationMapping = async function(productId, stage, grade, remainingQty, buttonEl) {
  const toLocation = document.getElementById('swal-loc-select').value;
  const quantity = parseFloat(document.getElementById('swal-loc-qty').value);
  if(!toLocation || isNaN(quantity) || quantity <= 0) {
    Swal.showValidationMessage('Please select location and enter positive quantity');
    return;
  }
  if(quantity > remainingQty) {
    Swal.showValidationMessage(`Quantity cannot exceed remaining stock (${remainingQty.toFixed(2)} kg).`);
    return;
  }

  try {
    const res = await fetch(window.baseUrl + '/' + window.userSlug + '/api/stock/locations/transfer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage: stage,
        grade: grade,
        from_location: null,
        to_location: toLocation,
        quantity: quantity
      })
    });
    const data = await res.json();
    if(data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Saved',
        background: '#ffffff',
        color: '#333333',
        timer: 1000,
        showConfirmButton: false
      }).then(() => {
        fetchLiveStock();
      });
    } else {
      Swal.showValidationMessage(data.message || 'Failed to save mapping');
    }
  } catch(e) {
    Swal.showValidationMessage('Network error: ' + e.message);
  }
}

window.transferLocationMapping = async function(productId, stage, grade, buttonEl) {
  const fromLocation = document.getElementById('swal-transfer-from').value;
  const toLocation = document.getElementById('swal-transfer-to').value;
  const quantity = parseFloat(document.getElementById('swal-transfer-qty').value);
  
  if(!fromLocation || !toLocation || isNaN(quantity) || quantity <= 0) {
    Swal.showValidationMessage('Please select both locations and enter a positive quantity.');
    return;
  }
  if(fromLocation === toLocation) {
    Swal.showValidationMessage('From and To locations must be different.');
    return;
  }

  try {
    const res = await fetch(window.baseUrl + '/' + window.userSlug + '/api/stock/locations/transfer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage: stage,
        grade: grade,
        from_location: fromLocation,
        to_location: toLocation,
        quantity: quantity
      })
    });
    const data = await res.json();
    if(data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Transferred',
        background: '#ffffff',
        color: '#333333',
        timer: 1000,
        showConfirmButton: false
      }).then(() => {
        fetchLiveStock();
      });
    } else {
      Swal.showValidationMessage(data.message || 'Failed to transfer stock');
    }
  } catch(e) {
    Swal.showValidationMessage('Network error: ' + e.message);
  }
}

function adminExportStockPdf() {
  Swal.fire({
    title: '📄 EXPORT STOCK VALUATION PDF',
    html: `
      <div style="text-align:left; font-size:0.95rem; color:#333;">
        <p style="margin-bottom:12px; color:#6b7280;">Select the stock panels to include in the PDF report:</p>
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-stage-raw" checked style="width:20px; height:20px; cursor:pointer;"> 🌿 Raw Material Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-stage-semi" checked style="width:20px; height:20px; cursor:pointer;"> ⚗️ Semi-Finished Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-stage-finished" checked style="width:20px; height:20px; cursor:pointer;"> ✅ FG Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-stage-packaging" checked style="width:20px; height:20px; cursor:pointer;"> 📦 Packaging Materials
          </label>
        </div>
        
        <div style="margin-bottom:10px;">
          <label class="field-label">
            DATE <span style="font-weight:400; text-transform:none; color:#94a3b8 !important; -webkit-text-fill-color:#94a3b8 !important;">(OPTIONAL)</span>
          </label>
          <input type="date" id="export-date" style="width: 100%;">
        </div>
        <p style="margin-top:8px; font-size:0.82rem; color:#94a3b8 !important; -webkit-text-fill-color:#94a3b8 !important; font-style:italic;">Leave empty to generate live stock report for today.</p>
      </div>
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'GENERATE REPORT',
    cancelButtonText: 'CANCEL',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    customClass: {
      popup: 'swal-stock-popup',
      title: 'swal-stock-title',
      confirmButton: 'swal-confirm-btn-primary',
      cancelButton: 'swal-cancel-btn-secondary'
    },
    preConfirm: () => {
      const raw = document.getElementById('export-stage-raw').checked;
      const semi = document.getElementById('export-stage-semi').checked;
      const finished = document.getElementById('export-stage-finished').checked;
      const packaging = document.getElementById('export-stage-packaging')?.checked;
      const selectedDate = document.getElementById('export-date').value;
      
      const stages = [];
      if (raw) stages.push('RAW');
      if (semi) stages.push('SEMI');
      if (finished) stages.push('FINISHED');
      if (packaging) stages.push('PACKAGING');
      
      if (stages.length === 0) {
        Swal.showValidationMessage('Please select at least one stock panel.');
        return false;
      }

      return { stages, selectedDate };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    
    const { stages, selectedDate } = result.value;
    const btn = document.querySelector('button[onclick="adminExportStockPdf()"]');
    
    window.downloadPdfAsync('{{ route(request()->segment(1) . ".stock.pdf") }}', {
      stages: stages.join(','),
      date: selectedDate
    }, btn);
  });
}

function adminExportStockCsv() {
  Swal.fire({
    title: '📊 EXPORT STOCK REPORT (CSV)',
    html: `
      <div style="text-align:left; font-size:0.95rem; color:#333;">
        <p style="margin-bottom:12px; color:#6b7280;">Select the stock panels to export to CSV (Excel compatible):</p>
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-csv-stage-raw" checked style="width:20px; height:20px; cursor:pointer;"> 🌿 Raw Material Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-csv-stage-semi" checked style="width:20px; height:20px; cursor:pointer;"> ⚗️ Semi-Finished Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-csv-stage-finished" checked style="width:20px; height:20px; cursor:pointer;"> ✅ FG Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; color:#333333 !important;">
            <input type="checkbox" id="export-csv-stage-packaging" checked style="width:20px; height:20px; cursor:pointer;"> 📦 Packaging Materials
          </label>
        </div>
        
        <div style="margin-bottom:10px;">
          <label class="field-label" style="font-weight:600; font-size:0.8rem; color:#475569; display:block; margin-bottom:4px;">
            DATE <span style="font-weight:400; text-transform:none; color:#94a3b8 !important;">(OPTIONAL)</span>
          </label>
          <input type="date" id="export-csv-date" style="width: 100%; padding:0.4rem 0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
        </div>
        <p style="margin-top:8px; font-size:0.82rem; color:#94a3b8 !important; font-style:italic;">Leave empty to export live stock report for today.</p>
      </div>
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'DOWNLOAD CSV',
    cancelButtonText: 'CANCEL',
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#9ca3af',
    customClass: {
      popup: 'swal-stock-popup',
      title: 'swal-stock-title',
      confirmButton: 'swal-confirm-btn-primary',
      cancelButton: 'swal-cancel-btn-secondary'
    },
    preConfirm: () => {
      const raw = document.getElementById('export-csv-stage-raw').checked;
      const semi = document.getElementById('export-csv-stage-semi').checked;
      const finished = document.getElementById('export-csv-stage-finished').checked;
      const packaging = document.getElementById('export-csv-stage-packaging').checked;
      const selectedDate = document.getElementById('export-csv-date').value;
      
      const stages = [];
      if (raw) stages.push('RAW');
      if (semi) stages.push('SEMI');
      if (finished) stages.push('FINISHED');
      if (packaging) stages.push('PACKAGING');
      
      if (stages.length === 0) {
        Swal.showValidationMessage('Please select at least one stock panel.');
        return false;
      }

      return { stages, selectedDate };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    
    const { stages, selectedDate } = result.value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route(request()->segment(1) . ".stock.csv") }}';
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    const stageInput = document.createElement('input');
    stageInput.type = 'hidden';
    stageInput.name = 'stages';
    stageInput.value = stages.join(',');
    form.appendChild(stageInput);
    
    if (selectedDate) {
      const dateInput = document.createElement('input');
      dateInput.type = 'hidden';
      dateInput.name = 'date';
      dateInput.value = selectedDate;
      form.appendChild(dateInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    setTimeout(() => {
      if (document.body.contains(form)) {
        document.body.removeChild(form);
      }
    }, 1000);
  });
}



window.initBsProductSelect2 = function(selectElement) {
  if (!selectElement) return;
  const $select = $(selectElement);
  if (!$select.length) return;

  if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
    setTimeout(() => window.initBsProductSelect2(selectElement), 50);
    return;
  }

  if ($select.hasClass('select2-hidden-accessible')) {
    try {
      $select.select2('destroy');
    } catch (e) {}
  }

  $select.select2({
    placeholder: 'SELECT PRODUCT...',
    allowClear: false,
    width: '100%',
    dropdownAutoWidth: false,
    dropdownCssClass: 'bs-product-select2-dropdown',
    matcher: function(params, data) {
      if (!params.term || $.trim(params.term) === '') {
        return data;
      }
      if (!data.text) {
        return null;
      }
      const term = params.term.toLowerCase().trim();
      const text = data.text.toLowerCase();
      const tokens = term.split(/\s+/).filter(Boolean);
      for (let i = 0; i < tokens.length; i++) {
        if (text.indexOf(tokens[i]) === -1) {
          return null;
        }
      }
      return data;
    },
    templateResult: function(data) {
      if (!data.id) {
        return $(`<span style="color:#9ca3af; font-weight:500;">${escapeHtml(data.text)}</span>`);
      }
      const el = data.element;
      const stage = el ? el.getAttribute('data-stage') : '';
      const unit = el ? el.getAttribute('data-unit') : '';
      const grade = el ? el.getAttribute('data-grade') : '';
      const prodName = el ? el.getAttribute('data-name') : data.text;

      let badgeColor = '#6b7280';
      let badgeBg = '#f3f4f6';
      let badgeBorder = '#d1d5db';
      if (stage === 'RAW') { badgeColor = '#065f46'; badgeBg = '#d1fae5'; badgeBorder = '#a7f3d0'; }
      else if (stage === 'SEMI') { badgeColor = '#1e40af'; badgeBg = '#dbeafe'; badgeBorder = '#bfdbfe'; }
      else if (stage === 'FINISHED' || stage === 'FG') { badgeColor = '#92400e'; badgeBg = '#fef3c7'; badgeBorder = '#fde68a'; }
      else if (stage === 'PACKAGING' || stage === 'PKG') { badgeColor = '#0369a1'; badgeBg = '#e0f2fe'; badgeBorder = '#bae6fd'; }

      let gradeBadge = '';
      if (grade && !['NONE', 'N/A', 'NA', 'N / A'].includes(grade.trim().toUpperCase())) {
        gradeBadge = `<span class="prod-grade-badge" style="font-size:0.7rem; font-weight:700; padding:1px 6px; border-radius:4px; background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; margin-left:4px;">${escapeHtml(grade)}</span>`;
      }

      const displayStage = (stage === 'FINISHED' ? 'FG' : (stage === 'PACKAGING' ? 'PKG' : stage));

      return $(`
        <div class="prod-option-row" style="display:flex; justify-content:space-between; align-items:center; width:100%; padding:2px 0;">
          <div style="display:flex; align-items:center; gap:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
            <span class="prod-name" style="font-weight:700; color:#111827;">${escapeHtml(prodName)}</span>
            ${gradeBadge}
          </div>
          <div style="display:flex; align-items:center; gap:6px; flex-shrink:0; margin-left:8px;">
            <span class="prod-stage-badge" style="font-size:0.68rem; font-weight:700; padding:1px 6px; border-radius:4px; background:${badgeBg}; color:${badgeColor}; border:1px solid ${badgeBorder};">${displayStage}</span>
            ${unit ? `<span class="prod-unit" style="font-size:0.72rem; color:#4b5563; font-weight:600;">(${escapeHtml(unit)})</span>` : ''}
          </div>
        </div>
      `);
    },
    templateSelection: function(data) {
      if (!data.id) return data.text;
      return data.text;
    }
  });

  $select.off('select2:open.bsFocus').on('select2:open.bsFocus', function() {
    setTimeout(() => {
      const searchBox = document.querySelector('.bs-product-select2-dropdown .select2-search__field, .select2-container--open .select2-search__field');
      if (searchBox) {
        searchBox.focus();
      }
    }, 15);
  });
};

window.onBsStageChange = function(element) {
  const row = element.closest('.bulk-stock-row');
  const stage = row.querySelector('.bs-stage').value;
  const productSelect = row.querySelector('.bs-product');
  
  const targetStage = (stage === 'FG' ? 'FINISHED' : stage);
  let filteredProducts = [];
  if (targetStage === 'ALL') {
    filteredProducts = adminStockProducts.filter(p => p.is_active);
  } else {
    filteredProducts = adminStockProducts.filter(p => (p.type === targetStage || (targetStage === 'FINISHED' && p.type === 'FG')) && p.is_active);
  }
  
  if (window.jQuery && $.fn.select2 && $(productSelect).hasClass('select2-hidden-accessible')) {
    try {
      $(productSelect).select2('destroy');
    } catch(e) {}
  }

  // Empty the select and add a blank option for placeholder
  $(productSelect).empty();
  
  const placeholderOpt = new Option('SELECT PRODUCT...', '', true, true);
  placeholderOpt.disabled = true;
  $(productSelect).append(placeholderOpt);
  
  // Append new options dynamically
  filteredProducts.forEach(p => {
    let t = (p.type === 'FINISHED' || p.type === 'FG') ? 'FG' : p.type;
    if (p.grades && p.grades.length > 0) {
      p.grades.forEach(g => {
        const val = `${p.id}|${g.name}`;
        const isDef = !g.name || ['NONE', 'N/A', 'NA', 'N / A'].includes(g.name.trim().toUpperCase());
        const gradeText = !isDef ? `_${g.name}` : '';
        const text = `${p.name}${gradeText} (${t})`;
        const opt = new Option(text, val, false, false);
        opt.setAttribute('data-unit', p.unit || 'kg');
        opt.setAttribute('data-stage', t);
        opt.setAttribute('data-name', p.name);
        opt.setAttribute('data-grade', g.name || 'NONE');
        $(productSelect).append(opt);
      });
    } else {
      const val = `${p.id}|NONE`;
      const text = `${p.name} (${t})`;
      const opt = new Option(text, val, false, false);
      opt.setAttribute('data-unit', p.unit || 'kg');
      opt.setAttribute('data-stage', t);
      opt.setAttribute('data-name', p.name);
      opt.setAttribute('data-grade', 'NONE');
      $(productSelect).append(opt);
    }
  });

  // Re-initialize Select2 Smart Search
  window.initBsProductSelect2(productSelect);
  
  $(productSelect).off('change.bsProd').on('change.bsProd', function() {
    onBsProductChange(this);
  });
  
  onBsProductChange(productSelect);
};

window.onBsProductChange = function(element) {
  const row = element.closest('.bulk-stock-row');
  const val = element.value;
  if (!val) return;
  const [productId, gradeName] = val.split('|');
  const prod = adminStockProducts.find(p => String(p.id) === String(productId));
  if (prod) {
    const rateInput = row.querySelector('.bs-rate');
    if (rateInput && (!rateInput.value || rateInput.value === '0') && prod.rate) {
      rateInput.value = prod.rate;
    }
    const minQtyInput = row.querySelector('.bs-min-qty');
    if (minQtyInput && (!minQtyInput.value || minQtyInput.value === '0') && prod.threshold) {
      minQtyInput.value = prod.threshold;
    }
  }
};

window.toggleStockFormCard = function() {
  const card = document.getElementById('stock-form-card');
  if (card) {
    const isOpening = card.style.display === 'none';
    card.style.display = isOpening ? 'block' : 'none';
    if (isOpening) {
      card.scrollIntoView({ behavior: 'smooth' });
      // Re-initialize all Select2 instances on visible card
      document.querySelectorAll('.bulk-stock-row .bs-product').forEach(sel => {
        window.initBsProductSelect2(sel);
      });
    }
  }
};

window.adminSaveBulkStock = function() {
  const btn = document.getElementById('btn-save-stock-card');
  const rows = document.querySelectorAll('.bulk-stock-row');
  const fallbackDate = '{{ date('Y-m-d') }}';
  
  if (rows.length === 0) {
    Swal.fire('Error', 'No products to add.', 'error');
    return;
  }
  
  const items = [];
  let hasError = false;
  
  rows.forEach(row => {
    const val = row.querySelector('.bs-product').value;
    const [productId, gradeVal] = val ? val.split('|') : ['', 'NONE'];
    let stage = row.querySelector('.bs-stage').value;
    if (stage === 'ALL') {
      const prod = adminStockProducts.find(p => String(p.id) === String(productId));
      stage = prod ? (prod.type === 'FG' ? 'FINISHED' : prod.type) : 'RAW';
    } else if (stage === 'FG') {
      stage = 'FINISHED';
    }
    let grade = gradeVal || 'NONE';
    const rowDate = (row.querySelector('.bs-date') && row.querySelector('.bs-date').value) ? row.querySelector('.bs-date').value : fallbackDate;
    const alertLimit = parseFloat(row.querySelector('.bs-min-qty').value);
    const rate = parseFloat(row.querySelector('.bs-rate') ? row.querySelector('.bs-rate').value : NaN);
    const note = row.querySelector('.bs-note').value.trim();
    const locInputs = row.querySelectorAll('.loc-qty-input');
    const locations = [];
    locInputs.forEach(input => {
      const qty = parseFloat(input.value) || 0;
      if (qty > 0) {
        locations.push({ name: input.getAttribute('data-loc'), qty: qty });
      }
    });
    
    if (productId && locations.length > 0) {
      items.push({
        product_id: productId,
        stage: stage,
        grade: grade,
        date: rowDate,
        alert_limit: isNaN(alertLimit) ? null : alertLimit,
        rate: isNaN(rate) ? null : rate,
        note: note,
        locations: locations
      });
    } else {
      hasError = true;
    }
  });
  
  if (items.length === 0) {
    Swal.fire('Validation Error', 'Please select a product and enter a quantity (>0) with at least one storage location.', 'error');
    return;
  }
  
  if (hasError) {
    Swal.fire('Incomplete Rows', 'Some rows have missing product or storage location quantities. Please complete or remove incomplete rows before saving.', 'warning');
    return;
  }
  
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  fetch(window.baseUrl + '/' + window.userSlug + '/stock/bulk-add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ items, date: items[0]?.date || fallbackDate })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // 1. Reset form (but don't hide it)
      document.querySelectorAll('.loc-qty-input').forEach(inp => inp.value = '0');
      document.querySelector('.bs-loc-qty').value = '';
      const btnText = document.querySelector('.loc-dropdown-text');
      if (btnText) btnText.innerHTML = '📍 Select Locations <span style="float:right;">▼</span>';

      // 2. Refresh page instantly
      sessionStorage.setItem('keepStockFormOpen', 'true');
      location.reload();

      // 3. Show success message (will not show due to reload, but kept for logic)
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: 'Stock entries added successfully!',
        timer: 1000,
        showConfirmButton: false,
        background: '#ffffff',
        color: '#333333'
      });
    } else {
      Swal.fire('Error', data.message || 'Something went wrong.', 'error');
    }
  })
  .catch(err => {
    console.error(err);
    Swal.fire('Error', 'Server error while saving.', 'error');
  })
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Save Stock';
  });
};

document.addEventListener('input', function(e) {
  if (e.target.classList.contains('loc-qty-input')) {
    const row = e.target.closest('.bulk-stock-row');
    if (!row) return;
    
    const inputs = row.querySelectorAll('.loc-qty-input');
    let total = 0;
    let selectedLocs = [];
    
    inputs.forEach(input => {
      const val = parseFloat(input.value) || 0;
      if (val > 0) {
        total += val;
        selectedLocs.push(input.getAttribute('data-loc'));
      }
    });
    
    const totalInput = row.querySelector('.bs-loc-qty');
    if (totalInput) {
      totalInput.value = total > 0 ? total : '';
    }
    
    const btnText = row.querySelector('.loc-dropdown-text');
    if (btnText) {
      if (selectedLocs.length === 0) {
        btnText.textContent = 'Main Warehouse';
      } else if (selectedLocs.length === 1) {
        btnText.textContent = selectedLocs[0];
      } else {
        btnText.textContent = selectedLocs.length + ' Locations';
      }
    }
  }
});

document.addEventListener('click', function(e) {
  if (!e.target.closest('.custom-location-dropdown')) {
    document.querySelectorAll('.custom-location-dropdown .dropdown-menu').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // Initialize the single form on load
  const stageSelect = document.querySelector('#single-stock-row .bs-stage');
  if (stageSelect) {
    onBsStageChange(stageSelect);
  }
  
  // Instantly update all location labels on page load
  updateAllLocationLabels();

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('low_stock') === '1' || urlParams.get('filter') === 'low') {
    toggleLowStockOnly();
  }

  if (sessionStorage.getItem('keepStockFormOpen') === 'true') {
    const el = document.getElementById('stock-form-card');
    if (el) el.style.display = 'block';
    sessionStorage.removeItem('keepStockFormOpen');
    document.querySelectorAll('.bulk-stock-row .bs-product').forEach(sel => {
      window.initBsProductSelect2(sel);
    });
  }
});

function addStockRow() {
    const wrapper = document.getElementById('stock-rows-wrapper');
    const firstRow = wrapper.querySelector('.bulk-stock-row');
    const newRow = firstRow.cloneNode(true);
    
    // Clear inputs in new row
    newRow.querySelectorAll('input').forEach(inp => {
        if (inp.type === 'number' || inp.type === 'text') inp.value = '';
    });
    // Set location quantities to 0
    newRow.querySelectorAll('.loc-qty-input').forEach(inp => inp.value = '0');
    
    const dropdownText = newRow.querySelector('.loc-dropdown-text');
    if (dropdownText) dropdownText.textContent = 'Main Warehouse';
    
    // Set date of new row to previous row's date or today
    const prevDate = firstRow.querySelector('.bs-date')?.value || '{{ date('Y-m-d') }}';
    const dateInput = newRow.querySelector('.bs-date');
    if (dateInput) {
        dateInput.value = prevDate;
    }
    
    // Remove cloned Select2 container from newRow
    newRow.querySelectorAll('.select2-container').forEach(el => el.remove());
    
    // Clean all select elements and options in newRow
    newRow.querySelectorAll('select').forEach(sel => {
        sel.classList.remove('select2-hidden-accessible');
        sel.removeAttribute('data-select2-id');
        sel.removeAttribute('tabindex');
        sel.removeAttribute('aria-hidden');
        sel.removeAttribute('id');
        sel.selectedIndex = 0;
    });
    newRow.querySelectorAll('option').forEach(opt => {
        opt.removeAttribute('data-select2-id');
    });
    
    // Hide grade wrapper initially if present
    const gradeWrapper = newRow.querySelector('.bs-grade-wrapper');
    if (gradeWrapper) gradeWrapper.style.display = 'none';

    // Add remove button
    let actionsDiv = newRow.querySelector('.row-actions');
    if (!actionsDiv) {
        actionsDiv = document.createElement('div');
        actionsDiv.className = 'row-actions form-group bs-col-actions';
        newRow.querySelector('.bulk-stock-fields').appendChild(actionsDiv);
    }
    actionsDiv.innerHTML = `<button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.bulk-stock-row').remove()" style="height: 1.8rem; padding: 0 0.55rem; background: #dc3545; color: white; border: none; font-weight: bold; line-height: 1.8rem; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 0.85rem;" title="Remove row">✖</button>`;

    // Add top border/margin to separate rows
    newRow.style.borderTop = '1px dashed #d1d5db';
    newRow.style.paddingTop = '1rem';
    newRow.style.marginTop = '1rem';

    // Remove any leftover IDs
    newRow.removeAttribute('id');

    wrapper.appendChild(newRow);
    
    // Trigger onBsStageChange to re-populate products and init select2 on newRow
    const stageSelect = newRow.querySelector('.bs-stage');
    if (typeof onBsStageChange === 'function' && stageSelect) {
        onBsStageChange(stageSelect);
    }
}
</script>

<style>
/* Hide spin arrows on location quantity inputs */
.loc-qty-input::-webkit-outer-spin-button,
.loc-qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.loc-qty-input {
  -moz-appearance: textfield;
}

/* High Contrast Overrides for Stock SweetAlert Modals */
.swal-stock-popup,
.swal2-popup.swal-stock-popup {
  background-color: #ffffff !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 16px !important;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
  padding: 1.5rem !important;
}

.swal-stock-popup .swal2-title,
.swal-stock-title,
.swal2-title {
  color: #333333 !important;
  -webkit-text-fill-color: #333333 !important;
  font-weight: 700 !important;
  font-size: 1.35rem !important;
  margin-bottom: 1rem !important;
}

.swal-stock-popup .swal2-html-container,
.swal-stock-popup .swal2-html-container p,
.swal-stock-popup .swal2-html-container div {
  color: #333333 !important;
  -webkit-text-fill-color: #333333 !important;
}

/* Checkbox Card Option Containers & Labels */
.swal-stock-popup label,
.swal-stock-popup label span,
.swal-stock-popup .export-option-card,
.swal-stock-popup .export-option-card span {
  color: #333333 !important;
  -webkit-text-fill-color: #333333 !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
}

.swal-stock-popup .export-option-card {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  cursor: pointer !important;
  background-color: #f9fafb !important;
  border: 1.5px solid #d1d5db !important;
  padding: 12px 16px !important;
  border-radius: 10px !important;
  margin-bottom: 8px !important;
  transition: all 0.2s ease !important;
}

.swal-stock-popup .export-option-card:hover {
  background-color: #f3f4f6 !important;
  border-color: #f59e0b !important;
}

.swal-stock-popup .export-option-card input[type="checkbox"] {
  width: 20px !important;
  height: 20px !important;
  accent-color: #f59e0b !important;
  cursor: pointer !important;
}

/* Field Labels */
.swal-stock-popup .field-label {
  color: #4b5563 !important;
  -webkit-text-fill-color: #4b5563 !important;
  font-size: 0.85rem !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  margin-bottom: 0.4rem !important;
  display: block !important;
}

/* Inputs & Date Pickers */
.swal-stock-popup input[type="date"],
.swal-stock-popup input[type="text"],
.swal-stock-popup input[type="number"],
.swal-stock-popup select,
.swal-stock-popup textarea {
  background-color: #f9fafb !important;
  border: 1.5px solid #d1d5db !important;
  color: #333333 !important;
  -webkit-text-fill-color: #333333 !important;
  color-scheme: light !important;
  font-size: 0.95rem !important;
  font-weight: 600 !important;
  padding: 0.65rem 0.8rem !important;
  border-radius: 8px !important;
  width: 100% !important;
  box-sizing: border-box !important;
}

.swal-stock-popup input[type="date"]:focus,
.swal-stock-popup input[type="text"]:focus,
.swal-stock-popup input[type="number"]:focus,
.swal-stock-popup select:focus,
.swal-stock-popup textarea:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245,158,11,0.25) !important;
  outline: none !important;
}

.swal-stock-popup input[type="date"]::-webkit-calendar-picker-indicator {
  filter: brightness(0) opacity(0.7) !important;
  cursor: pointer !important;
}

.swal2-validation-message {
  background-color: #fee2e2 !important;
  color: #991b1b !important;
  -webkit-text-fill-color: #991b1b !important;
  border: 1px solid #f87171 !important;
  border-radius: 8px !important;
  margin-top: 1rem !important;
}

.swal-confirm-btn-primary {
  background-color: #f59e0b !important;
  color: #000000 !important;
  -webkit-text-fill-color: #000000 !important;
  font-weight: 700 !important;
  border-radius: 8px !important;
  padding: 0.65rem 1.4rem !important;
  border: none !important;
}

.swal-confirm-btn-primary:hover {
  background-color: #d97706 !important;
}

.swal-cancel-btn-secondary {
  background-color: #e5e7eb !important;
  color: #374151 !important;
  -webkit-text-fill-color: #374151 !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  padding: 0.65rem 1.4rem !important;
  border: none !important;
}

.swal-cancel-btn-secondary:hover {
  background-color: #d1d5db !important;
}

/* Disable row hover effect for low stock (visual only, buttons remain clickable) */
tr.low-stock-no-hover:hover {
  background-color: inherit !important;
  color: inherit !important;
}
</style>
@endsection

<style>
/* White and Orange Theme for Forms */
.white-orange-card {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
.white-orange-card .card-title,
.white-orange-card h4 {
    color: #333333 !important;
    font-weight: 700 !important;
}
.white-orange-card label {
    color: #4b5563 !important;
    font-weight: 600 !important;
}
.white-orange-card input,
.white-orange-card select,
.white-orange-card textarea {
    background-color: #f9fafb !important;
    border: 1px solid #d1d5db !important;
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.white-orange-card input::placeholder,
.white-orange-card textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.white-orange-card .btn-primary,
.white-orange-card button[type="submit"] {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: none !important;
}
.white-orange-card .btn-secondary,
.white-orange-card button[type="button"] {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
    border: none !important;
}
.white-orange-card span {
    color: #333333 !important;
}
</style>

<style>
/* Absolute override for all text in stock popup */
.swal-stock-popup * {
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.swal-stock-popup input,
.swal-stock-popup select,
.swal-stock-popup textarea,
.swal-stock-popup option {
    background-color: #ffffff !important;
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.swal-stock-popup input::placeholder,
.swal-stock-popup textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.swal-stock-popup .swal-cancel-btn-secondary,
.swal-stock-popup .swal-cancel-btn {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
}
.swal-stock-popup .swal-confirm-btn-primary,
.swal-stock-popup .swal-confirm-btn,
.swal-stock-popup .swal2-confirm {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}
</style>
