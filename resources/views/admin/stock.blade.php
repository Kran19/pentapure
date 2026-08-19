@extends('layouts.admin')

@section('content')

<div style="padding:0.25rem 0 1rem 0;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <h2 style="margin:0;">📦 Live Stock Overview</h2>
    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
      <button class="btn btn-secondary" onclick="adminExportStockPdf()" style="width:auto; padding:0.65rem 1.2rem; border-color:#DDCFAF !important;">📄 Generate PDF Report</button>
      <button class="btn" onclick="toggleStockFormCard()" style="width:auto; padding:0.65rem 1.2rem;">+ Add Stock</button>
    </div>
  </div>

  <!-- In-Page Add / Adjust Stock Card (Hidden by Default) -->
  <div id="stock-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
      <span>📦 Add Stock Entry</span>
      <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('stock-form-card').style.display='none'" style="width:auto; padding:0.3rem 0.8rem;">✕ Close</button>
    </div>

    <div id="stock-rows-wrapper">
        <div class="bulk-stock-row" id="single-stock-row" style="padding: 1rem; margin-bottom: 1rem; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;">
        <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
            
            <div class="form-group" style="margin:0; flex: 1 1 90px;">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Stock Type *</label>
                <select class="form-control form-control-sm bs-stage" onchange="onBsStageChange(this)" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem;">
                    <option value="RAW" selected>RAW</option>
                    <option value="SEMI">SEMI</option>
                    <option value="FINISHED">FINISHED</option>
                </select>
            </div>
            
            <div class="form-group" style="margin:0; flex: 2 1 160px;">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Product *</label>
                <select class="form-control form-control-sm bs-product">
                    <option></option>
                </select>
            </div>
            


            <div class="bs-location-row" style="display:flex; gap:0.5rem; flex: 2 1 180px; margin:0; position:relative;">
                <div class="form-group" style="margin:0; flex:2;">
                    <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280; display:block;">Storage Location *</label>
                    <div class="custom-location-dropdown" style="width: 100%; position: relative;">
                        <button class="btn" type="button" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block'" style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center; background:#fff; border: 1px solid #d1d5db; height:1.8rem; padding: 0.1rem 0.5rem; font-size:0.8rem; color:#333; cursor:pointer;">
                            <span class="loc-dropdown-text">Main Warehouse</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
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
                <div class="form-group" style="margin:0; flex:1;">
                    <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Qty *</label>
                    <input type="number" min="0.001" step="0.001" class="form-control form-control-sm bs-loc-qty no-spinners" placeholder="0.00" readonly style="background-color: #f9fafb; height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem;">
                </div>
            </div>

            <div class="form-group" style="margin:0; flex: 1 1 80px;">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">MIN.QTY</label>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm bs-min-qty no-spinners" placeholder="0.00" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem;">
            </div>

            <div class="form-group" style="margin:0; flex: 1 1 80px;">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Rate</label>
                <input type="number" min="0" step="0.01" class="form-control form-control-sm bs-rate no-spinners" placeholder="0.00" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem;">
            </div>

            <div class="form-group" style="margin:0; flex: 1 1 100px;">
                <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.1rem; color:#6b7280;">Note</label>
                <input type="text" class="form-control form-control-sm bs-note" placeholder="Optional" style="height:1.8rem; padding:0.1rem 0.5rem; font-size:0.8rem;">
            </div>
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

  @php
    $typeFilter = request('type') ? strtoupper(request('type')) : null;
    $rawItems      = collect($pageData['allStock'])->where('stage', 'RAW');
    $semiItems     = collect($pageData['allStock'])->where('stage', 'SEMI');
    $finishedItems = collect($pageData['allStock'])->where('stage', 'FINISHED');
    $adminAllGrades = \App\Models\Grade::orderBy('id')->get();
  @endphp
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
    
    /* Fix Select2 visibility inside white-orange-card and make it compact */
    .select2-container .select2-selection--single .select2-selection__rendered {
        color: #333333 !important;
        font-weight: 600;
        font-size: 0.8rem;
        line-height: 1.6rem !important;
    }
    .select2-container--default .select2-selection--single {
        background-color: #f9fafb !important;
        border: 1px solid #d1d5db !important;
        height: 1.8rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 1.6rem !important;
    }
  </style>

  @if(!$typeFilter || $typeFilter === 'RAW')
  <!-- RAW Stock -->
  <div class="card" style="padding:1.2rem; margin-bottom:1rem;">
    <div class="card-title" style="color:var(--primary-light);">🌿 Raw Material Stock ({{ $rawItems->count() }} items)</div>
    @if($rawItems->isEmpty())
      <p class="text-muted text-center">No raw stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="raw-stock-tbody">
@foreach($rawItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && $s->grade !== 'NONE')_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(RAW)</span>
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
            <td style="font-weight:bold; color:var(--text-color);">{{ number_format($s->alert_limit, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="RAW" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }}, {{ $s->alert_limit }})" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Set Alert Limit" style="color:var(--danger);">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
  <div class="card" style="padding:1.2rem; margin-bottom:1rem;">
    <div class="card-title" style="color:var(--warning);">⚗️ Semi-Finished Stock ({{ $semiItems->count() }} items)</div>
    @if($semiItems->isEmpty())
      <p class="text-muted text-center">No semi stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="semi-stock-tbody">
          @foreach($semiItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && $s->grade !== 'NONE')_<strong>{{ $s->grade }}</strong>@endif <span style='font-weight:bold;'>(SEMI)</span>
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
            <td style="font-weight:bold; color:var(--text-color);">{{ number_format($s->alert_limit, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="SEMI" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }}, {{ $s->alert_limit }})" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Set Alert Limit" style="color:var(--danger);">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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

  @if(!$typeFilter || $typeFilter === 'FINISHED')
  <!-- Finished Stock -->
  <div class="card" style="padding:1.2rem;">
    <div class="card-title" style="color:var(--secondary);">✅ FG Stock ({{ $finishedItems->count() }} items)</div>
    @if($finishedItems->isEmpty())
      <p class="text-muted text-center">No finished stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>min_qty</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="finished-stock-tbody">
          @foreach($finishedItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity <= $s->alert_limit;
          @endphp
          <tr @if($isLow) class="low-stock-row" title="Low Stock! min_qty is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:400;">
              <div style="font-weight:normal; color:var(--text-color);">
                {{ $s->name }}@if($s->grade && $s->grade !== 'NONE')_<strong>{{ $s->grade }}</strong>@endif <span>(FINISHED)</span>
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
            <td style="font-weight:bold; color:var(--text-color);">{{ number_format($s->alert_limit, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="FINISHED" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}', {{ $s->quantity }}, {{ $s->alert_limit }})" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}', '{{ addslashes($s->name) }}')" title="Set Alert Limit" style="color:var(--danger);">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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

function adminAddStock() {
  Swal.fire({
    title: 'Add Stock',
    html: `
      <div style="text-align:left;">
        <label style="font-size:0.82rem; font-weight:600; color:#6b7280;">Stock Type</label>
        <select id="add-stock-stage" onchange="onStockStageChange()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
          <option value="RAW">RAW</option>
          <option value="SEMI">SEMI</option>
          <option value="FINISHED">FINISHED</option>
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
    showCancelButton: true,
    confirmButtonText: 'Add Stock',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#9ca3af',
    didOpen: () => {
      window.onStockStageChange = function() {
        const stage = document.getElementById('add-stock-stage').value;
        const productSelect = document.getElementById('add-stock-product');
        const targetType = stage === 'RAW' ? 'RAW' : 'FINISHED';
        const filteredProducts = adminStockProducts.filter(p => p.type === targetType && p.is_active);
        
        productSelect.innerHTML = filteredProducts.map(p => {
          let t = stage.toLowerCase() === 'finished' ? 'fg' : stage.toLowerCase();
          if (p.grades && p.grades.length > 0) {
            return p.grades.map(g => {
                let gradeText = g.name !== 'NONE' ? `_${escapeHtml(g.name)}` : '';
                return `<option value="${p.id}|${g.name}" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)}${gradeText} (${t})</option>`;
            }).join('');
          } else {
            return `<option value="${p.id}|NONE" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)} (${t})</option>`;
          }
        }).join('');
        
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
      const stage = document.getElementById('add-stock-stage').value;
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
      return { product_id: productId, stage, grade, quantity, adjust_type: 'add', reason };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    fetch(window.location.origin + '/' + window.userSlug + '/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(result.value)
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        Swal.fire('Saved', d.message || 'Stock added.', 'success').then(() => fetchLiveStock());
      } else {
        Swal.fire('Error', d.message || 'Could not add stock.', 'error');
      }
    });
  });
}

function adminAdjustStock(productId, stage, grade, productName = '', currentQty = 0, currentMinQty = 0) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG' }[stage] || stage;
  const displayGrade = grade !== 'NONE' ? ` &nbsp;·&nbsp; Grade: <strong style="color:#333;">${grade}</strong>` : '';

  Swal.fire({
    title: 'Adjust Stock',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        <strong style="color:var(--primary); font-size:1.05rem;">${productName} (${stage})</strong><br>
        <span style="font-size:0.85rem;">${stageLabel}${displayGrade}</span>
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Adjustment Type
      </label>
      <select id="swal-adj-type" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:0.95rem; margin-bottom:1rem; outline:none;
      ">
        <option value="set">🎯 Set — Override to exact quantity</option>
        <option value="add">➕ Add — Increase current stock</option>
        <option value="subtract">➖ Subtract — Decrease current stock</option>
      </select>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Quantity (kg)
      </label>
      <input id="swal-qty" type="number" min="0" step="0.01" value="${currentQty}" placeholder="e.g. 150.00" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Min Qty (Alert Limit)
      </label>
      <input id="swal-min-qty" type="number" min="0" step="0.01" value="${currentMinQty}" placeholder="e.g. 50.00" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#6b7280;margin-bottom:0.35rem;">
        Reason / Note <span style="font-weight:400;">(optional)</span>
      </label>
      <textarea id="swal-reason" rows="2" placeholder="e.g. Physical count correction, spillage, etc." style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#fff; border:1px solid #d1d5db; color:#333;
        font-size:0.9rem; resize:vertical; outline:none; box-sizing:border-box;
      "></textarea>
    `,
    background: '#ffffff',
    color: '#333333',
    showCancelButton: true,
    confirmButtonText: 'Apply Adjustment',
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
      const qty    = parseFloat(document.getElementById('swal-qty').value);
      const type   = document.getElementById('swal-adj-type').value;
      const reason = document.getElementById('swal-reason').value.trim();
      const minQty = parseFloat(document.getElementById('swal-min-qty').value) || 0;

      if (isNaN(qty) || qty < 0) {
        Swal.showValidationMessage('⚠️ Please enter a valid quantity (≥ 0).');
        return false;
      }
      return { qty, type, reason, min_qty: minQty };
    }
  }).then(result => {
    if (!result.isConfirmed) return;

    const { qty, type, reason, min_qty } = result.value;

    Swal.fire({
      title: 'Applying…',
      text: 'Updating stock record.',
      allowOutsideClick: false,
      background: '#ffffff',
      color: '#333333',
      didOpen: () => Swal.showLoading()
    });

    fetch(window.location.origin + '/' + window.userSlug + '/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage,
        grade,
        quantity: qty,
        adjust_type: type,
        reason,
        min_qty
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        Swal.fire({
          icon: 'success',
          title: 'Stock Updated',
          text: d.message || 'Adjustment applied successfully.',
          background: '#ffffff',
          color: '#333333',
          confirmButtonColor: '#f59e0b',
          timer: 2000,
          timerProgressBar: true,
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
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Could not reach the server. Please try again.',
        background: '#ffffff',
        color: '#333333',
        confirmButtonColor: '#f59e0b',
      });
    });
  });
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
      fetch(window.location.origin + '/' + window.userSlug + '/stock/rate', {
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
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG' }[stage] || stage;
  const displayGrade = grade !== 'NONE' ? ` &nbsp;·&nbsp; Grade: <strong style="color:#333;">${grade}</strong>` : '';

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

    fetch(window.location.origin + '/' + window.userSlug + '/stock/limit', {
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
  fetch(window.location.origin + '/' + window.userSlug + '/stock/live', {
    headers: { 'Accept': 'application/json' }
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
  const stages = { 'RAW': 'raw-stock-tbody', 'SEMI': 'semi-stock-tbody', 'FINISHED': 'finished-stock-tbody' };
  
  // Group data by stage
  const grouped = { 'RAW': [], 'SEMI': [], 'FINISHED': [] };
  stockData.forEach(s => {
    if (grouped[s.stage]) grouped[s.stage].push(s);
  });

  for (const [stage, tbodyId] of Object.entries(stages)) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) continue;

    const items = grouped[stage];
    
    // Update headers count (optional, but good for UI consistency if we have access, maybe skip for now or just replace table body)
    if (items.length === 0) {
      // Keep existing "No stock" message if handled by blade, or just leave empty table.
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">No stock recorded yet.</td></tr>`;
      continue;
    }

    let html = '';
    items.forEach(s => {
      const limit = parseFloat(s.alert_limit) || 0;
      const qty = parseFloat(s.quantity);
      const isLow = limit > 0 && qty <= limit;
      const rowClass = isLow ? 'low-stock-row' : '';
      const titleAttr = isLow ? `title="Low Stock! min_qty is ${limit}"` : '';

      const formattedQty = qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      let qtyColor = 'var(--text-color)';
      if (stage === 'RAW') qtyColor = 'var(--secondary)';
      if (stage === 'SEMI') qtyColor = 'var(--warning)';
      if (stage === 'FINISHED') qtyColor = 'var(--secondary)';

      html += `
        <tr class="${rowClass}" ${titleAttr}>
          <td>
            <div style="font-weight:normal; color:var(--text-color);">
              ${s.name}${s.grade && s.grade !== 'NONE' ? '_<strong>' + s.grade + '</strong>' : ''}(${s.stage === 'FINISHED' ? 'FG' : s.stage})
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
          <td style="font-weight:bold; color:var(--text-color);">${limit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
          <td class="location-col" data-product="${s.productId}" data-grade="${s.grade}" data-stage="${stage}" style="cursor:pointer; text-decoration:underline; color:var(--primary-light);" onclick="showLocationBreakdown(this)">📍 View Locations</td>
          <td>
            <div style="display:flex; align-items:center; gap:0.4rem;">
              <button class="btn-icon edit" onclick="adminAdjustStock('${s.productId}', '${s.stage}', '${s.grade}', '${escapeHtml(s.name)}', ${s.quantity}, ${limit})" title="Adjust">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
              <button class="btn-icon edit" onclick="adminSetLimit('${s.productId}', '${s.stage}', '${s.grade}', '${limit}', '${escapeHtml(s.name)}')" title="Set Alert Limit" style="color:var(--danger);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </button>
              <button class="btn btn-sm" onclick="window.location.href='{{ url(request()->segment(1) . '/product') }}/' + s.productId + '/' + s.stage + '/' + s.grade + '/history'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
            </div>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = html;
  }
  updateAllLocationLabels();
}

function getStoredLocationMappings() {
  return locationMappings;
}

function parseStockNumber(value) {
  return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function getAvailableStockForLocationCell(el) {
  const row = el.closest('tr');
  return row ? parseStockNumber(row.children[2]?.textContent) : 0;
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

async function showLocationBreakdown(el) {
  const pId = el.getAttribute('data-product');
  const grade = el.getAttribute('data-grade') || 'NONE';
  const stage = el.getAttribute('data-stage');
  const key = `${pId}_${grade}_${stage}`;
  
  // Fetch available locations from DB
  let allLocations = [];
  try {
    const locRes = await fetch(window.location.origin + '/' + window.userSlug + '/api/locations');
    const locData = await locRes.json();
    if (locData.success) {
      allLocations = locData.locations;
    }
  } catch (e) {
    console.error('Failed to load locations', e);
  }

  if (allLocations.length === 0) {
    allLocations = [{ id: null, name: 'No locations available' }];
  }
  
  const mappings = getStoredLocationMappings();
  const availableQty = getAvailableStockForLocationCell(el);
  const locMap = mappings[key] || {};
  
  const assignedQty = Object.values(locMap).reduce((t, q) => t + q, 0);
  const remainingQty = Math.max(availableQty - assignedQty, 0);

  let locationsListHtml = Object.entries(locMap).map(([loc, qty]) => `
    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:#f9fafb; border-radius:8px; margin-bottom:6px;">
      <span style="font-weight:600; color:#333;">📍 ${escapeHtml(loc)}</span>
      <span style="font-weight:bold; color:var(--secondary);">${qty.toFixed(2)} kg</span>
    </div>
  `).join('') || '<p style="text-align:center; color:#6b7280; margin: 1rem 0;">No locations linked yet.</p>';

  let optionsHtml = allLocations.map(loc => `<option value="${escapeHtml(loc.name)}">${escapeHtml(loc.name)}</option>`).join('');
  
  let fromOptionsHtml = Object.entries(locMap).map(([loc, qty]) => `<option value="${escapeHtml(loc)}">${escapeHtml(loc)} (${qty} kg)</option>`).join('');
  let transferHtml = '';
  if (Object.keys(locMap).length > 0) {
    transferHtml = `
      <div style="border-top:1px dashed var(--border-soft); padding-top:1rem; margin-top:1rem; text-align:left;">
        <label style="display:block; font-size:0.8rem; color:#6b7280; margin-bottom:0.5rem;">Transfer Stock</label>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
          <select id="swal-transfer-from" style="flex:1; min-width:100px; padding:0.45rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:6px; font-size:0.8rem;">
            <option value="" disabled selected>From Location</option>
            ${fromOptionsHtml}
          </select>
          <span style="color:#6b7280; font-size:0.8rem;">➡</span>
          <select id="swal-transfer-to" style="flex:1; min-width:100px; padding:0.45rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:6px; font-size:0.8rem;">
            <option value="" disabled selected>To Location</option>
            ${optionsHtml}
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
      <div style="display:flex; justify-content:space-between; margin-bottom:1rem; font-size:0.85rem; color:#333; background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:10px; flex-wrap:wrap; gap:0.5rem;">
        <div><span style="color:#6b7280;">Available:</span> <strong>${availableQty.toFixed(2)} kg</strong></div>
        <div><span style="color:#6b7280;">Assigned:</span> <strong style="color:var(--secondary);">${assignedQty.toFixed(2)} kg</strong></div>
        <div><span style="color:#6b7280;">Unassigned:</span> <strong>${remainingQty.toFixed(2)} kg</strong></div>
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
    const res = await fetch(window.location.origin + '/' + window.userSlug + '/api/stock/locations/transfer', {
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
    const res = await fetch(window.location.origin + '/' + window.userSlug + '/api/stock/locations/transfer', {
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
      const selectedDate = document.getElementById('export-date').value;
      
      const stages = [];
      if (raw) stages.push('RAW');
      if (semi) stages.push('SEMI');
      if (finished) stages.push('FINISHED');
      
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



window.onBsStageChange = function(element) {
  const row = element.closest('.bulk-stock-row');
  const stage = row.querySelector('.bs-stage').value;
  const productSelect = row.querySelector('.bs-product');
  
  const targetType = stage; // 'RAW', 'SEMI', or 'FINISHED'
  const filteredProducts = adminStockProducts.filter(p => p.type === targetType && p.is_active);
  
  // Empty the select and add a blank option for the placeholder
  $(productSelect).empty();
  
  // Add a disabled placeholder option for standard select
  const placeholderOpt = new Option('SELECT PRODUCT...', '', false, false);
  placeholderOpt.disabled = true;
  placeholderOpt.selected = true;
  $(productSelect).append(placeholderOpt);
  
  // Append new options dynamically
  filteredProducts.forEach(p => {
    let t = targetType === 'FINISHED' ? 'FG' : targetType;
    if (p.grades && p.grades.length > 0) {
      p.grades.forEach(g => {
        const val = `${p.id}|${g.name}`;
        const gradeText = g.name !== 'NONE' ? `_${g.name}` : '';
        const text = `${p.name}${gradeText}(${t})`;
        const opt = new Option(text, val, false, false);
        opt.setAttribute('data-unit', p.unit || 'kg');
        $(productSelect).append(opt);
      });
    } else {
      const val = `${p.id}|NONE`;
      const text = `${p.name}(${t})`;
      const opt = new Option(text, val, false, false);
      opt.setAttribute('data-unit', p.unit || 'kg');
      $(productSelect).append(opt);
    }
  });
  
  $(productSelect).off('change').on('change', function() {
    onBsProductChange(this);
  });
  
  onBsProductChange(productSelect);
};

window.onBsProductChange = function(element) {
  // Grade dropdown removed, do nothing
};



window.toggleStockFormCard = function() {
  const card = document.getElementById('stock-form-card');
  if (card) {
    card.style.display = card.style.display === 'none' ? 'block' : 'none';
    if (card.style.display === 'block') {
      card.scrollIntoView({ behavior: 'smooth' });
    }
  }
};

window.adminSaveBulkStock = function() {
  const btn = document.getElementById('btn-save-stock-card');
  const rows = document.querySelectorAll('.bulk-stock-row');
  
  if (rows.length === 0) {
    Swal.fire('Error', 'No products to add.', 'error');
    return;
  }
  
  const items = [];
  let hasError = false;
  
  rows.forEach(row => {
    const val = row.querySelector('.bs-product').value;
    const [productId, gradeVal] = val ? val.split('|') : ['', 'NONE'];
    const stage = row.querySelector('.bs-stage').value;
    let grade = gradeVal || 'NONE';
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
    
    if (locations.length > 0) {
      items.push({
        product_id: productId,
        stage: stage,
        grade: grade,
        alert_limit: isNaN(alertLimit) ? null : alertLimit,
        rate: isNaN(rate) ? null : rate,
        note: note,
        locations: locations
      });
    } else {
      hasError = true;
    }
  });
  
  if (hasError && items.length === 0) {
    Swal.fire('Error', 'Please fill in product, quantity (>0) and at least one location for all rows.', 'error');
    return;
  }
  
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  fetch(window.location.origin + '/' + window.userSlug + '/stock/bulk-add', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ items })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // 1. Reset form (but don't hide it)
      document.querySelectorAll('.loc-qty-input').forEach(inp => inp.value = '0');
      document.querySelector('.bs-loc-qty').value = '';
      const btnText = document.querySelector('.loc-dropdown-text');
      if (btnText) btnText.innerHTML = '📍 Select Locations <span style="float:right;">▼</span>';

      // 2. Instantly update live stock
      if (typeof fetchLiveStock === 'function') fetchLiveStock();
      if (typeof fetchDashboardStats === 'function') fetchDashboardStats();

      // 3. Show success message
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
    
    // For Select2, remove cloned artifacts
    const select2Span = newRow.querySelector('.select2-container');
    if (select2Span) select2Span.remove();
    
    const bsProduct = newRow.querySelector('.bs-product');
    if (bsProduct) {
        bsProduct.classList.remove('select2-hidden-accessible');
        bsProduct.removeAttribute('data-select2-id');
        bsProduct.removeAttribute('tabindex');
        bsProduct.removeAttribute('aria-hidden');
    }
    
    // Make sure select elements are un-selected
    newRow.querySelectorAll('select').forEach(sel => {
        sel.selectedIndex = 0;
        sel.removeAttribute('data-select2-id');
    });
    
    // Hide grade wrapper initially
    const gradeWrapper = newRow.querySelector('.bs-grade-wrapper');
    if (gradeWrapper) gradeWrapper.style.display = 'none';

    // Add remove button
    let actionsDiv = newRow.querySelector('.row-actions');
    if (!actionsDiv) {
        actionsDiv = document.createElement('div');
        actionsDiv.className = 'row-actions form-group';
        actionsDiv.style.cssText = 'margin:0; display:flex; align-items:flex-end; padding-bottom: 0.1rem;';
        newRow.firstElementChild.appendChild(actionsDiv);
    }
    actionsDiv.innerHTML = `<button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.bulk-stock-row').remove()" style="height: 2rem; padding: 0 0.5rem; background: #dc3545; color: white; border: none; font-weight: bold; line-height: 1;" title="Remove row">✖</button>`;

    // Add top border/margin to separate rows
    newRow.style.borderTop = '1px dashed #d1d5db';
    newRow.style.paddingTop = '1rem';
    newRow.style.marginTop = '1rem';

    // Remove any leftover IDs
    newRow.removeAttribute('id');

    wrapper.appendChild(newRow);
    
    // Trigger onBsStageChange to re-populate products and re-init any select2
    const stageSelect = newRow.querySelector('.bs-stage');
    if (typeof onBsStageChange === 'function') {
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
