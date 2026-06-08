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
          @php $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit; @endphp
          <tr @if($isLow) style="background-color: #e60000; color: #fff; pointer-events:none;" title="Low Stock! Limit is {{ $s->alert_limit }}" @endif>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td @if($isLow) style="font-weight:bold; color:#fff;" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
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
                <button class="btn btn-sm" onclick="showStockDetails('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
          @php $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit; @endphp
<tr @if($isLow) style="background-color: #ff4d4d; color: #fff; pointer-events:none;" title="Low Stock! Limit is {{ $s->alert_limit }}" @endif>

            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>
            <td @if($isLow) style="font-weight:bold; color:#fff;" @else style="font-weight:bold; color:var(--warning);" @endif>{{ number_format($s->quantity, 2) }}</td>
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
                <button class="btn btn-sm" onclick="showStockDetails('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
    <div class="card-title" style="color:var(--secondary);">✅ Finished Goods Stock ({{ $finishedItems->count() }} items)</div>
    @if($finishedItems->isEmpty())
      <p class="text-muted text-center">No finished stock recorded yet.</p>
    @else
    <div class="table-container">
      <table>
        <thead><tr><th>Product</th><th>Grade</th><th>Total Qty</th><th>Unit</th><th>Rate (Ref)</th><th>Location</th><th>Action</th></tr></thead>
        <tbody id="finished-stock-tbody">
          @foreach($finishedItems as $s)
          @php $isLow = $s->alert_limit > 0 && $s->quantity < $s->alert_limit; @endphp
<tr @if($isLow) style="background-color: #ff4d4d; color:#fff; pointer-events:none;" title="Low Stock! Limit is {{ $s->alert_limit }}" @endif>

            <td style="font-weight:600;">{{ $s->name }}</td>
            <td><span class="badge badge-info">{{ $s->grade }}</span></td>

            <td @if($isLow) style="font-weight:bold; color:#fff;" @else style="font-weight:bold; color:var(--secondary);" @endif>{{ number_format($s->quantity, 2) }}</td>
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
                <button class="btn btn-sm" onclick="showStockDetails('{{ $s->productId }}', '{{ $s->stage }}', '{{ $s->grade }}', '{{ addslashes($s->name) }}')" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
  const productOptions = adminStockProducts.map(p =>
    `<option value="${p.id}" data-type="${p.type}" data-unit="${escapeHtml(p.unit || 'kg')}">${escapeHtml(p.type)} - ${escapeHtml(p.name)} (${escapeHtml(p.unit || 'kg')})</option>`
  ).join('');

  Swal.fire({
    title: 'Add Stock',
    html: `
      <div style="text-align:left;">
        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Product</label>
        <select id="add-stock-product" onchange="syncAddStockStage()" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          ${productOptions}
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Stock Type</label>
        <select id="add-stock-stage" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">
          <option value="RAW">RAW</option>
          <option value="SEMI">SEMI</option>
          <option value="FINISHED">FINISHED</option>
        </select>

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Grade</label>
        <input id="add-stock-grade" value="NONE" style="width:100%; padding:0.65rem; margin:0.35rem 0 0.85rem; border-radius:8px; background:#161b22; border:1px solid #30363d; color:#e6edf3;">

        <label style="font-size:0.82rem; font-weight:600; color:#8b949e;">Quantity</label>
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
    didOpen: syncAddStockStage,
    preConfirm: () => {
      const productId = document.getElementById('add-stock-product').value;
      const stage = document.getElementById('add-stock-stage').value;
      const grade = document.getElementById('add-stock-grade').value.trim() || 'NONE';
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

function syncAddStockStage() {
  const productSelect = document.getElementById('add-stock-product');
  const stageSelect = document.getElementById('add-stock-stage');
  const productType = productSelect?.selectedOptions?.[0]?.dataset?.type;
  if (productType && stageSelect) stageSelect.value = productType;
}

function showStockDetails(productId, stage, grade, productName) {
  const key = `${productId}_${grade || 'NONE'}_${stage}`;
  const logs = adminStockLogsByKey[key] || [];
  const rows = logs.map(log => {
    const sign = log.transaction_type === 'IN' ? '+' : '-';
    const color = log.transaction_type === 'IN' ? '#22c55e' : '#ef4444';
    return `
      <tr style="background: #161b22 !important;">
        <td style="padding:8px; border-bottom:1px solid #30363d; color:${color}; font-weight:700; background: #161b22 !important;">${sign}${Number(log.quantity).toLocaleString(undefined, { maximumFractionDigits: 3 })}</td>
        <td style="padding:8px; border-bottom:1px solid #30363d; color:#e6edf3; background: #161b22 !important;">${escapeHtml(log.transaction_type)}</td>
        <td style="padding:8px; border-bottom:1px solid #30363d; color:#e6edf3; background: #161b22 !important;">${escapeHtml(log.user_name)}</td>
        <td style="padding:8px; border-bottom:1px solid #30363d; color:#e6edf3; background: #161b22 !important;">${escapeHtml(log.created_at)}</td>
        <td style="padding:8px; border-bottom:1px solid #30363d; color:#e6edf3; background: #161b22 !important;">${escapeHtml(log.notes || '')}</td>
      </tr>
    `;
  }).join('') || `<tr style="background: #161b22 !important;"><td colspan="5" style="padding:1rem; text-align:center; color:#8b949e; background: #161b22 !important;">No stock details found.</td></tr>`;

  Swal.fire({
    title: 'Stock Details',
    html: `
      <div style="text-align:left; color:#8b949e; margin-bottom:0.8rem;">
        <strong style="color:#e6edf3;">${escapeHtml(productName)}</strong> / ${escapeHtml(stage)} / ${escapeHtml(grade || 'NONE')}
      </div>
      <div style="max-height:360px; overflow:auto;">
        <table data-filterable="false" style="width:100%; border-collapse:collapse; font-size:0.82rem; text-align:left; background: #0d1117 !important;">
          <thead>
            <tr style="color:#8b949e; background: #21262d !important;">
              <th style="padding:8px; background: #21262d !important; color: #8b949e !important; border-bottom:1px solid #30363d;">Qty</th>
              <th style="padding:8px; background: #21262d !important; color: #8b949e !important; border-bottom:1px solid #30363d;">Type</th>
              <th style="padding:8px; background: #21262d !important; color: #8b949e !important; border-bottom:1px solid #30363d;">By</th>
              <th style="padding:8px; background: #21262d !important; color: #8b949e !important; border-bottom:1px solid #30363d;">Time</th>
              <th style="padding:8px; background: #21262d !important; color: #8b949e !important; border-bottom:1px solid #30363d;">Notes</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `,
    width: '850px',
    background: '#0d1117',
    color: '#e6edf3',
    confirmButtonText: 'Close',
    confirmButtonColor: '#30363d'
  });
}

function adminAdjustStock(productId, stage, grade) {
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ Finished' }[stage] || stage;

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
  const stageLabel = { RAW: '🌿 Raw', SEMI: '⚗️ Semi-Finished', FINISHED: '✅ Finished' }[stage] || stage;

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
const rowStyle = isLow ? 'background-color: #ff4d4d; color: #fff;' : '';
      const titleAttr = isLow ? `title="Low Stock! Limit is ${limit}"` : '';

      // Disable hover ONLY visually for low rows without breaking the click buttons
      const disableHoverClass = isLow ? 'low-stock-no-hover' : '';

      const formattedQty = qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      let qtyColor = 'var(--text-color)';
      if (stage === 'RAW') qtyColor = 'var(--secondary)';
      if (stage === 'SEMI') qtyColor = 'var(--warning)';
      if (stage === 'FINISHED') qtyColor = 'var(--secondary)';

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
              <button class="btn btn-sm" onclick="showStockDetails('${s.productId}', '${s.stage}', '${String(s.grade).replace(/'/g, "\\'")}', '${String(s.name).replace(/'/g, "\\'")}')" style="width:auto; padding:0.35rem 0.55rem; font-size:0.75rem;">Details</button>
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
  try {
    return JSON.parse(localStorage.getItem('pentapure_product_locations')) || {};
  } catch(e) {
    return {};
  }
}

function saveStoredLocationMappings(mappings) {
  localStorage.setItem('pentapure_product_locations', JSON.stringify(mappings));
}

function parseStockNumber(value) {
  return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function getAvailableStockForLocationCell(el) {
  const row = el.closest('tr');
  return row ? parseStockNumber(row.children[2]?.textContent) : 0;
}

function sumLocationQuantities(locMap) {
  return Object.values(locMap || {}).reduce((total, qty) => total + parseStockNumber(qty), 0);
}

function trimLocationMapToAvailable(locMap, availableQty) {
  const cleaned = {};
  let usedQty = 0;

  Object.entries(locMap || {}).forEach(([loc, rawQty]) => {
    const qty = parseStockNumber(rawQty);
    const remaining = availableQty - usedQty;
    if (!loc || qty <= 0 || remaining <= 0) return;
    const safeQty = Math.min(qty, remaining);
    cleaned[loc] = parseFloat(safeQty.toFixed(2));
    usedQty += safeQty;
  });

  return cleaned;
}

function cleanupLocationMappingsForVisibleStock() {
  const mappings = getStoredLocationMappings();
  let changed = false;

  document.querySelectorAll('.location-col').forEach(td => {
    const pId = td.getAttribute('data-product');
    const grade = td.getAttribute('data-grade') || 'NONE';
    const stage = td.getAttribute('data-stage');
    const key = `${pId}_${grade}_${stage}`;
    const availableQty = getAvailableStockForLocationCell(td);
    const originalMap = mappings[key] || {};
    const cleanedMap = trimLocationMapToAvailable(originalMap, availableQty);

    if (JSON.stringify(originalMap) !== JSON.stringify(cleanedMap)) {
      changed = true;
      if (Object.keys(cleanedMap).length) {
        mappings[key] = cleanedMap;
      } else {
        delete mappings[key];
      }
    }
  });

  if (changed) saveStoredLocationMappings(mappings);
}

function updateAllLocationLabels() {
  cleanupLocationMappingsForVisibleStock();
  const mappings = getStoredLocationMappings();
  document.querySelectorAll('.location-col').forEach(td => {
    const pId = td.getAttribute('data-product');
    const grade = td.getAttribute('data-grade') || 'NONE';
    const stage = td.getAttribute('data-stage');
    const key = `${pId}_${grade}_${stage}`;
    const locMap = mappings[key] || {};
    const count = Object.keys(locMap).length;
    if(count === 0) {
      td.innerHTML = `📍 <span style="font-size:0.75rem; color:#8b949e;">Not Set</span>`;
    } else if(count === 1) {
      td.innerHTML = `📍 <span style="font-weight:600; color:var(--secondary);">${Object.keys(locMap)[0]}</span>`;
    } else {
      td.innerHTML = `📍 <span class="badge badge-info" style="cursor:pointer; font-size:0.75rem;">${count} Locations</span>`;
    }
  });
}

function showLocationBreakdown(el) {
  const pId = el.getAttribute('data-product');
  const grade = el.getAttribute('data-grade') || 'NONE';
  const stage = el.getAttribute('data-stage');
  const key = `${pId}_${grade}_${stage}`;
  
  const mappings = getStoredLocationMappings();
  const availableQty = getAvailableStockForLocationCell(el);
  const locMap = trimLocationMapToAvailable(mappings[key] || {}, availableQty);
  mappings[key] = locMap;
  saveStoredLocationMappings(mappings);
  const assignedQty = sumLocationQuantities(locMap);
  const remainingQty = Math.max(availableQty - assignedQty, 0);

  let locationsListHtml = Object.entries(locMap).map(([loc, qty]) => `
    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.05); border-radius:8px; margin-bottom:6px;">
      <span style="font-weight:600; color:#e6edf3;">📍 ${loc}</span>
      <span style="font-weight:bold; color:var(--secondary);">${qty} kg</span>
    </div>
  `).join('') || '<p style="text-align:center; color:#8b949e; margin: 1rem 0;">No locations linked yet.</p>';

  // Admin can override/adjust locations manually
  const allLocations = JSON.parse(localStorage.getItem('pentapure_storage_locations')) || ['Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
  let optionsHtml = allLocations.map(loc => `<option value="${loc}">${loc}</option>`).join('');
  
  let fromOptionsHtml = Object.keys(locMap).map(loc => `<option value="${loc}">${loc} (${locMap[loc]} kg)</option>`).join('');
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
          <button class="btn btn-sm" onclick="transferLocationMapping('${key}')" style="flex:1;">Transfer Stock</button>
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
          <div style="font-size:0.7rem; color:#8b949e;">Remaining</div>
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
        <button class="btn btn-sm" onclick="addLocationMapping('${key}', ${availableQty})" style="width:100%;">Save Location link</button>
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

window.addLocationMapping = function(key, availableQty) {
  const loc = document.getElementById('swal-loc-select').value;
  const qty = parseFloat(document.getElementById('swal-loc-qty').value);
  if(!loc || isNaN(qty) || qty <= 0) {
    Swal.showValidationMessage('Please select location and enter positive quantity');
    return;
  }
  const mappings = getStoredLocationMappings();
  if(!mappings[key]) mappings[key] = {};
  const currentQtyForLocation = parseStockNumber(mappings[key][loc]);
  const assignedExcludingLocation = sumLocationQuantities(mappings[key]) - currentQtyForLocation;
  const maxAllowed = Math.max(availableQty - assignedExcludingLocation, 0);

  if(qty > maxAllowed) {
    Swal.showValidationMessage(`Quantity cannot exceed remaining stock (${maxAllowed.toFixed(2)} kg).`);
    return;
  }

  mappings[key][loc] = qty;
  saveStoredLocationMappings(mappings);
  Swal.fire({
    icon: 'success',
    title: 'Saved',
    background: '#0d1117',
    color: '#e6edf3',
    timer: 1000,
    showConfirmButton: false
  }).then(() => {
    updateAllLocationLabels();
  });
}

window.transferLocationMapping = function(key) {
  const fromLoc = document.getElementById('swal-transfer-from').value;
  const toLoc = document.getElementById('swal-transfer-to').value;
  const qty = parseFloat(document.getElementById('swal-transfer-qty').value);
  
  if(!fromLoc || !toLoc || isNaN(qty) || qty <= 0) {
    Swal.showValidationMessage('Please select both locations and enter a positive quantity.');
    return;
  }
  if(fromLoc === toLoc) {
    Swal.showValidationMessage('From and To locations must be different.');
    return;
  }
  
  const mappings = getStoredLocationMappings();
  const availableInFrom = parseStockNumber(mappings[key][fromLoc]);
  
  if(qty > availableInFrom) {
    Swal.showValidationMessage(`Quantity cannot exceed available stock in source location (${availableInFrom.toFixed(2)} kg).`);
    return;
  }

  mappings[key][fromLoc] = availableInFrom - qty;
  if (mappings[key][fromLoc] <= 0) {
    delete mappings[key][fromLoc];
  }
  
  mappings[key][toLoc] = (parseStockNumber(mappings[key][toLoc]) || 0) + qty;
  saveStoredLocationMappings(mappings);
  
  Swal.fire({
    icon: 'success',
    title: 'Transferred',
    background: '#0d1117',
    color: '#e6edf3',
    timer: 1000,
    showConfirmButton: false
  }).then(() => {
    updateAllLocationLabels();
  });
}

function adminExportStockPdf() {
  Swal.fire({
    title: 'Export Stock Valuation PDF',
    html: `
      <div style="text-align:left; font-size:0.95rem; color:#e6edf3;">
        <p style="margin-bottom:12px; color:#8b949e;">Select the stock panels to include in the PDF report:</p>
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:10px;">
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-raw" checked style="width:20px; height:20px; cursor:pointer;"> 🌿 Raw Material Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-semi" checked style="width:20px; height:20px; cursor:pointer;"> ⚗️ Semi-Finished Stock
          </label>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" id="export-stage-finished" checked style="width:20px; height:20px; cursor:pointer;"> ✅ Finished Goods Stock
          </label>
        </div>
      </div>
    `,
    background: '#e7e7e7',
    color: '#000000',
    showCancelButton: true,
    confirmButtonText: 'Generate Report',
    confirmButtonColor: '#238636',
    cancelButtonColor: '#30363d',
    preConfirm: () => {
      const raw = document.getElementById('export-stage-raw').checked;
      const semi = document.getElementById('export-stage-semi').checked;
      const finished = document.getElementById('export-stage-finished').checked;
      
      const stages = [];
      if (raw) stages.push('RAW');
      if (semi) stages.push('SEMI');
      if (finished) stages.push('FINISHED');
      
      if (stages.length === 0) {
        Swal.showValidationMessage('Please select at least one stock panel.');
        return false;
      }
      return stages;
    }
  }).then(result => {
    if (!result.isConfirmed) return;
    
    const stages = result.value;
    const locations = localStorage.getItem('pentapure_product_locations') || '{}';
    
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
    
    const locationsInput = document.createElement('input');
    locationsInput.type = 'hidden';
    locationsInput.name = 'locations';
    locationsInput.value = locations;
    form.appendChild(locationsInput);
    
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
