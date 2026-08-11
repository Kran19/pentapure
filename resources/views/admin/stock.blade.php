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

  <!-- In-Page Add / Adjust Stock Card (Open by Default) -->
  <div id="stock-form-card" class="card white-orange-card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
      <span>📦 Add Stock Entry</span>
      <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('stock-form-card').style.display='none'" style="width:auto; padding:0.3rem 0.8rem;">✕ Close</button>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-top:1rem;">
      <div class="form-group">
        <label>Stock Type *</label>
        <select id="card-stock-stage" onchange="onCardStockStageChange()" class="form-control" style="width:100%;">
          <option value="RAW" selected>RAW (Input Material)</option>
          <option value="SEMI">SEMI (Intermediate Production)</option>
          <option value="FINISHED">FINISHED (Packaged Goods)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Product *</label>
        <select id="card-stock-product" onchange="onCardStockProductChange()" class="form-control" style="width:100%;">
          <!-- Populated dynamically -->
        </select>
      </div>

      <div class="form-group">
        <label>Grade *</label>
        <div id="card-stock-grade-container">
          <!-- Populated dynamically -->
        </div>
      </div>

      <div class="form-group">
        <label>Storage Location</label>
        <select id="card-stock-location" class="form-control" style="width:100%;">
          <option value="Main Warehouse" selected>Main Warehouse</option>
          @php $allLocs = \App\Models\Location::orderBy('name')->get(); @endphp
          @foreach($allLocs as $loc)
            @if($loc->name !== 'Main Warehouse')
              <option value="{{ $loc->name }}">{{ $loc->name }}</option>
            @endif
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label>Quantity *</label>
        <input id="card-stock-qty" type="number" min="0.001" step="0.001" placeholder="e.g. 200.00" class="form-control">
      </div>

      <div class="form-group">
        <label>Note / Reason</label>
        <input id="card-stock-note" type="text" placeholder="Optional details (e.g. Inward arrival)" class="form-control">
      </div>
    </div>

    <div style="display:flex; gap:1rem; margin-top:1.5rem;">
      <button class="btn" id="btn-save-stock-card" onclick="adminSaveStockFromCard()" style="width:auto; padding:0.6rem 1.8rem;">Add Stock</button>
      <button class="btn btn-secondary" onclick="document.getElementById('stock-form-card').style.display='none'" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
    </div>
  </div>

  @php
    $typeFilter = request('type') ? strtoupper(request('type')) : null;
    $rawItems      = collect($pageData['allStock'])->where('stage', 'RAW');
    $semiItems     = collect($pageData['allStock'])->where('stage', 'SEMI');
    $finishedItems = collect($pageData['allStock'])->where('stage', 'FINISHED');
  @endphp

  @if(!$typeFilter || $typeFilter === 'RAW')
  <!-- RAW Stock -->
  <div class="card" style="padding:1.2rem; margin-bottom:1rem;">
    <div class="card-title" style="color:var(--primary-light);">🌿 Raw Material Stock ({{ $rawItems->count() }} items)</div>
    @if($rawItems->isEmpty())
      <p class="text-muted text-center">No raw stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="raw-stock-tbody">
@foreach($rawItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit;
            $gStr = ($s->grade && $s->grade !== 'NONE' && $s->grade !== 'N/A') ? ' - ' . strtoupper($s->grade) : '';
            $displayType = $s->stage === 'FINISHED' ? 'FG' : ($s->stage === 'SEMI' ? 'Semi-Finished' : 'Raw');
          @endphp
          <tr @if($isLow) style="background-color: rgba(220, 38, 38, 0.35) !important; color: #ffffff !important;" title="Low Stock! Threshold is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:600;">{{ $s->name }}{{ $gStr }} ({{ $displayType }})</td>
            <td @if($isLow) style="font-weight:bold; color:#333;" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="RAW" style="cursor:pointer; text-decoration:underline; @if($isLow) color:#333; @else color:var(--primary-light); @endif" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust" @if($isLow) style="color:#333;" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" @if($isLow) style="color:#ffcccc;" @else style="color:var(--danger);" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem; @if($isLow) background:rgba(255,255,255,0.2); color:#fff; border-color:transparent; @endif">Details</button>
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
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="semi-stock-tbody">
          @foreach($semiItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit;
            $gStr = ($s->grade && $s->grade !== 'NONE' && $s->grade !== 'N/A') ? ' - ' . strtoupper($s->grade) : '';
            $displayType = $s->stage === 'FINISHED' ? 'FG' : ($s->stage === 'SEMI' ? 'Semi-Finished' : 'Raw');
          @endphp
          <tr @if($isLow) style="background-color: rgba(220, 38, 38, 0.35) !important; color: #ffffff !important;" title="Low Stock! Threshold is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:600;">{{ $s->name }}{{ $gStr }} ({{ $displayType }})</td>
            <td @if($isLow) style="font-weight:bold; color:#333;" @else style="font-weight:bold; color:var(--warning);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="SEMI" style="cursor:pointer; text-decoration:underline; @if($isLow) color:#333; @else color:var(--primary-light); @endif" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust" @if($isLow) style="color:#333;" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" @if($isLow) style="color:#ffcccc;" @else style="color:var(--danger);" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem; @if($isLow) background:rgba(255,255,255,0.2); color:#fff; border-color:transparent; @endif">Details</button>
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
        <thead><tr><th>Product</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="finished-stock-tbody">
          @foreach($finishedItems as $s)
          @php 
            $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit;
            $gStr = ($s->grade && $s->grade !== 'NONE' && $s->grade !== 'N/A') ? ' - ' . strtoupper($s->grade) : '';
            $displayType = $s->stage === 'FINISHED' ? 'FG' : ($s->stage === 'SEMI' ? 'Semi-Finished' : 'Raw');
          @endphp
          <tr @if($isLow) style="background-color: rgba(220, 38, 38, 0.35) !important; color: #ffffff !important;" title="Low Stock! Threshold is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:600;">{{ $s->name }}{{ $gStr }} ({{ $displayType }})</td>
            <td @if($isLow) style="font-weight:bold; color:#333;" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">
              ₹{{ number_format($s->rate ?? 0, 2) }}
              <button class="btn-icon edit" onclick="adminUpdateRate('{{ $s->productId }}', '{{ $s->rate ?? 0 }}', '{{ addslashes($s->name) }}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
            </td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="FINISHED" style="cursor:pointer; text-decoration:underline; @if($isLow) color:#333; @else color:var(--primary-light); @endif" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust" @if($isLow) style="color:#333;" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" @if($isLow) style="color:#ffcccc;" @else style="color:var(--danger);" @endif>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                </button>
                <button class="btn btn-sm" onclick="window.location.href='{{ route('product.stock.history', ['productId' => $s->productId, 'stage' => $s->stage, 'grade' => $s->grade]) }}'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem; @if($isLow) background:rgba(255,255,255,0.2); color:#fff; border-color:transparent; @endif">Details</button>
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
          return `<option value="${p.id}" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)} - (grade- N/A) (type - ${t})</option>`;
        }).join('');
        
        onStockProductChange();
      };

      window.onStockProductChange = function() {
        const productSelect = document.getElementById('add-stock-product');
        const productId = productSelect.value;
        const product = adminStockProducts.find(p => p.id == productId);
        const gradeContainer = document.getElementById('add-stock-grade-container');
        
        if (!product) {
          gradeContainer.innerHTML = `<input id="add-stock-grade" value="NONE" style="width:100%; padding:0.65rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">`;
          return;
        }

        const stage = document.getElementById('add-stock-stage').value;
        if (stage === 'RAW') {
          gradeContainer.innerHTML = `
            <select id="add-stock-grade" style="width:100%; padding:0.65rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
              <option value="NONE">NONE</option>
              <option value="CUSTOM">Type custom grade...</option>
            </select>
            <input id="add-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; padding:0.65rem; margin-top:0.5rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
          `;
          
          const select = document.getElementById('add-stock-grade');
          const customInput = document.getElementById('add-stock-grade-custom');
          select.onchange = function() {
            customInput.style.display = select.value === 'CUSTOM' ? 'block' : 'none';
          };
          return;
        }

        const grades = product.grades || [];
        if (grades.length > 0) {
          const options = grades.map(g => `<option value="${escapeHtml(g.name)}">${escapeHtml(g.name)}</option>`).join('');
          gradeContainer.innerHTML = `
            <select id="add-stock-grade" style="width:100%; padding:0.65rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
              ${options}
              <option value="CUSTOM">Type custom grade...</option>
            </select>
            <input id="add-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; padding:0.65rem; margin-top:0.5rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">
          `;
        } else {
          gradeContainer.innerHTML = `<input id="add-stock-grade" placeholder="e.g. PREMIUM" style="width:100%; padding:0.65rem; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#333;">`;
        }

        const select = document.getElementById('add-stock-grade');
        const customInput = document.getElementById('add-stock-grade-custom');
        if (select && customInput) {
          select.onchange = function() {
            customInput.style.display = select.value === 'CUSTOM' ? 'block' : 'none';
          };
        }
      };

      onStockStageChange();
    },
    preConfirm: () => {
      const productId = document.getElementById('add-stock-product').value;
      const stage = document.getElementById('add-stock-stage').value;
      
      let grade = 'NONE';
      const gradeEl = document.getElementById('add-stock-grade');
      if (gradeEl) {
        if (gradeEl.tagName === 'SELECT') {
          if (gradeEl.value === 'CUSTOM') {
            grade = document.getElementById('add-stock-grade-custom').value.trim();
          } else {
            grade = gradeEl.value;
          }
        } else {
          grade = gradeEl.value.trim();
        }
      }
      grade = grade || 'NONE';

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
    fetch('/admin/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify(result.value)
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        Swal.fire('Saved', d.message || 'Stock added.', 'success').then(() => location.reload());
      } else {
        Swal.fire('Error', d.message || 'Could not add stock.', 'error');
      }
    });
  });
}

function adminAdjustStock(productId, stage, grade) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG' }[stage] || stage;

  Swal.fire({
    title: 'Adjust Stock',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        <strong style="color:#333;">${grade}</strong> &nbsp;·&nbsp; ${stageLabel}
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
      <input id="swal-qty" type="number" min="0" step="0.01" placeholder="e.g. 150.00" style="
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

      if (isNaN(qty) || qty < 0) {
        Swal.showValidationMessage('⚠️ Please enter a valid quantity (≥ 0).');
        return false;
      }
      return { qty, type, reason };
    }
  }).then(result => {
    if (!result.isConfirmed) return;

    const { qty, type, reason } = result.value;

    Swal.fire({
      title: 'Applying…',
      text: 'Updating stock record.',
      allowOutsideClick: false,
      background: '#ffffff',
      color: '#333333',
      didOpen: () => Swal.showLoading()
    });

    fetch('/admin/stock/adjust', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({
        product_id: productId,
        stage,
        grade,
        quantity: qty,
        adjust_type: type,
        reason
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
        }).then(() => location.reload());
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
      fetch('/stock/rate', {
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
          }).then(() => location.reload());
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

function adminSetLimit(productId, stage, grade, currentLimit) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG' }[stage] || stage;

  Swal.fire({
    title: 'Set Alert Limit',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        <strong style="color:#333;">${grade}</strong> &nbsp;·&nbsp; ${stageLabel}
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

    fetch('/admin/stock/limit', {
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
        }).then(() => location.reload());
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

// AJAX Polling every 30 seconds
setInterval(() => {
  fetch('/stock/live', {
    headers: { 'Accept': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success && data.data) {
      if (data.locationMappings) {
        locationMappings = data.locationMappings;
      }
      updateStockTables(data.data);
    }
  })
  .catch(err => console.error('Polling error:', err));
}, 30000);

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
      const isLow = limit > 0 && qty < limit;
const rowStyle = isLow ? 'background-color: #8b0000; color:#333;' : '';
      const titleAttr = isLow ? `title="Low Stock! Threshold is ${limit}"` : '';

      // Disable hover ONLY visually for low rows without breaking the click buttons
      const disableHoverClass = isLow ? 'low-stock-no-hover' : '';

      const formattedQty = qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      let qtyColor = 'var(--text-color)';
      if (stage === 'RAW') qtyColor = 'var(--secondary)';
      if (stage === 'SEMI') qtyColor = 'var(--warning)';
      if (stage === 'FINISHED') qtyColor = 'var(--secondary)';
      if (isLow) qtyColor = '#ffffff';

      html += `
        <tr style="${rowStyle}" ${titleAttr} class="${disableHoverClass}">
          <td style="font-weight:600;">
            ${s.name}${s.grade && s.grade !== 'NONE' && s.grade !== 'N/A' ? ' - ' + s.grade : ''} (${stage === 'FINISHED' ? 'FG' : (stage === 'SEMI' ? 'Semi-Finished' : 'Raw')})
          </td>
          <td style="font-weight:bold; color:${qtyColor};">${formattedQty}</td>
          <td>${s.unit || ''}</td>
          <td style="font-weight:bold;">
            ₹${window.number_format(s.rate ?? 0, 2)}
            <button class="btn-icon edit" onclick="adminUpdateRate('${s.productId}', '${s.rate ?? 0}', '${escapeHtml(s.name)}')" title="Edit Rate" style="color:var(--secondary); padding: 0; margin-left: 0.4rem; background: none; border: none; cursor: pointer; display: inline-flex; vertical-align: middle;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
            </button>
          </td>
          <td class="location-col" data-product="${s.productId}" data-grade="${s.grade}" data-stage="${stage}" style="cursor:pointer; text-decoration:underline; ${isLow ? 'color:#333;' : 'color:var(--primary-light);'}" onclick="showLocationBreakdown(this)">📍 View Locations</td>
          <td>

            <div style="display:flex; align-items:center; gap:0.4rem;">
              <button class="btn-icon edit" onclick="adminAdjustStock('${s.productId}', '${s.stage}', '${s.grade}')" title="Adjust" ${isLow ? 'style="color:#333;"' : ''}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
              <button class="btn-icon edit" onclick="adminSetLimit('${s.productId}', '${s.stage}', '${s.grade}', '${limit}')" title="Set Alert Limit" style="color:${isLow ? '#ffcccc' : 'var(--danger)'};">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </button>
              <button class="btn btn-sm" onclick="window.location.href='{{ url('/product') }}/' + s.productId + '/' + s.stage + '/' + s.grade + '/history'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem; ${isLow ? 'background:rgba(255,255,255,0.2); color:#fff; border-color:transparent;' : ''}">Details</button>
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
    const locRes = await fetch('/api/locations');
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
        <label style="display:block; font-size:0.8rem; color:#6b7280; margin-bottom:0.5rem;">Transfer Stock Between Locations</label>
        <div style="display:flex; gap:8px; margin-bottom:0.5rem;">
          <select id="swal-transfer-from" style="flex:1; padding:0.55rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:8px; font-size:0.85rem;">
            <option value="" disabled selected>From Location</option>
            ${fromOptionsHtml}
          </select>
          <span style="color:#6b7280; align-self:center;">➡</span>
          <select id="swal-transfer-to" style="flex:1; padding:0.55rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:8px; font-size:0.85rem;">
            <option value="" disabled selected>To Location</option>
            ${optionsHtml}
          </select>
        </div>
        <div style="display:flex; gap:8px;">
          <input type="number" id="swal-transfer-qty" min="0.01" step="0.01" placeholder="Qty (kg)" style="width:100px; padding:0.55rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:8px;">
          <button class="btn btn-sm" onclick="transferLocationMapping('${pId}', '${stage}', '${grade}', this)" style="flex:1;">Transfer Stock</button>
        </div>
      </div>
    `;
  }

  Swal.fire({
    title: '📍 Stock Storage Locations',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#6b7280;">
        Product locations for this item.
      </div>
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:1rem; text-align:center;">
        <div style="background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#6b7280;">Available</div>
          <div style="font-weight:700; color:#333;">${availableQty.toFixed(2)} kg</div>
        </div>
        <div style="background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#6b7280;">Assigned</div>
          <div style="font-weight:700; color:var(--secondary);">${assignedQty.toFixed(2)} kg</div>
        </div>
        <div style="background:var(--bg-sidebar, #FFF8EA); border:1px solid var(--border-soft, #ECE4CF); border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#6b7280;">Unassigned</div>
          <div style="font-weight:700; color:#333;">${remainingQty.toFixed(2)} kg</div>
        </div>
      </div>
      <div style="margin-bottom:1rem; max-height:200px; overflow-y:auto;">
        ${locationsListHtml}
      </div>
      <div style="border-top:1px dashed var(--border-soft); padding-top:1rem; text-align:left;">
        <label style="display:block; font-size:0.8rem; color:#6b7280; margin-bottom:0.5rem;">Link Quantity to Location</label>
        <div style="display:flex; gap:8px; margin-bottom:0.5rem;">
          <select id="swal-loc-select" style="flex:1; padding:0.55rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:8px;">
            ${optionsHtml}
          </select>
          <input type="number" id="swal-loc-qty" min="0.01" max="${remainingQty.toFixed(2)}" step="0.01" placeholder="Qty (kg)" style="width:100px; padding:0.55rem; background:#ffffff; border:1px solid #d1d5db; color:#333333; border-radius:8px;">
        </div>
        <button class="btn btn-sm" onclick="addLocationMapping('${pId}', '${stage}', '${grade}', ${remainingQty}, this)" style="width:100%;">Save Location link</button>
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
    const res = await fetch('/api/stock/locations/transfer', {
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
        location.reload();
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
    const res = await fetch('/api/stock/locations/transfer', {
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
        location.reload();
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
    
    window.downloadPdfAsync('{{ route("admin.stock.pdf") }}', {
      stages: stages.join(','),
      date: selectedDate
    }, btn);
  });
}

window.onCardStockStageChange = function() {
  const stage = document.getElementById('card-stock-stage').value;
  const productSelect = document.getElementById('card-stock-product');
  const targetType = stage === 'RAW' ? 'RAW' : 'FINISHED';
  const filteredProducts = adminStockProducts.filter(p => p.type === targetType && p.is_active);
  
  productSelect.innerHTML = filteredProducts.map(p => {
    let t = stage.toLowerCase() === 'finished' ? 'fg' : stage.toLowerCase();
    return `<option value="${p.id}" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.name)} - (grade- N/A) (type - ${t})</option>`;
  }).join('');
  
  onCardStockProductChange();
};

window.onCardStockProductChange = function() {
  const productSelect = document.getElementById('card-stock-product');
  const productId = productSelect.value;
  const product = adminStockProducts.find(p => p.id == productId);
  const gradeContainer = document.getElementById('card-stock-grade-container');
  
  if (!product) {
    gradeContainer.innerHTML = `<input id="card-stock-grade" value="NONE" class="form-control" style="width:100%;">`;
    return;
  }

  const stage = document.getElementById('card-stock-stage').value;
  if (stage === 'RAW') {
    gradeContainer.innerHTML = `
      <select id="card-stock-grade" class="form-control" style="width:100%;">
        <option value="NONE">NONE</option>
        <option value="CUSTOM">Type custom grade...</option>
      </select>
      <input id="card-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; margin-top:0.5rem;" class="form-control">
    `;
    
    const select = document.getElementById('card-stock-grade');
    const customInput = document.getElementById('card-stock-grade-custom');
    select.onchange = function() {
      customInput.style.display = select.value === 'CUSTOM' ? 'block' : 'none';
    };
    return;
  }

  const grades = product.grades || [];
  if (grades.length > 0) {
    const options = grades.map(g => `<option value="${escapeHtml(g.name)}">${escapeHtml(g.name)}</option>`).join('');
    gradeContainer.innerHTML = `
      <select id="card-stock-grade" class="form-control" style="width:100%;">
        ${options}
        <option value="CUSTOM">Type custom grade...</option>
      </select>
      <input id="card-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; margin-top:0.5rem;" class="form-control">
    `;
  } else {
    gradeContainer.innerHTML = `<input id="card-stock-grade" placeholder="e.g. PREMIUM" class="form-control" style="width:100%;">`;
  }

  const select = document.getElementById('card-stock-grade');
  const customInput = document.getElementById('card-stock-grade-custom');
  if (select && customInput) {
    select.onchange = function() {
      customInput.style.display = select.value === 'CUSTOM' ? 'block' : 'none';
    };
  }
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

window.adminSaveStockFromCard = function() {
  const btn = document.getElementById('btn-save-stock-card');
  const productId = document.getElementById('card-stock-product').value;
  const stage = document.getElementById('card-stock-stage').value;
  const locationName = document.getElementById('card-stock-location').value;
  
  let grade = 'NONE';
  const gradeEl = document.getElementById('card-stock-grade');
  if (gradeEl) {
    if (gradeEl.tagName === 'SELECT') {
      if (gradeEl.value === 'CUSTOM') {
        grade = document.getElementById('card-stock-grade-custom')?.value.trim() || 'NONE';
      } else {
        grade = gradeEl.value;
      }
    } else {
      grade = gradeEl.value.trim();
    }
  }
  grade = grade || 'NONE';

  const quantity = parseFloat(document.getElementById('card-stock-qty').value);
  const reason = document.getElementById('card-stock-note').value.trim();

  if (!productId) {
    Swal.fire('Error', 'Please select a product.', 'error');
    return;
  }
  if (isNaN(quantity) || quantity <= 0) {
    Swal.fire('Error', 'Please enter a quantity greater than 0.', 'error');
    return;
  }

  btn.disabled = true;
  btn.style.opacity = '0.7';

  fetch('/stock/adjust', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ product_id: productId, stage, grade, quantity, adjust_type: 'add', location_name: locationName, reason })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      Swal.fire('Saved', d.message || 'Stock added.', 'success').then(() => location.reload());
    } else {
      Swal.fire('Error', d.message || 'Could not add stock.', 'error');
      btn.disabled = false;
      btn.style.opacity = '1';
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.style.opacity = '1';
  });
};

document.addEventListener('DOMContentLoaded', () => {
  updateAllLocationLabels();
  if (typeof onCardStockStageChange === 'function') {
    onCardStockStageChange();
  }
});
</script>

<style>
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
