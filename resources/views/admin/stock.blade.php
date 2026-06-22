@extends('layouts.admin')

@section('content')

<div style="padding:1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <h2 style="margin:0;">📦 Live Stock Overview</h2>
    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
      <button class="btn btn-secondary" onclick="adminExportStockPdf()" style="width:auto; padding:0.65rem 1.2rem; border-color:#DDCFAF !important;">📄 Generate PDF Report</button>
      <button class="btn" onclick="adminAddStock()" style="width:auto; padding:0.65rem 1.2rem;">+ Add Stock</button>
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
        <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="raw-stock-tbody">
@foreach($rawItems as $s)
          @php $isLow = $s->threshold > 0 && $s->quantity < $s->threshold; @endphp
          <tr @if($isLow) style="background-color: rgba(255, 77, 77, 0.15);" title="Low Stock! Threshold is {{ $s->threshold }}" @endif>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td @if($isLow) style="font-weight:bold; color:var(--danger);" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">₹{{ number_format($s->rate ?? 0, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="RAW" style="cursor:pointer; color:var(--primary-light); text-decoration:underline;" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" style="color:var(--danger);">
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
        <thead><tr><th>Product</th><th>Grade</th><th>Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="semi-stock-tbody">
          @foreach($semiItems as $s)
          @php $isLow = $s->threshold > 0 && $s->quantity < $s->threshold; @endphp
<tr @if($isLow) style="background-color: rgba(255, 77, 77, 0.15);" title="Low Stock! Threshold is {{ $s->threshold }}" @endif>

            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td @if($isLow) style="font-weight:bold; color:var(--danger);" @else style="font-weight:bold; color:var(--warning);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">₹{{ number_format($s->rate ?? 0, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="SEMI" style="cursor:pointer; color:var(--primary-light); text-decoration:underline;" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" style="color:var(--danger);">
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
        <thead><tr><th>Product</th><th>Grade</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="finished-stock-tbody">
          @foreach($finishedItems as $s)
          @php $isLow = $s->threshold > 0 && $s->quantity < $s->threshold; @endphp
<tr @if($isLow) style="background-color: rgba(255, 77, 77, 0.15);" title="Low Stock! Threshold is {{ $s->threshold }}" @endif>

            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>

            <td @if($isLow) style="font-weight:bold; color:var(--danger);" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
            <td>{{ $s->unit }}</td>
            <td style="font-weight:bold;">₹{{ number_format($s->rate ?? 0, 2) }}</td>
            <td class="location-col" data-product="{{ $s->productId }}" data-grade="{{ $s->grade }}" data-stage="FINISHED" style="cursor:pointer; color:var(--primary-light); text-decoration:underline;" onclick="showLocationBreakdown(this)">📍 View Locations</td>
            <td>
              <div style="display:flex; align-items:center; gap:0.4rem;">
                <button class="btn-icon edit" onclick="adminAdjustStock('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}')" title="Adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon edit" onclick="adminSetLimit('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ $s->alert_limit }}')" title="Set Alert Limit" style="color:var(--danger);">
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
        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Stock Type</label>
        <select id="add-stock-stage" onchange="onStockStageChange()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          <option value="RAW">RAW</option>
          <option value="SEMI">SEMI</option>
          <option value="FINISHED">FINISHED</option>
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Product</label>
        <select id="add-stock-product" onchange="onStockProductChange()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          <!-- Populated dynamically -->
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Grade</label>
        <div id="add-stock-grade-container">
          <!-- Populated dynamically -->
        </div>

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e; margin-top:0.85rem; display:block;">Quantity</label>
        <input id="add-stock-qty" type="number" min="0.001" step="0.001" placeholder="e.g. 200" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Note</label>
        <textarea id="add-stock-note" rows="2" placeholder="Optional details" style="width:100%; padding:0.65rem; margin-top:0.35rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3; resize:vertical;"></textarea>
      </div>
    `,
    background: '#0d1117',
    color: '#e6edf3',
    showCancelButton: true,
    confirmButtonText: 'Add Stock',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
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
          gradeContainer.innerHTML = `<input id="add-stock-grade" value="NONE" style="width:100%; padding:0.65rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">`;
          return;
        }

        const stage = document.getElementById('add-stock-stage').value;
        if (stage === 'RAW') {
          gradeContainer.innerHTML = `
            <select id="add-stock-grade" style="width:100%; padding:0.65rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
              <option value="NONE">NONE</option>
              <option value="CUSTOM">Type custom grade...</option>
            </select>
            <input id="add-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; padding:0.65rem; margin-top:0.5rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
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
            <select id="add-stock-grade" style="width:100%; padding:0.65rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
              ${options}
              <option value="CUSTOM">Type custom grade...</option>
            </select>
            <input id="add-stock-grade-custom" placeholder="Enter custom grade" style="display:none; width:100%; padding:0.65rem; margin-top:0.5rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          `;
        } else {
          gradeContainer.innerHTML = `<input id="add-stock-grade" placeholder="e.g. PREMIUM" style="width:100%; padding:0.65rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">`;
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
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#8b949e;">
        <strong style="color:#e6edf3;">${grade}</strong> &nbsp;·&nbsp; ${stageLabel}
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Adjustment Type
      </label>
      <select id="swal-adj-type" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:0.95rem; margin-bottom:1rem; outline:none;
      ">
        <option value="set">🎯 Set — Override to exact quantity</option>
        <option value="add">➕ Add — Increase current stock</option>
        <option value="subtract">➖ Subtract — Decrease current stock</option>
      </select>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Quantity (kg)
      </label>
      <input id="swal-qty" type="number" min="0" step="0.01" placeholder="e.g. 150.00" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Reason / Note <span style="font-weight:400;">(optional)</span>
      </label>
      <textarea id="swal-reason" rows="2" placeholder="e.g. Physical count correction, spillage, etc." style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:0.9rem; resize:vertical; outline:none; box-sizing:border-box;
      "></textarea>
    `,
    background: '#0d1117',
    color: '#e6edf3',
    showCancelButton: true,
    confirmButtonText: 'Apply Adjustment',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
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
      background: '#0d1117',
      color: '#e6edf3',
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
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
          timer: 2000,
          timerProgressBar: true,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: d.message || 'Something went wrong.',
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
        });
      }
    })
    .catch(() => {
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Could not reach the server. Please try again.',
        background: '#0d1117',
        color: '#e6edf3',
        confirmButtonColor: '#238636',
      });
    });
  });
}

function adminSetLimit(productId, stage, grade, currentLimit) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ FG' }[stage] || stage;

  Swal.fire({
    title: 'Set Alert Limit',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#8b949e;">
        <strong style="color:#e6edf3;">${grade}</strong> &nbsp;·&nbsp; ${stageLabel}
      </div>

      <label style="display:block;text-align:left;font-size:0.82rem;font-weight:600;color:#8b949e;margin-bottom:0.35rem;">
        Alert Limit (kg)
      </label>
      <input id="swal-limit-qty" type="number" min="0" step="0.01" value="${currentLimit}" style="
        width:100%; padding:0.65rem 0.8rem; border-radius:8px;
        background:#161b22; border:1px solid #30363d; color:#e6edf3;
        font-size:1rem; margin-bottom:1rem; outline:none; box-sizing:border-box;
      ">
      <p style="text-align:left; font-size:0.8rem; color:#8b949e;">Set to 0 to disable alerts for this item.</p>
    `,
    background: '#0d1117',
    color: '#e6edf3',
    showCancelButton: true,
    confirmButtonText: 'Save Limit',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
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
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
          timer: 1500,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed',
          text: d.message || 'Something went wrong.',
          background: '#0d1117',
          color: '#e6edf3',
          confirmButtonColor: '#238636',
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
      const limit = parseFloat(s.threshold) || 0;
      const qty = parseFloat(s.quantity);
      const isLow = limit > 0 && qty < limit;
const rowStyle = isLow ? 'background-color: rgba(255, 77, 77, 0.15);' : '';
      const titleAttr = isLow ? `title="Low Stock! Threshold is ${limit}"` : '';

      // Disable hover ONLY visually for low rows without breaking the click buttons
      const disableHoverClass = isLow ? 'low-stock-no-hover' : '';

      const formattedQty = qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      let qtyColor = 'var(--text-color)';
      if (stage === 'RAW') qtyColor = 'var(--secondary)';
      if (stage === 'SEMI') qtyColor = 'var(--warning)';
      if (stage === 'FINISHED') qtyColor = 'var(--secondary)';
      if (isLow) qtyColor = 'var(--danger)';

      html += `
        <tr style="${rowStyle}" ${titleAttr} class="${disableHoverClass}">
          <td style="font-weight:600;">${s.name}</td>
          <td><span class="badge badge-info">${s.grade}</span></td>
          <td style="font-weight:bold; color:${qtyColor};">${formattedQty}</td>
          <td>${s.unit || ''}</td>
          <td style="font-weight:bold;">₹${number_format(parseFloat(s.rate ?? 0) || 0, 2)}</td>
          <td class="location-col" data-product="${s.productId}" data-grade="${s.grade}" data-stage="${stage}" style="cursor:pointer; color:var(--primary-light); text-decoration:underline;" onclick="showLocationBreakdown(this)">📍 View Locations</td>
          <td>

            <div style="display:flex; align-items:center; gap:0.4rem;">
              <button class="btn-icon edit" onclick="adminAdjustStock('${s.productId}', '${s.stage}', '${s.grade}')" title="Adjust">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
              </button>
              <button class="btn-icon edit" onclick="adminSetLimit('${s.productId}', '${s.stage}', '${s.grade}', '${limit}')" title="Set Alert Limit" style="color:var(--danger);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </button>
              <button class="btn btn-sm" onclick="window.location.href='{{ url('/product') }}/' + s.productId + '/' + s.stage + '/' + s.grade + '/history'" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
      td.innerHTML = `📍 <span style="font-size:0.75rem; color:#8b949e;">Not Set</span>`;
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
    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.05); border-radius:8px; margin-bottom:6px;">
      <span style="font-weight:600; color:#e6edf3;">📍 ${escapeHtml(loc)}</span>
      <span style="font-weight:bold; color:var(--secondary);">${qty.toFixed(2)} kg</span>
    </div>
  `).join('') || '<p style="text-align:center; color:#8b949e; margin: 1rem 0;">No locations linked yet.</p>';

  let optionsHtml = allLocations.map(loc => `<option value="${escapeHtml(loc.name)}">${escapeHtml(loc.name)}</option>`).join('');
  
  let fromOptionsHtml = Object.entries(locMap).map(([loc, qty]) => `<option value="${escapeHtml(loc)}">${escapeHtml(loc)} (${qty} kg)</option>`).join('');
  let transferHtml = '';
  if (Object.keys(locMap).length > 0) {
    transferHtml = `
      <div style="border-top:1px dashed #30363d; padding-top:1rem; margin-top:1rem; text-align:left;">
        <label style="display:block; font-size:0.8rem; color:#8b949e; margin-bottom:0.5rem;">Transfer Stock Between Locations</label>
        <div style="display:flex; gap:8px; margin-bottom:0.5rem;">
          <select id="swal-transfer-from" style="flex:1; padding:0.55rem; background:#161b22; border:1px solid #30363d; color:#e6edf3; border-radius:8px; font-size:0.85rem;">
            <option value="" disabled selected>From Location</option>
            ${fromOptionsHtml}
          </select>
          <span style="color:#8b949e; align-self:center;">➡</span>
          <select id="swal-transfer-to" style="flex:1; padding:0.55rem; background:#161b22; border:1px solid #30363d; color:#e6edf3; border-radius:8px; font-size:0.85rem;">
            <option value="" disabled selected>To Location</option>
            ${optionsHtml}
          </select>
        </div>
        <div style="display:flex; gap:8px;">
          <input type="number" id="swal-transfer-qty" min="0.01" step="0.01" placeholder="Qty (kg)" style="width:100px; padding:0.55rem; background:#161b22; border:1px solid #30363d; color:#e6edf3; border-radius:8px;">
          <button class="btn btn-sm" onclick="transferLocationMapping('${pId}', '${stage}', '${grade}', this)" style="flex:1;">Transfer Stock</button>
        </div>
      </div>
    `;
  }

  Swal.fire({
    title: '📍 Stock Storage Locations',
    html: `
      <div style="text-align:left; font-size:0.9rem; margin-bottom:1rem; color:#8b949e;">
        Product locations for this item.
      </div>
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:1rem; text-align:center;">
        <div style="background:#161b22; border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#8b949e;">Available</div>
          <div style="font-weight:700; color:#e6edf3;">${availableQty.toFixed(2)} kg</div>
        </div>
        <div style="background:#161b22; border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#8b949e;">Assigned</div>
          <div style="font-weight:700; color:var(--secondary);">${assignedQty.toFixed(2)} kg</div>
        </div>
        <div style="background:#161b22; border-radius:8px; padding:8px;">
          <div style="font-size:0.7rem; color:#8b949e;">Unassigned</div>
          <div style="font-weight:700; color:#e6edf3;">${remainingQty.toFixed(2)} kg</div>
        </div>
      </div>
      <div style="margin-bottom:1rem; max-height:200px; overflow-y:auto;">
        ${locationsListHtml}
      </div>
      <div style="border-top:1px dashed #30363d; padding-top:1rem; text-align:left;">
        <label style="display:block; font-size:0.8rem; color:#8b949e; margin-bottom:0.5rem;">Link Quantity to Location</label>
        <div style="display:flex; gap:8px; margin-bottom:0.5rem;">
          <select id="swal-loc-select" style="flex:1; padding:0.55rem; background:#161b22; border:1px solid #30363d; color:#e6edf3; border-radius:8px;">
            ${optionsHtml}
          </select>
          <input type="number" id="swal-loc-qty" min="0.01" max="${remainingQty.toFixed(2)}" step="0.01" placeholder="Qty (kg)" style="width:100px; padding:0.55rem; background:#161b22; border:1px solid #30363d; color:#e6edf3; border-radius:8px;">
        </div>
        <button class="btn btn-sm" onclick="addLocationMapping('${pId}', '${stage}', '${grade}', ${remainingQty}, this)" style="width:100%;">Save Location link</button>
      </div>
      ${transferHtml}
    `,
    showConfirmButton: false,
    showCancelButton: true,
    cancelButtonText: 'Close',
    background: '#0d1117',
    color: '#e6edf3',
    cancelButtonColor: '#30363d'
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
        background: '#0d1117',
        color: '#e6edf3',
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
        background: '#0d1117',
        color: '#e6edf3',
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
    title: 'Export Stock Valuation PDF',
    html: `
      <div style="text-align:left; font-size:0.95rem; color:#e6edf3;">
        <p style="margin-bottom:12px; color:#8b949e;">Select the stock panels to include in the PDF report:</p>
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-raw" checked style="width:20px; height:20px; cursor:pointer;"> 🌿 Raw Material Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-semi" checked style="width:20px; height:20px; cursor:pointer;"> ⚗️ Semi-Finished Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-finished" checked style="width:20px; height:20px; cursor:pointer;"> ✅ FG Stock
          </label>
        </div>
        
        <div style="display:flex; gap:10px; margin-bottom:8px;">
          <div style="flex:1;">
            <label style="display:block; text-align:left; font-size:0.82rem; font-weight:600; color:#8b949e; margin-bottom:0.35rem;">
              From Date (Optional)
            </label>
            <input type="date" id="export-start-date" style="
              width:100%; padding:0.65rem 0.8rem; border-radius:8px;
              background:#161b22; border:1px solid #30363d; color:#e6edf3;
              font-size:0.95rem; outline:none; box-sizing:border-box;
            ">
          </div>
          <div style="flex:1;">
            <label style="display:block; text-align:left; font-size:0.82rem; font-weight:600; color:#8b949e; margin-bottom:0.35rem;">
              To Date (Optional)
            </label>
            <input type="date" id="export-end-date" style="
              width:100%; padding:0.65rem 0.8rem; border-radius:8px;
              background:#161b22; border:1px solid #30363d; color:#e6edf3;
              font-size:0.95rem; outline:none; box-sizing:border-box;
            ">
          </div>
        </div>
        <p style="margin-top:6px; font-size:0.8rem; color:#8b949e;">Leave empty to generate live stock report.</p>
      </div>
    `,
    background: '#0d1117',
    color: '#e6edf3',
    showCancelButton: true,
    confirmButtonText: 'Generate Report',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
    preConfirm: () => {
      const raw = document.getElementById('export-stage-raw').checked;
      const semi = document.getElementById('export-stage-semi').checked;
      const finished = document.getElementById('export-stage-finished').checked;
      const startDate = document.getElementById('export-start-date').value;
      const endDate = document.getElementById('export-end-date').value;
      
      const stages = [];
      if (raw) stages.push('RAW');
      if (semi) stages.push('SEMI');
      if (finished) stages.push('FINISHED');
      
      if (stages.length === 0) {
        Swal.showValidationMessage('Please select at least one stock panel.');
        return false;
      }

      if (startDate && endDate && startDate > endDate) {
        Swal.showValidationMessage('From Date cannot be later than To Date.');
        return false;
      }

      return { stages, startDate, endDate };
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    
    const { stages, startDate, endDate } = result.value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.stock.pdf") }}';
    form.style.display = 'none';
    
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = csrfToken;
    form.appendChild(tokenInput);
    
    const stagesInput = document.createElement('input');
    stagesInput.type = 'hidden';
    stagesInput.name = 'stages';
    stagesInput.value = stages.join(',');
    form.appendChild(stagesInput);
    
    if (startDate) {
      const startInput = document.createElement('input');
      startInput.type = 'hidden';
      startInput.name = 'start_date';
      startInput.value = startDate;
      form.appendChild(startInput);
    }

    if (endDate) {
      const endInput = document.createElement('input');
      endInput.type = 'hidden';
      endInput.name = 'end_date';
      endInput.value = endDate;
      form.appendChild(endInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  });
}

document.addEventListener('DOMContentLoaded', updateAllLocationLabels);
</script>

<style>
.swal-stock-popup { border: 1px solid #30363d !important; border-radius: 14px !important; }
.swal-confirm-btn, .swal-cancel-btn { border-radius: 8px !important; font-weight: 600 !important; padding: 0.55rem 1.4rem !important; }
#swal-qty:focus, #swal-reason:focus, #swal-adj-type:focus {
  border-color: #238636 !important;
  box-shadow: 0 0 0 3px rgba(35,134,54,0.25) !important;
}

/* Disable row hover effect for low stock (visual only, buttons remain clickable) */
tr.low-stock-no-hover:hover {
  background-color: inherit !important;
  color: inherit !important;
}
</style>
@endsection
