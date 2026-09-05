@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'inward');
  $productsJson = $pageData['products']->map(function($p) {
      $gradesList = $p->grades->pluck('name')->toArray();
      if (empty($gradesList)) $gradesList = ['NONE'];
      return [
          'id' => $p->id,
          'name' => $p->name,
          'type' => $p->type,
          'unit' => $p->unit ?? 'kg',
          'grades' => $gradesList
      ];
  });
@endphp

<style>
/* Clean White & Orange Theme for Stock Manager Action */
.sm-tab-container {
    display: flex;
    gap: 8px;
    background: #ffffff;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 1.2rem;
}
.sm-tab-btn {
    flex: 1;
    text-align: center;
    padding: 0.7rem 1rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
}
.sm-tab-btn.active {
    background: #f59e0b !important;
    color: #ffffff !important;
    border: none !important;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
}
.sm-tab-btn.inactive {
    background: #ffffff !important;
    color: #4b5563 !important;
    border: 1px solid #d1d5db !important;
}
.sm-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
}
.sm-card label {
    color: #374151 !important;
    font-weight: 600 !important;
    margin-bottom: 0.4rem;
    display: block;
}
.sm-card input,
.sm-card select,
.sm-card textarea {
    background-color: #ffffff !important;
    border: 1px solid #d1d5db !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
    border-radius: 8px !important;
}
.sm-card input::placeholder,
.sm-card textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.sm-prod-card, .sm-stock-card {
    background: #f9fafb !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 10px !important;
    padding: 12px 8px !important;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sm-prod-card:hover, .sm-stock-card:hover {
    border-color: #f59e0b !important;
    background: #fffbe6 !important;
}
</style>

<div class="sm-tab-container">
  <a class="sm-tab-btn {{ $tab==='inward'?'active':'inactive' }}" href="?tab=inward">📥 STOCK INWARD</a>
  <a class="sm-tab-btn {{ $tab==='outward'?'active':'inactive' }}" href="?tab=outward">📤 OUTWARD STOCK / TRANSFER</a>
</div>

<div class="sm-card">
  @if($tab === 'inward')
  <div style="font-size:1.25rem; font-weight:700; margin-bottom:1.2rem; color:#111827; display:flex; align-items:center; gap:8px;">
    <span>🌿 Inward Material / Product</span>
  </div>

  <!-- Stage Filter Selection FIRST -->
  <div class="form-group" style="margin-bottom:1.2rem;">
    <label style="font-size:0.95rem; font-weight:700; color:#111827;">Stage *</label>
    <select id="sm-stage" onchange="onStageChange(this.value)" style="padding:0.75rem; width:100%; font-size:1rem; font-weight:600; cursor:pointer;">
      <option value="RAW" selected>RAW</option>
      <option value="SEMI">SEMI</option>
      <option value="FINISHED">FINISHED</option>
    </select>
  </div>
  
  <div class="form-group" style="margin-bottom:0.8rem;">
    <input type="text" id="sm-search" placeholder="🔍 Search product..." oninput="filterSmProductsList(this.value)" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%;">
  </div>
  
  <!-- Products Grid (Filtered by Selected Stage) -->
  <div class="responsive-grid" id="sm-products-grid" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); margin-bottom:1.2rem; max-height:260px; overflow-y:auto; padding:4px; gap:10px;">
    @foreach($pageData['products'] as $p)
      @php
        $gNames = $p->grades->pluck('name')->toArray();
        $gDisplay = !empty($gNames) ? implode(', ', $gNames) : 'NONE';
      @endphp
      <div class="sm-prod-card" data-id="{{ $p->id }}" data-type="{{ $p->type }}" data-name="{{ strtolower($p->name) }}" onclick="selectSmMaterial('{{ $p->id }}', '{{ addslashes($p->name) }}', '{{ $p->type }}', this)"
        style="display:{{ $p->type === 'RAW' ? 'block' : 'none' }};">
        <div style="font-size:0.9rem; font-weight:700; color:#111827; line-height:1.2;">{{ $p->name }}</div>
        <div style="font-size:0.75rem; color:#6b7280; margin-top:4px; font-weight:500;">
          Grade: <span style="color:#d97706; font-weight:700;">{{ $gDisplay }}</span>
        </div>
      </div>
    @endforeach
  </div>
  
  <form id="sm-inward-form" onsubmit="submitSmInward(event)">
    <input type="hidden" id="sm-prod-id" name="product_id" value="">
    <div id="sm-selected-name" style="font-size:0.95rem; font-weight:700; color:#d97706; margin-bottom:1rem; min-height:1.2em;"></div>
    
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Grade *</label>
      <select id="sm-grade" name="grade" onchange="loadSmInwardLocations()" style="padding:0.7rem; width:100%; font-weight:600; cursor:pointer;">
        <option value="NONE">NONE</option>
      </select>
    </div>

    <div style="margin-bottom:1.2rem; padding:12px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <label style="font-size:0.85rem; font-weight:700; color:#374151; text-transform:uppercase; margin:0;">
          Select Locations & Quantities: <span id="sm-inward-total-qty" style="color:#d97706; font-size:0.95rem; font-weight:800; margin-left:6px;">(Total: 0 kg)</span>
        </label>
        <button type="button" class="btn btn-sm" onclick="addSmLocationRow('inward')" style="padding:0.3rem 0.6rem; font-size:0.75rem; width:auto; background:#f59e0b; color:#fff; border:none; border-radius:6px; font-weight:600;">+ Add Location</button>
      </div>
      <div id="sm-inward-loc-rows" style="display:flex; flex-direction:column; gap:10px;">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label>Notes</label>
      <textarea id="sm-notes" name="notes" placeholder="Enter notes (optional)..." style="padding:0.7rem; width:100%; height:70px; resize:vertical;"></textarea>
    </div>
    
    <button type="submit" class="btn" id="sm-submit-btn" style="width:100%; padding:0.8rem; font-size:1rem; font-weight:700; background:#f59e0b; color:#ffffff; border:none; border-radius:8px; cursor:pointer;">
      ADD TO STOCK
    </button>
  </form>
  @endif

  @if($tab === 'outward')
  <div style="font-size:1.25rem; font-weight:700; margin-bottom:1.2rem; color:#111827;">📤 Outward / Transfer Stock</div>
  
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); margin-bottom:1.2rem; max-height:260px; overflow-y:auto; padding:4px; gap:10px;">
    @foreach($pageData['liveStock'] as $ls)
      <div class="sm-stock-card" onclick="selectOutwardStock('{{ $ls['productId'] }}', '{{ addslashes($ls['name']) }}', '{{ $ls['stage'] }}', '{{ addslashes($ls['grade']) }}', {{ $ls['quantity'] }}, this)">
        <div style="font-size:0.9rem; font-weight:700; color:#111827; line-height:1.2;">{{ $ls['name'] }}</div>
        <div style="font-size:0.75rem; color:#d97706; font-weight:700; margin-top:2px;">{{ $ls['stage'] }} ({{ $ls['grade'] }})</div>
        <div style="font-size:0.75rem; color:#6b7280; margin-top:2px;">Avail: <b>{{ number_format($ls['quantity'], 2) }} {{ $ls['unit'] }}</b></div>
      </div>
    @endforeach
  </div>

  <form id="sm-outward-form" onsubmit="submitSmOutward(event)">
    <input type="hidden" id="outward-prod-id" name="product_id" value="">
    <input type="hidden" id="outward-stage" name="stage" value="">
    <input type="hidden" id="outward-grade" name="grade" value="">
    <div id="outward-selected-name" style="font-size:0.95rem; font-weight:700; color:#dc2626; margin-bottom:1rem; min-height:1.2em;"></div>
    
    <div style="margin-bottom:1.2rem; padding:12px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <label style="font-size:0.85rem; font-weight:700; color:#374151; text-transform:uppercase; margin:0;">
          Select Locations & Quantities: <span id="sm-outward-total-qty" style="color:#dc2626; font-size:0.95rem; font-weight:800; margin-left:6px;">(Total: 0 kg)</span>
        </label>
        <button type="button" class="btn btn-sm" onclick="addSmLocationRow('outward')" style="padding:0.3rem 0.6rem; font-size:0.75rem; width:auto; background:#dc2626; color:#fff; border:none; border-radius:6px; font-weight:600;">+ Add Location</button>
      </div>
      <div id="sm-outward-loc-rows" style="display:flex; flex-direction:column; gap:10px;">
      </div>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label>Reason / Notes</label>
      <textarea id="outward-notes" name="notes" placeholder="Reason for outward (e.g. production consumption, dispatch, etc.)..." style="padding:0.7rem; width:100%; height:70px; resize:vertical;"></textarea>
    </div>
    
    <button type="submit" class="btn" id="outward-submit-btn" style="width:100%; padding:0.8rem; font-size:1rem; font-weight:700; background:#dc2626; border:none; color:#ffffff; border-radius:8px; cursor:pointer;">
      OUTWARD STOCK
    </button>
  </form>
  @endif
</div>

<script>
const allMasterProducts = {!! json_encode($productsJson) !!};
const masterLocations = {!! json_encode($pageData['locations'] ?? []) !!};

window.currentInwardLocBreakdown = [];
window.currentOutwardLocBreakdown = [];

function addSmLocationRow(tabType, initialLoc = '', initialQty = '') {
  const container = document.getElementById(tabType === 'inward' ? 'sm-inward-loc-rows' : 'sm-outward-loc-rows');
  if (!container) return;

  const locBreakdown = tabType === 'inward' ? (window.currentInwardLocBreakdown || []) : (window.currentOutwardLocBreakdown || []);
  const locMap = {};
  locBreakdown.forEach(l => { locMap[l.name] = l.quantity; });

  const locs = masterLocations.length ? masterLocations : ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];

  let autoLoc = initialLoc;
  if (!autoLoc && tabType === 'outward') {
    const firstWithStock = locBreakdown.find(l => l.quantity > 0);
    if (firstWithStock) autoLoc = firstWithStock.name;
  }

  let autoAvail = (autoLoc && locMap[autoLoc] !== undefined) ? locMap[autoLoc] : 0;
  let defaultQty = initialQty !== '' ? initialQty : (tabType === 'outward' && autoAvail > 0 ? autoAvail : '');

  let optionsHtml = `<option value="" disabled ${!autoLoc ? 'selected' : ''}>-- SELECT STORAGE LOCATION --</option>`;

  locs.forEach(loc => {
    const avail = locMap[loc] !== undefined ? locMap[loc] : 0;
    const label = tabType === 'outward' 
      ? `${loc.toUpperCase()} (Avail: ${avail} KG)`
      : `${loc.toUpperCase()} (Current: ${avail} KG)`;

    optionsHtml += `<option value="${loc}" data-avail="${avail}" ${loc === autoLoc ? 'selected' : ''}>${label}</option>`;
  });

  const row = document.createElement('div');
  row.className = 'sm-loc-row';
  row.style.cssText = 'display:flex; gap:10px; align-items:flex-end; width:100%;';

  row.innerHTML = `
    <div style="flex:2.2; min-width:140px;">
      <label style="font-size:0.7rem; font-weight:700; color:#374151; text-transform:uppercase; margin-bottom:2px; display:block;">STORAGE LOCATION *</label>
      <select class="sm-loc-select" onchange="onSmLocChange(this, '${tabType}')" style="width:100%; padding:0.65rem 0.75rem; border-radius:8px; border:1px solid #d1d5db; background:#ffffff; color:#111827; font-size:0.88rem; font-weight:600;">
        ${optionsHtml}
      </select>
    </div>
    <div style="flex:1; min-width:90px;">
      <label style="font-size:0.7rem; font-weight:700; color:#374151; text-transform:uppercase; margin-bottom:2px; display:block;">QTY</label>
      <input type="number" class="sm-loc-qty" value="${defaultQty}" placeholder="QTY" step="any" min="0.001" oninput="onSmLocQtyInput(this, '${tabType}')" style="width:100%; padding:0.65rem 0.75rem; border-radius:8px; border:1px solid #d1d5db; background:#ffffff; color:#111827; font-size:0.88rem; font-weight:600;">
    </div>
    <button type="button" onclick="this.closest('.sm-loc-row').remove(); syncSmTotalQty('${tabType}')" title="Remove Location" style="flex:0 0 36px; width:36px; height:36px; margin-bottom:1px; padding:0; display:flex; align-items:center; justify-content:center; border-radius:8px; border:none; background:#ef4444; color:#fff; cursor:pointer; font-weight:bold;">
      ✕
    </button>
  `;

  container.appendChild(row);
  const sel = row.querySelector('.sm-loc-select');
  if (sel && autoLoc) {
    onSmLocChange(sel, tabType);
  }
  syncSmTotalQty(tabType);
}

function onSmLocChange(selectEl, tabType) {
  const row = selectEl.closest('.sm-loc-row');
  if (!row) return;
  const qtyInp = row.querySelector('.sm-loc-qty');
  const selectedOption = selectEl.options[selectEl.selectedIndex];
  if (!selectedOption) return;
  
  const avail = parseFloat(selectedOption.getAttribute('data-avail') || 0);
  const locName = selectEl.value;

  if (qtyInp) {
    qtyInp.dataset.loc = locName;
    qtyInp.dataset.avail = avail;
    if (tabType === 'outward') {
      qtyInp.max = avail;
      if (!qtyInp.value || parseFloat(qtyInp.value) === 0 || parseFloat(qtyInp.value) > avail) {
        qtyInp.value = avail > 0 ? avail : '';
      }
    } else if (tabType === 'inward') {
      if ((!qtyInp.value || parseFloat(qtyInp.value) === 0) && avail > 0) {
        qtyInp.value = avail;
      }
    }
  }
  syncSmTotalQty(tabType);
}

function onSmLocQtyInput(inputEl, tabType) {
  if (tabType === 'outward') {
    const avail = parseFloat(inputEl.dataset.avail || 0);
    const val = parseFloat(inputEl.value || 0);
    const locName = inputEl.dataset.loc || 'selected location';

    if (avail >= 0 && val > avail) {
      inputEl.value = avail;
      if (typeof app !== 'undefined' && app.toast) {
        app.toast(`Entered qty exceeds available stock (${avail} kg) in ${locName}`, 'error');
      }
    }
  }
  syncSmTotalQty(tabType);
}

function syncSmTotalQty(tabType) {
  const container = document.getElementById(tabType === 'inward' ? 'sm-inward-loc-rows' : 'sm-outward-loc-rows');
  const badge = document.getElementById(tabType === 'inward' ? 'sm-inward-total-qty' : 'sm-outward-total-qty');
  if (!container || !badge) return 0;

  const qtyInputs = container.querySelectorAll('.sm-loc-qty');
  let total = 0;
  qtyInputs.forEach(inp => {
    const val = parseFloat(inp.value);
    if (!isNaN(val) && val > 0) total += val;
  });

  if (total > 0) {
    badge.innerText = `(Total: ${total.toLocaleString('en-IN', { maximumFractionDigits: 3 })} kg)`;
  } else {
    const locBreakdown = tabType === 'inward' ? (window.currentInwardLocBreakdown || []) : (window.currentOutwardLocBreakdown || []);
    const totalLocStock = locBreakdown.reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);
    if (totalLocStock > 0) {
      badge.innerText = `(Total Stock: ${totalLocStock.toLocaleString('en-IN', { maximumFractionDigits: 3 })} kg)`;
    } else {
      badge.innerText = `(Total: 0 kg)`;
    }
  }

  return total;
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('sm-inward-loc-rows')) {
    addSmLocationRow('inward');
  }
  if (document.getElementById('sm-outward-loc-rows')) {
    const firstStockCard = document.querySelector('.sm-stock-card');
    if (firstStockCard) {
      firstStockCard.click();
    } else {
      addSmLocationRow('outward');
    }
  }
});

function onStageChange(stage) {
  document.querySelectorAll('.sm-prod-card').forEach(card => {
    const cardType = card.getAttribute('data-type');
    if (cardType === stage) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });

  document.getElementById('sm-prod-id').value = '';
  document.getElementById('sm-selected-name').innerText = '';
  document.querySelectorAll('.sm-prod-card').forEach(c => c.style.borderColor = '#e5e7eb');
  
  const gradeSelect = document.getElementById('sm-grade');
  if (gradeSelect) {
    gradeSelect.innerHTML = '<option value="NONE">NONE</option>';
  }
  loadSmInwardLocations();
}

function filterSmProductsList(q) {
  const query = q.trim().toLowerCase();
  const currentStage = document.getElementById('sm-stage') ? document.getElementById('sm-stage').value : 'RAW';
  
  document.querySelectorAll('.sm-prod-card').forEach(card => {
    const name = card.getAttribute('data-name');
    const cardType = card.getAttribute('data-type');
    if (cardType === currentStage && name.includes(query)) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

function selectSmMaterial(id, name, type, el) {
  document.querySelectorAll('.sm-prod-card').forEach(c => c.style.borderColor = '#e5e7eb');
  el.style.borderColor = '#f59e0b';
  document.getElementById('sm-prod-id').value = id;
  document.getElementById('sm-selected-name').innerText = 'Selected: ' + name + ' (Stage: ' + type + ')';

  const p = allMasterProducts.find(item => item.id == id);
  const gradeSelect = document.getElementById('sm-grade');
  if (gradeSelect) {
    if (p && p.grades && p.grades.length > 0) {
      gradeSelect.innerHTML = p.grades.map(g => `<option value="${g}">${g}</option>`).join('');
    } else {
      gradeSelect.innerHTML = '<option value="NONE">NONE</option>';
    }
  }
  loadSmInwardLocations();
}

function loadSmInwardLocations() {
  const prodId = document.getElementById('sm-prod-id') ? document.getElementById('sm-prod-id').value : '';
  const stage = document.getElementById('sm-stage') ? document.getElementById('sm-stage').value : 'RAW';
  const grade = document.getElementById('sm-grade') ? document.getElementById('sm-grade').value : 'NONE';
  if (!prodId) return;

  fetch(`/api/stock/locations?product_id=${prodId}&stage=${stage}&grade=${encodeURIComponent(grade)}`)
    .then(r => r.json())
    .then(data => {
      window.currentInwardLocBreakdown = (data.success && data.breakdown) ? data.breakdown : [];
      document.querySelectorAll('#sm-inward-loc-rows .sm-loc-row').forEach(row => {
        const sel = row.querySelector('.sm-loc-select');
        const qtyInp = row.querySelector('.sm-loc-qty');
        if (!sel) return;

        const val = sel.value;
        const locMap = {};
        window.currentInwardLocBreakdown.forEach(l => { locMap[l.name] = l.quantity; });
        
        const locs = masterLocations.length ? masterLocations : ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
        let optionsHtml = `<option value="" disabled ${!val ? 'selected' : ''}>-- SELECT STORAGE LOCATION --</option>`;
        locs.forEach(loc => {
          const avail = locMap[loc] !== undefined ? locMap[loc] : 0;
          optionsHtml += `<option value="${loc}" data-avail="${avail}" ${loc === val ? 'selected' : ''}>${loc.toUpperCase()} (Current: ${avail} KG)</option>`;
        });
        sel.innerHTML = optionsHtml;

        if (val && sel.selectedIndex >= 0) {
          const opt = sel.options[sel.selectedIndex];
          if (opt) {
            const avail = parseFloat(opt.getAttribute('data-avail') || 0);
            if (qtyInp) {
              qtyInp.dataset.avail = avail;
              if ((!qtyInp.value || parseFloat(qtyInp.value) === 0) && avail > 0) {
                qtyInp.value = avail;
              }
            }
          }
        }
      });

      syncSmTotalQty('inward');
    })
    .catch(() => {});
}

function submitSmInward(e) {
  e.preventDefault();
  const prodId = document.getElementById('sm-prod-id').value;
  const stage = document.getElementById('sm-stage').value;
  const grade = document.getElementById('sm-grade').value;
  const notes = document.getElementById('sm-notes').value;
  const btn = document.getElementById('sm-submit-btn');

  if (!prodId) {
    app.toast('Please select a product from the grid', 'error');
    return;
  }

  const container = document.getElementById('sm-inward-loc-rows');
  const rows = container ? container.querySelectorAll('.sm-loc-row') : [];
  const locationSplits = [];
  rows.forEach(r => {
    const loc = r.querySelector('.sm-loc-select').value;
    const qty = parseFloat(r.querySelector('.sm-loc-qty').value);
    if (loc && !isNaN(qty) && qty > 0) {
      locationSplits.push({ location: loc, quantity: qty });
    }
  });

  if (locationSplits.length === 0) {
    app.toast('Please select at least one location and enter a valid quantity', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerText = 'Adding...';

  fetch(window.baseUrl + '/' + window.userSlug + '/action', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.csrfToken 
    },
    body: JSON.stringify({ 
      product_id: prodId, 
      stage: stage,
      grade: grade, 
      location_splits: locationSplits, 
      notes: notes 
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      app.toast(data.message || 'Stock added!');
      setTimeout(() => location.reload(), 1000);
    } else {
      app.toast(data.message || 'Failed to add stock', 'error');
      btn.disabled = false;
      btn.innerText = 'ADD TO STOCK';
    }
  })
  .catch(err => {
    app.toast('Error: ' + err.message, 'error');
    btn.disabled = false;
    btn.innerText = 'ADD TO STOCK';
  });
}

function selectOutwardStock(id, name, stage, grade, maxQty, el) {
  document.querySelectorAll('.sm-stock-card').forEach(c => c.style.borderColor = '#e5e7eb');
  el.style.borderColor = '#dc2626';
  document.getElementById('outward-prod-id').value = id;
  document.getElementById('outward-stage').value = stage;
  document.getElementById('outward-grade').value = grade;
  document.getElementById('outward-selected-name').innerText = 'Selected: ' + name + ' (' + stage + ' / ' + grade + ') - Max Available: ' + maxQty + ' kg';

  const container = document.getElementById('sm-outward-loc-rows');
  if (container) container.innerHTML = '<div style="font-size:0.8rem; color:#6b7280; padding:6px;">⏳ Loading storage location breakdown...</div>';

  fetch(`/api/stock/locations?product_id=${id}&stage=${stage}&grade=${encodeURIComponent(grade)}`)
    .then(r => r.json())
    .then(data => {
      if (container) container.innerHTML = '';
      window.currentOutwardLocBreakdown = (data.success && data.breakdown) ? data.breakdown : [];
      
      const locationsWithStock = window.currentOutwardLocBreakdown.filter(l => l.quantity > 0);
      if (locationsWithStock.length > 0) {
        locationsWithStock.forEach(loc => {
          addSmLocationRow('outward', loc.name, loc.quantity);
        });
      } else {
        addSmLocationRow('outward');
      }
    })
    .catch(() => {
      if (container) container.innerHTML = '';
      window.currentOutwardLocBreakdown = [];
      addSmLocationRow('outward');
    });
}

function submitSmOutward(e) {
  e.preventDefault();
  const prodId = document.getElementById('outward-prod-id').value;
  const stage = document.getElementById('outward-stage').value;
  const grade = document.getElementById('outward-grade').value;
  const notes = document.getElementById('outward-notes').value;
  const btn = document.getElementById('outward-submit-btn');

  if (!prodId) {
    app.toast('Please select stock item to outward', 'error');
    return;
  }

  const container = document.getElementById('sm-outward-loc-rows');
  const rows = container ? container.querySelectorAll('.sm-loc-row') : [];
  const locationSplits = [];
  rows.forEach(r => {
    const loc = r.querySelector('.sm-loc-select').value;
    const qty = parseFloat(r.querySelector('.sm-loc-qty').value);
    if (loc && !isNaN(qty) && qty > 0) {
      locationSplits.push({ location: loc, quantity: qty });
    }
  });

  if (locationSplits.length === 0) {
    app.toast('Please select at least one location and enter a valid quantity', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerText = 'Outwarding...';

  fetch(window.baseUrl + '/' + window.userSlug + '/outward', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.csrfToken 
    },
    body: JSON.stringify({ product_id: prodId, stage: stage, grade: grade, location_splits: locationSplits, notes: notes })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      app.toast(data.message || 'Stock outward recorded!');
      setTimeout(() => location.reload(), 1000);
    } else {
      app.toast(data.message || 'Failed to process outward', 'error');
      btn.disabled = false;
      btn.innerText = 'OUTWARD STOCK';
    }
  })
  .catch(err => {
    app.toast('Error: ' + err.message, 'error');
    btn.disabled = false;
    btn.innerText = 'OUTWARD STOCK';
  });
}
</script>
@endsection
