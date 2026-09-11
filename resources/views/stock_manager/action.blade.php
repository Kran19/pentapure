@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
@php
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
.sm-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
}
.sm-card label {
    color: #374151 !important;
    font-weight: 700 !important;
    font-size: 0.9rem;
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
.custom-location-dropdown {
    width: 100%;
    position: relative;
}
.custom-location-dropdown button {
    width: 100%;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border: 1px solid #d1d5db;
    padding: 0.65rem 0.75rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: #111827;
    border-radius: 8px;
    cursor: pointer;
}
.custom-location-dropdown ul.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    width: 100%;
    max-height: 260px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    list-style: none;
    margin-top: 0.25rem;
    padding: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
/* Hide spin arrows on number inputs */
input[type=number].no-spinners::-webkit-outer-spin-button,
input[type=number].no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number].no-spinners {
  -moz-appearance: textfield;
}
</style>

<div class="sm-card">
  <div style="font-size:1.25rem; font-weight:700; margin-bottom:1.5rem; color:#111827; display:flex; align-items:center; gap:8px;">
    <span>📦 Stock Inward / Outward Entry</span>
  </div>

  <form id="sm-action-form" onsubmit="submitSmAction(event)">
    <!-- 1. Stage * -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Stage *</label>
      <select id="sm-stage" onchange="onStageChange(this.value)" style="padding:0.75rem; width:100%; font-size:1rem; font-weight:600; cursor:pointer;">
        <option value="ALL" selected>ALL (RAW, SEMI, FINISHED)</option>
        <option value="RAW">RAW</option>
        <option value="SEMI">SEMI</option>
        <option value="FINISHED">FINISHED</option>
      </select>
    </div>

    <!-- 2. Product Dropdown -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Product *</label>
      <select id="sm-prod-id" name="product_id" onchange="onProductChange(this.value)" required style="padding:0.75rem; width:100%; font-size:0.95rem; font-weight:600; cursor:pointer;">
        <option value="" disabled selected>-- SELECT PRODUCT --</option>
        @foreach($pageData['products'] as $p)
          <option value="{{ $p->id }}" data-type="{{ $p->type }}">{{ $p->name }} ({{ $p->type }})</option>
        @endforeach
      </select>
    </div>

    <!-- 3. Grade -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Grade *</label>
      <select id="sm-grade" name="grade" onchange="loadSmLocations()" style="padding:0.75rem; width:100%; font-weight:600; cursor:pointer;">
        <option value="ALL">ALL GRADES</option>
        <option value="NONE">NONE</option>
      </select>
    </div>

    <!-- 4. Select Locations & Quantities -->
    <div class="bs-location-row" style="margin-bottom:1.2rem; padding:14px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <label style="font-size:0.85rem; font-weight:700; color:#374151; text-transform:uppercase; margin:0;">
          SELECT LOCATIONS & QUANTITIES: <span id="sm-action-total-qty" style="color:#d97706; font-size:0.95rem; font-weight:800; margin-left:6px;">(TOTAL: 0 KG)</span>
        </label>
      </div>

      <div style="display:flex; gap:0.5rem; align-items:flex-start; width:100%;">
        <div style="flex:2.2; min-width:140px;">
          <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.2rem; color:#6b7280; display:block;">STORAGE LOCATION *</label>
          <div class="custom-location-dropdown" id="sm-custom-location-dropdown">
            <button type="button" onclick="toggleLocationDropdownMenu(this)">
              <span class="loc-dropdown-text">Select Storage Location</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <ul class="dropdown-menu shadow">
              <!-- Dynamically populated via JS -->
            </ul>
          </div>
        </div>

        <div style="flex:1; min-width:90px;">
          <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.2rem; color:#6b7280; display:block;">QTY *</label>
          <input type="number" min="0" step="0.001" class="sm-loc-qty no-spinners" id="sm-total-qty-input" placeholder="0" value="0" oninput="onDirectTotalQtyInput(this)" style="height:2.5rem; padding:0.4rem 0.6rem; font-size:0.9rem; font-weight:700; text-align:center; width:100%;">
        </div>
      </div>
    </div>

    <!-- 5. Transaction Type (Stock Inward / Stock Outward) -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Action Type *</label>
      <select id="sm-action-type" name="action_type" onchange="onActionTypeChange(this.value)" style="padding:0.75rem; width:100%; font-size:1rem; font-weight:700; cursor:pointer;">
        <option value="INWARD" selected>📥 Stock Inward</option>
        <option value="OUTWARD">📤 Stock Outward</option>
      </select>
    </div>

    <!-- 6. Notes -->
    <div class="form-group" style="margin-bottom:1.5rem;">
      <label>Notes</label>
      <textarea id="sm-notes" name="notes" placeholder="Enter notes (optional)..." style="padding:0.7rem; width:100%; height:70px; resize:vertical;"></textarea>
    </div>

    <!-- 7. Submit Button -->
    <button type="submit" class="btn" id="sm-submit-btn" style="width:100%; padding:0.8rem; font-size:1rem; font-weight:700; background:#f59e0b; color:#ffffff; border:none; border-radius:8px; cursor:pointer;">
      SUBMIT INWARD
    </button>
  </form>
</div>

<script>
const allMasterProducts = {!! json_encode($productsJson) !!};
const masterLocations = {!! json_encode($pageData['locations'] ?? []) !!};
window.currentLocBreakdown = [];

function toggleLocationDropdownMenu(btn) {
  const menu = btn.nextElementSibling;
  menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.custom-location-dropdown')) {
    document.querySelectorAll('.custom-location-dropdown .dropdown-menu').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});

function onStageChange(stage) {
  const prodSelect = document.getElementById('sm-prod-id');
  const currentVal = prodSelect.value;
  
  let html = '<option value="" disabled selected>-- SELECT PRODUCT --</option>';
  allMasterProducts.forEach(p => {
    if (stage === 'ALL' || p.type === stage) {
      html += `<option value="${p.id}" data-type="${p.type}">${p.name} (${p.type})</option>`;
    }
  });
  prodSelect.innerHTML = html;

  const exists = allMasterProducts.some(p => p.id == currentVal && (stage === 'ALL' || p.type === stage));
  if (exists) {
    prodSelect.value = currentVal;
  } else {
    document.getElementById('sm-grade').innerHTML = '<option value="ALL">ALL GRADES</option><option value="NONE">NONE</option>';
  }
  loadSmLocations(true);
}

function onProductChange(prodId) {
  const p = allMasterProducts.find(item => item.id == prodId);
  const gradeSelect = document.getElementById('sm-grade');
  if (gradeSelect) {
    if (p && p.grades && p.grades.length > 0) {
      let opts = '<option value="ALL">ALL GRADES</option>';
      opts += p.grades.map(g => `<option value="${g}">${g}</option>`).join('');
      gradeSelect.innerHTML = opts;
    } else {
      gradeSelect.innerHTML = '<option value="ALL">ALL GRADES</option><option value="NONE">NONE</option>';
    }
  }
  loadSmLocations(true);
}

function loadSmLocations(resetInputs = false) {
  const prodId = document.getElementById('sm-prod-id').value;
  const stageSelect = document.getElementById('sm-stage').value;
  const stage = stageSelect || 'ALL';
  const grade = document.getElementById('sm-grade') ? document.getElementById('sm-grade').value : 'ALL';

  if (!prodId) {
    window.currentLocBreakdown = [];
    renderLocationDropdownMenu(resetInputs);
    return;
  }

  const query = `product_id=${prodId}&stage=${stage}&grade=${encodeURIComponent(grade)}`;
  const userSlug = window.userSlug || 'stock_manager';

  const fetchStock = fetch(`/api/stock/locations?${query}`)
    .then(r => r.ok ? r.json() : fetch(`${window.location.origin}/${userSlug}/api/stock/locations?${query}`).then(r2 => r2.json()))
    .catch(() => fetch(`${window.location.origin}/${userSlug}/api/stock/locations?${query}`).then(r2 => r2.json()).catch(() => ({ success: false })));

  const fetchLocs = fetch(`/api/locations`)
    .then(r => r.ok ? r.json() : fetch(`${window.location.origin}/${userSlug}/api/locations`).then(r2 => r2.json()))
    .catch(() => fetch(`${window.location.origin}/${userSlug}/api/locations`).then(r2 => r2.json()).catch(() => ({ success: false })));

  Promise.all([fetchStock, fetchLocs])
  .then(([stockData, locData]) => {
    if (stockData && stockData.success && stockData.breakdown) {
      window.currentLocBreakdown = stockData.breakdown;
    } else {
      window.currentLocBreakdown = [];
    }

    if (locData && locData.success && locData.locations && locData.locations.length > 0) {
      window.liveMasterLocations = locData.locations.map(l => l.name);
    }
    renderLocationDropdownMenu(resetInputs);
  })
  .catch(() => {
    window.currentLocBreakdown = [];
    renderLocationDropdownMenu(resetInputs);
  });
}

function renderLocationDropdownMenu(resetInputs = false) {
  const dropdownMenu = document.querySelector('#sm-custom-location-dropdown .dropdown-menu');
  if (!dropdownMenu) return;

  // Save current user typed input values before re-rendering unless resetting
  const existingValues = {};
  if (!resetInputs) {
    document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input').forEach(inp => {
      const loc = inp.getAttribute('data-loc');
      if (loc && inp.value !== '') {
        existingValues[loc.trim().toLowerCase()] = inp.value;
      }
    });
  } else {
    const mainQtyInp = document.getElementById('sm-total-qty-input');
    if (mainQtyInp) mainQtyInp.value = 0;
  }

  const locMap = {};
  (window.currentLocBreakdown || []).forEach(l => { locMap[l.name.trim().toLowerCase()] = l.quantity; });

  const defaultLocs = ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
  const baseLocs = (window.liveMasterLocations && window.liveMasterLocations.length)
    ? window.liveMasterLocations
    : (masterLocations.length ? masterLocations : defaultLocs);

  // Combine with any extra locations returned in currentLocBreakdown
  const allLocNames = new Set(baseLocs);
  (window.currentLocBreakdown || []).forEach(l => {
    if (l.name) allLocNames.add(l.name.trim());
  });

  let html = '';
  allLocNames.forEach(locName => {
    const key = locName.trim().toLowerCase();
    const avail = locMap[key] !== undefined ? parseFloat(locMap[key]) : 0;
    const formattedAvail = avail.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const preservedVal = existingValues[key] !== undefined ? existingValues[key] : '0';
    
    html += `
      <li style="margin-bottom:0.55rem; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:#333; gap:8px;">
        <span style="padding-left:0.2rem; font-weight:700; color:#1f2937;">${locName.toUpperCase()} <small style="color:${avail > 0 ? '#16a34a' : '#9ca3af'}; font-weight:800;">(AVAIL: ${formattedAvail} KG)</small></span>
        <input type="number" min="0" step="0.001" class="form-control form-control-sm inner-qty-input no-spinners" data-loc="${escapeHtml(locName)}" data-avail="${avail}" oninput="recalcDropdownTotals()" style="width: 75px; text-align:center; padding: 0.25rem; height:1.8rem; font-size:0.82rem; border:1px solid #d1d5db; border-radius:4px; font-weight:700;" value="${preservedVal}">
      </li>
    `;
  });

  dropdownMenu.innerHTML = html;
  recalcDropdownTotals();
}

function recalcDropdownTotals() {
  const inputs = document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input');
  let sumEnteredQty = 0;
  const activeLocSummary = [];
  const actionType = document.getElementById('sm-action-type').value;

  const totalExistingAvail = (window.currentLocBreakdown || []).reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);

  inputs.forEach(inp => {
    const qty = parseFloat(inp.value) || 0;
    const locName = inp.getAttribute('data-loc');
    const avail = parseFloat(inp.getAttribute('data-avail') || 0);

    if (actionType === 'OUTWARD' && qty > avail) {
      inp.style.border = '2px solid #ef4444';
      inp.style.backgroundColor = '#fef2f2';
    } else {
      inp.style.border = '1px solid #d1d5db';
      inp.style.backgroundColor = '#ffffff';
    }

    if (qty > 0) {
      sumEnteredQty += qty;
      const previewQty = actionType === 'INWARD' ? (avail + qty) : Math.max(0, avail - qty);
      activeLocSummary.push(`${locName.toUpperCase()} (${previewQty.toFixed(2)} KG)`);
    }
  });

  const btnText = document.querySelector('#sm-custom-location-dropdown .loc-dropdown-text');
  if (btnText) {
    if (activeLocSummary.length > 0) {
      btnText.textContent = activeLocSummary.join(', ');
    } else {
      btnText.textContent = 'Select Storage Location';
    }
  }

  const totalQtyInput = document.getElementById('sm-total-qty-input');
  if (totalQtyInput && sumEnteredQty > 0) {
    totalQtyInput.value = sumEnteredQty;
  }

  let grandTotal = actionType === 'INWARD'
    ? (totalExistingAvail + sumEnteredQty)
    : Math.max(0, totalExistingAvail - sumEnteredQty);

  const badge = document.getElementById('sm-action-total-qty');
  if (badge) {
    if (sumEnteredQty > 0) {
      badge.innerText = `(TOTAL AVAIL: ${totalExistingAvail.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG → NEW TOTAL: ${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG)`;
    } else {
      badge.innerText = `(TOTAL AVAIL: ${totalExistingAvail.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG)`;
    }
  }
}

function onDirectTotalQtyInput(mainInput) {
  const mainVal = parseFloat(mainInput.value) || 0;
  const totalExistingAvail = (window.currentLocBreakdown || []).reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);
  const actionType = document.getElementById('sm-action-type').value;

  let grandTotal = actionType === 'INWARD'
    ? (totalExistingAvail + mainVal)
    : Math.max(0, totalExistingAvail - mainVal);

  const badge = document.getElementById('sm-action-total-qty');
  if (badge) {
    if (mainVal > 0) {
      badge.innerText = `(TOTAL AVAIL: ${totalExistingAvail.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG → NEW TOTAL: ${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG)`;
    } else {
      badge.innerText = `(TOTAL AVAIL: ${totalExistingAvail.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} KG)`;
    }
  }
}

function onActionTypeChange(type) {
  const submitBtn = document.getElementById('sm-submit-btn');
  if (type === 'INWARD') {
    submitBtn.innerText = 'SUBMIT INWARD';
    submitBtn.style.background = '#f59e0b';
  } else {
    submitBtn.innerText = 'SUBMIT OUTWARD';
    submitBtn.style.background = '#dc2626';
  }
  recalcDropdownTotals();
}

document.addEventListener('DOMContentLoaded', () => {
  renderLocationDropdownMenu();
});

function submitSmAction(e) {
  e.preventDefault();
  const prodId = document.getElementById('sm-prod-id').value;
  const stageSelect = document.getElementById('sm-stage').value;
  const p = allMasterProducts.find(item => item.id == prodId);
  const stage = stageSelect !== 'ALL' ? stageSelect : (p ? p.type : 'RAW');
  const grade = document.getElementById('sm-grade').value;
  const actionType = document.getElementById('sm-action-type').value;
  const notes = document.getElementById('sm-notes').value;
  const btn = document.getElementById('sm-submit-btn');

  if (!prodId) {
    if (typeof app !== 'undefined' && app.toast) app.toast('Please select a product', 'error');
    else alert('Please select a product');
    return;
  }

  const inputs = document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input');
  const locationSplits = [];
  inputs.forEach(inp => {
    const loc = inp.getAttribute('data-loc');
    const qty = parseFloat(inp.value) || 0;
    const avail = parseFloat(inp.getAttribute('data-avail') || 0);

    if (qty > 0) {
      if (actionType === 'OUTWARD' && qty > avail) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Not Enough Quantity!',
            text: `Not enough quantity to do stock outwards in ${loc.toUpperCase()}. Available: ${avail} kg, Requested: ${qty} kg`,
            confirmButtonColor: '#f59e0b',
            background: '#ffffff',
            color: '#333333'
          });
        } else if (typeof app !== 'undefined' && app.toast) {
          app.toast(`Not enough quantity to do stock outwards in ${loc}. Available: ${avail} kg, Requested: ${qty} kg`, 'error');
        } else {
          alert(`Not enough quantity to do stock outwards in ${loc}. Available: ${avail} kg, Requested: ${qty} kg`);
        }
        throw new Error('Outward limit exceeded');
      }
      locationSplits.push({ location: loc, quantity: qty });
    }
  });

  // Fallback if user typed directly into main QTY field without opening dropdown
  if (locationSplits.length === 0) {
    const mainQty = parseFloat(document.getElementById('sm-total-qty-input')?.value) || 0;
    if (mainQty > 0) {
      const firstLocInput = inputs[0];
      const defaultLoc = firstLocInput ? firstLocInput.getAttribute('data-loc') : 'Main Warehouse';
      locationSplits.push({ location: defaultLoc, quantity: mainQty });
    }
  }

  if (locationSplits.length === 0) {
    if (typeof app !== 'undefined' && app.toast) app.toast('Please enter a quantity for at least one location', 'error');
    else alert('Please enter a quantity for at least one location');
    return;
  }

  if (window.isReadOnly) {
    if (typeof Swal !== 'undefined') Swal.fire('View-Only Mode', 'Action disabled in View-Only mode.', 'warning');
    else alert('Action disabled in View-Only mode.');
    return;
  }

  btn.disabled = true;
  btn.innerText = 'Processing...';

  const targetUrl = actionType === 'INWARD'
    ? window.baseUrl + '/' + window.userSlug + '/action'
    : window.baseUrl + '/' + window.userSlug + '/outward';

  fetch(targetUrl, {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Accept': 'application/json',
      'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
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
      if (typeof app !== 'undefined' && app.toast) app.toast(data.message || 'Transaction recorded!');
      else alert(data.message || 'Transaction recorded!');
      setTimeout(() => location.reload(), 1000);
    } else {
      if (typeof app !== 'undefined' && app.toast) app.toast(data.message || 'Error processing request', 'error');
      else alert(data.message || 'Error processing request');
      btn.disabled = false;
      onActionTypeChange(actionType);
    }
  })
  .catch(err => {
    if (err.message !== 'Outward limit exceeded') {
      if (typeof app !== 'undefined' && app.toast) app.toast('Error: ' + err.message, 'error');
      else alert('Error: ' + err.message);
    }
    btn.disabled = false;
    onActionTypeChange(actionType);
  });
}
document.addEventListener('DOMContentLoaded', function() {
  const prodSelect = document.getElementById('sm-prod-id');
  if (prodSelect && prodSelect.value) {
    onProductChange(prodSelect.value);
  } else {
    loadSmLocations();
  }
});
</script>
@endsection
