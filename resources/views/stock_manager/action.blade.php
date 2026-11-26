@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
@php
  $nowIst = \Carbon\Carbon::now('Asia/Kolkata');
  $todayDateIst = $nowIst->format('Y-m-d');
  $nowTimeIst = $nowIst->format('H:i');

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

  $smActionPrefix = (request()->segment(2) == 'stock-manager' || request()->segment(2) == 'stock_manager')
      ? request()->segment(1) . '/' . request()->segment(2)
      : request()->segment(1);
@endphp

<script>
  window.smInwardUrl = "{{ url($smActionPrefix . '/action') }}";
  window.smOutwardUrl = "{{ url($smActionPrefix . '/outward') }}";
</script>

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
    transition: all 0.2s;
}
.custom-location-dropdown button:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}
.custom-location-dropdown ul.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    width: 100%;
    max-height: 280px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    list-style: none;
    margin-top: 0.25rem;
    padding: 0.4rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.custom-location-dropdown ul.dropdown-menu li.loc-item-row {
    padding: 0.5rem 0.6rem;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.15s ease;
    margin-bottom: 0.25rem;
}
.custom-location-dropdown ul.dropdown-menu li.loc-item-row:hover {
    background: #f3f4f6;
}
.custom-location-dropdown ul.dropdown-menu li.loc-item-row.is-selected {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
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

/* Select2 Custom Styles */
.select2-container .select2-selection--single {
  height: 2.75rem !important;
  border: 1px solid #d1d5db !important;
  border-radius: 8px !important;
  display: flex !important;
  align-items: center !important;
  background-color: #fff !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 2.75rem !important;
  padding-left: 0.75rem !important;
  color: #111827 !important;
  font-weight: 600 !important;
  font-size: 0.95rem !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 2.75rem !important;
  right: 0.5rem !important;
}
.select2-dropdown {
  border-color: #d1d5db !important;
  border-radius: 8px !important;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;
}
.sm-card input[type="date"],
.sm-card input[type="time"] {
  color-scheme: light;
}
.sm-dt-pill {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #374151;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.32rem 0.7rem;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.sm-dt-pill:hover {
  border-color: #f59e0b;
  background: #fffbeb;
  color: #b45309;
}
.sm-dt-pill.is-active {
  background: #f59e0b;
  border-color: #f59e0b;
  color: #ffffff !important;
}
</style>

<div class="sm-card">
  <div style="font-size:1.25rem; font-weight:700; margin-bottom:1.5rem; color:#111827; display:flex; align-items:center; gap:8px;">
    <span>📦 Stock Inward / Outward Entry</span>
  </div>

  <form id="sm-action-form" onsubmit="submitSmAction(event)">
    <!-- 0. Entry Date * -->
    <div class="sm-datetime-box" style="margin-bottom:1.25rem; padding:14px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <span style="font-size:0.86rem; font-weight:700; color:#374151; text-transform:uppercase;">📅 ENTRY DATE *</span>
          <span id="sm-datetime-mode-badge" style="display:inline-flex; align-items:center; gap:5px; font-size:0.74rem; font-weight:700; padding:2px 9px; border-radius:999px; background:#dcfce7; color:#15803d;">
            ● Live (Today)
          </span>
        </div>
        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
          <button type="button" id="sm-btn-live-now" class="sm-dt-pill is-active" onclick="setSmEntryLiveNow()">
            ⚡ Live (Now)
          </button>
          <button type="button" id="sm-btn-yesterday" class="sm-dt-pill" onclick="setSmEntryYesterday()">
            ⏪ Yesterday
          </button>
        </div>
      </div>

      <div>
        <label style="font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#6b7280; display:block;">ENTRY DATE *</label>
        <input type="date" id="sm-entry-date" name="entry_date" value="{{ $todayDateIst }}" max="{{ $todayDateIst }}" required onchange="onSmDateTimeManualChange()" style="padding:0.62rem 0.75rem; width:100%; font-size:0.95rem; font-weight:700; cursor:pointer; border-radius:8px; border:1px solid #d1d5db; background:#ffffff; box-sizing:border-box;">
      </div>

      <div id="sm-backdate-banner" style="display:none; margin-top:10px; padding:8px 12px; border-radius:8px; background:#fffbeb; border:1px solid #fcd34d; color:#92400e; font-size:0.82rem; font-weight:600; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
        <span id="sm-backdate-banner-text">⚠️ Custom Date Entry</span>
        <button type="button" onclick="setSmEntryLiveNow()" style="background:none; border:none; color:#b45309; font-weight:700; font-size:0.78rem; text-decoration:underline; cursor:pointer; padding:0;">
          Reset to Today
        </button>
      </div>
    </div>

    <!-- 1. Stage * -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Stage *</label>
      <select id="sm-stage" onchange="onStageChange(this.value)" style="padding:0.75rem; width:100%; font-size:1rem; font-weight:600; cursor:pointer;">
        <option value="ALL" selected>ALL (RAW, SEMI, FG, PACKAGING)</option>
        <option value="RAW">RAW</option>
        <option value="SEMI">SEMI</option>
        <option value="FINISHED">FG</option>
        <option value="PACKAGING">PACKAGING</option>
      </select>
    </div>

    <!-- 2. Product Dropdown with Smart Search -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Product *</label>
      <select id="sm-prod-id" name="product_id" onchange="onProductChange(this.value)" required style="padding:0.75rem; width:100%; font-size:0.95rem; font-weight:600; cursor:pointer;">
        <option value="" disabled selected>-- SELECT PRODUCT --</option>
        @foreach($pageData['products'] as $p)
          @php
            $cleanType = strtoupper(trim((string)$p->type));
            $dispType = in_array($cleanType, ['FINISHED', 'FG']) ? 'FG' : $p->type;
          @endphp
          <option value="{{ $p->id }}" data-type="{{ $p->type }}">{{ $p->name }} ({{ $dispType }})</option>
        @endforeach
      </select>
    </div>

    <!-- 3. Grade -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Grade *</label>
      <select id="sm-grade" name="grade" onchange="onGradeChange(this.value)" style="padding:0.75rem; width:100%; font-weight:600; cursor:pointer;">
        <option value="ALL">ALL GRADES</option>
        <option value="NONE">NONE</option>
      </select>
    </div>

    <!-- 4. Action Type * (Stock Inward / Stock Outward) -->
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label>Action Type *</label>
      <select id="sm-action-type" name="action_type" onchange="onActionTypeChange(this.value)" style="padding:0.75rem; width:100%; font-size:1rem; font-weight:700; cursor:pointer;">
        <option value="INWARD" selected>📥 Stock Inward</option>
        <option value="OUTWARD">📤 Stock Outward</option>
      </select>
    </div>

    <!-- 5. Select Locations & Quantities -->
    <div class="bs-location-row" style="margin-bottom:1.2rem; padding:14px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:6px;">
        <label style="font-size:0.85rem; font-weight:700; color:#374151; text-transform:uppercase; margin:0;">
          SELECT LOCATIONS & QUANTITIES: <span id="sm-action-total-qty" style="color:#d97706; font-size:0.95rem; font-weight:800; margin-left:6px;">(TOTAL AVAIL: 0.00 KG)</span>
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
          <input type="number" min="0" step="0.001" class="sm-loc-qty no-spinners" id="sm-total-qty-input" placeholder="0" value="" oninput="onDirectTotalQtyInput(this)" style="height:2.5rem; padding:0.4rem 0.6rem; font-size:0.9rem; font-weight:700; text-align:center; width:100%;">
        </div>
      </div>
    </div>

    <!-- 6. Notes -->
    <div class="form-group" style="margin-bottom:1.5rem;">
      <label>Notes</label>
      <textarea id="sm-notes" name="notes" placeholder="Enter notes (optional)..." style="padding:0.7rem; width:100%; height:70px; resize:vertical;"></textarea>
    </div>

    <!-- 7. Submit Button -->
    @if(empty($isReadOnly))
      <button type="submit" class="btn" id="sm-submit-btn" style="width:100%; padding:0.8rem; font-size:1rem; font-weight:700; background:#f59e0b; color:#ffffff !important; border:none; border-radius:8px; cursor:pointer;">
        SUBMIT INWARD
      </button>
    @endif
  </form>
</div>

<script>
const allMasterProducts = {!! json_encode($productsJson) !!};
const masterLocations = {!! json_encode($pageData['locations'] ?? []) !!};
window.currentLocBreakdown = [];
window.selectedLocation = ''; // Currently selected single location

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

function formatNum(n) {
  const num = parseFloat(n) || 0;
  return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function onStageChange(stage) {
  const prodSelect = document.getElementById('sm-prod-id');
  const currentVal = prodSelect.value;
  
  let html = '<option value="" disabled selected>-- SELECT PRODUCT --</option>';
  allMasterProducts.forEach(p => {
    const cleanType = String(p.type || '').trim().toUpperCase();
    const cleanStage = String(stage || '').trim().toUpperCase();
    const isStageMatch = cleanStage === 'ALL' || cleanType === cleanStage || ((cleanStage === 'FINISHED' || cleanStage === 'FG') && (cleanType === 'FINISHED' || cleanType === 'FG'));
    if (isStageMatch) {
      const dispType = (cleanType === 'FINISHED' || cleanType === 'FG') ? 'FG' : p.type;
      html += `<option value="${p.id}" data-type="${p.type}">${p.name} (${dispType})</option>`;
    }
  });
  prodSelect.innerHTML = html;

  const exists = allMasterProducts.some(p => {
    const cleanType = String(p.type || '').trim().toUpperCase();
    const cleanStage = String(stage || '').trim().toUpperCase();
    return p.id == currentVal && (cleanStage === 'ALL' || cleanType === cleanStage || ((cleanStage === 'FINISHED' || cleanStage === 'FG') && (cleanType === 'FINISHED' || cleanType === 'FG')));
  });
  if (exists) {
    prodSelect.value = currentVal;
  } else {
    document.getElementById('sm-grade').innerHTML = '<option value="ALL">ALL GRADES</option><option value="NONE">NONE</option>';
    window.selectedLocation = '';
  }

  if (window.jQuery && $.fn.select2 && $('#sm-prod-id').data('select2')) {
    $('#sm-prod-id').trigger('change.select2');
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

function onGradeChange(grade) {
  loadSmLocations(false);
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
  const baseUrl = window.baseUrl || '';

  // Direct fetch to API with fallback
  const fetchStock = fetch(`${baseUrl}/${userSlug}/api/stock/locations?${query}`)
    .then(r => r.ok ? r.json() : fetch(`${baseUrl}/api/stock/locations?${query}`).then(r2 => r2.json()))
    .catch(() => fetch(`${baseUrl}/api/stock/locations?${query}`).then(r2 => r2.json()).catch(() => ({ success: false })));

  const fetchLocs = fetch(`${baseUrl}/${userSlug}/api/locations`)
    .then(r => r.ok ? r.json() : fetch(`${baseUrl}/api/locations`).then(r2 => r2.json()))
    .catch(() => fetch(`${baseUrl}/api/locations`).then(r2 => r2.json()).catch(() => ({ success: false })));

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
    if (mainQtyInp) mainQtyInp.value = '';
    window.selectedLocation = '';
  }

  const locMap = {};
  (window.currentLocBreakdown || []).forEach(l => { locMap[l.name.trim().toLowerCase()] = l.quantity; });

  const defaultLocs = ['Main Warehouse', 'Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
  const baseLocs = (window.liveMasterLocations && window.liveMasterLocations.length)
    ? window.liveMasterLocations
    : (masterLocations.length ? masterLocations : defaultLocs);

  const allLocNames = new Set(baseLocs);
  (window.currentLocBreakdown || []).forEach(l => {
    if (l.name) allLocNames.add(l.name.trim());
  });

  // If no location is currently selected, pick default
  if (!window.selectedLocation && allLocNames.size > 0) {
    window.selectedLocation = Array.from(allLocNames)[0];
  }

  let html = '';
  allLocNames.forEach(locName => {
    const key = locName.trim().toLowerCase();
    const avail = locMap[key] !== undefined ? parseFloat(locMap[key]) : 0;
    const formattedAvail = formatNum(avail);
    const preservedVal = existingValues[key] !== undefined ? existingValues[key] : '';
    const isSelected = (window.selectedLocation && window.selectedLocation.toLowerCase() === key);

    html += `
      <li class="loc-item-row ${isSelected ? 'is-selected' : ''}" onclick="onSelectLocationRow('${escapeHtml(locName)}', event)" data-loc="${escapeHtml(locName)}" style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <div style="display:flex; align-items:center; gap:6px; flex:1;">
          <span style="font-weight:700; color:#1f2937; font-size:0.85rem;">📍 ${locName.toUpperCase()}</span>
          <small style="color:${avail > 0 ? '#16a34a' : '#9ca3af'}; font-weight:800; font-size:0.75rem;">(AVAIL: ${formattedAvail} KG)</small>
        </div>
        <div style="display:flex; align-items:center;" onclick="event.stopPropagation();">
          <input type="number" min="0" step="0.001" class="form-control form-control-sm inner-qty-input no-spinners" data-loc="${escapeHtml(locName)}" data-avail="${avail}" oninput="onInnerQtyChange(this)" style="width:75px; text-align:center; padding:0.25rem; height:1.8rem; font-size:0.82rem; border:1px solid #d1d5db; border-radius:4px; font-weight:700;" value="${preservedVal}" placeholder="0">
        </div>
      </li>
    `;
  });

  dropdownMenu.innerHTML = html;
  recalcDropdownTotals();
}

function onSelectLocationRow(locName, e) {
  if (e) e.stopPropagation();
  window.selectedLocation = locName;

  // Mark selected in dropdown
  document.querySelectorAll('#sm-custom-location-dropdown .loc-item-row').forEach(row => {
    if (row.getAttribute('data-loc') === locName) {
      row.classList.add('is-selected');
    } else {
      row.classList.remove('is-selected');
    }
  });

  // Close dropdown
  const dropdownMenu = document.querySelector('#sm-custom-location-dropdown .dropdown-menu');
  if (dropdownMenu) dropdownMenu.style.display = 'none';

  // If user had typed in main QTY, sync to this location
  const mainQtyInput = document.getElementById('sm-total-qty-input');
  const mainVal = parseFloat(mainQtyInput?.value) || 0;
  if (mainVal > 0) {
    document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input').forEach(inp => {
      if (inp.getAttribute('data-loc') === locName) {
        inp.value = mainVal;
      } else {
        inp.value = '';
      }
    });
  }

  // Focus QTY input for user convenience
  if (mainQtyInput && !mainQtyInput.value) {
    mainQtyInput.focus();
  }

  recalcDropdownTotals();
}

function onInnerQtyChange(innerInp) {
  const inputs = document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input');
  let sum = 0;
  let singleLoc = '';
  let countFilled = 0;

  inputs.forEach(inp => {
    const val = parseFloat(inp.value) || 0;
    if (val > 0) {
      sum += val;
      countFilled++;
      singleLoc = inp.getAttribute('data-loc');
    }
  });

  if (countFilled === 1) {
    window.selectedLocation = singleLoc;
  }

  const mainQtyInput = document.getElementById('sm-total-qty-input');
  if (mainQtyInput) {
    mainQtyInput.value = sum > 0 ? sum : '';
  }

  recalcDropdownTotals();
}

function onDirectTotalQtyInput(mainInput) {
  const mainVal = parseFloat(mainInput.value) || 0;

  // If a location is selected, sync the value into its inner input
  if (window.selectedLocation) {
    document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input').forEach(inp => {
      if (inp.getAttribute('data-loc') === window.selectedLocation) {
        inp.value = mainVal > 0 ? mainVal : '';
      } else {
        inp.value = '';
      }
    });
  } else {
    // Default to first location
    const firstInp = document.querySelector('#sm-custom-location-dropdown .inner-qty-input');
    if (firstInp) {
      window.selectedLocation = firstInp.getAttribute('data-loc');
      firstInp.value = mainVal > 0 ? mainVal : '';
    }
  }

  recalcDropdownTotals();
}

function recalcDropdownTotals() {
  const inputs = document.querySelectorAll('#sm-custom-location-dropdown .inner-qty-input');
  let sumEnteredQty = 0;
  const activeLocSummary = [];
  const actionType = document.getElementById('sm-action-type').value;

  const totalExistingAvail = (window.currentLocBreakdown || []).reduce((sum, l) => sum + (parseFloat(l.quantity) || 0), 0);

  // Check quantities
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

  // Update button text
  const btnText = document.querySelector('#sm-custom-location-dropdown .loc-dropdown-text');
  if (btnText) {
    if (activeLocSummary.length > 1) {
      btnText.textContent = activeLocSummary.join(', ');
    } else if (window.selectedLocation) {
      const locObj = (window.currentLocBreakdown || []).find(l => l.name.toLowerCase() === window.selectedLocation.toLowerCase());
      const locAvail = locObj ? parseFloat(locObj.quantity) : 0;
      btnText.textContent = `📍 ${window.selectedLocation.toUpperCase()} (AVAIL: ${formatNum(locAvail)} KG)`;
    } else {
      btnText.textContent = 'Select Storage Location';
    }
  }

  // Update calculation badge
  const badge = document.getElementById('sm-action-total-qty');
  if (badge) {
    let specificLocAvail = null;
    if (window.selectedLocation && activeLocSummary.length <= 1) {
      const locObj = (window.currentLocBreakdown || []).find(l => l.name.toLowerCase() === window.selectedLocation.toLowerCase());
      if (locObj) specificLocAvail = parseFloat(locObj.quantity) || 0;
    }

    const availBase = (specificLocAvail !== null && activeLocSummary.length <= 1) ? specificLocAvail : totalExistingAvail;

    if (sumEnteredQty > 0) {
      if (actionType === 'INWARD') {
        const grandTotal = totalExistingAvail + sumEnteredQty;
        badge.innerHTML = `(TOTAL AVAIL: ${formatNum(totalExistingAvail)} KG <span style="color:#16a34a; font-weight:800;">→ NEW TOTAL: ${formatNum(grandTotal)} KG</span>)`;
      } else {
        // Outward
        const grandTotal = Math.max(0, totalExistingAvail - sumEnteredQty);
        if (sumEnteredQty > availBase) {
          badge.innerHTML = `(TOTAL AVAIL: ${formatNum(totalExistingAvail)} KG <span style="color:#dc2626; font-weight:800;">→ EXCEEDS AVAIL! SHORT BY ${formatNum(sumEnteredQty - availBase)} KG</span>)`;
        } else {
          badge.innerHTML = `(TOTAL AVAIL: ${formatNum(totalExistingAvail)} KG <span style="color:#2563eb; font-weight:800;">→ NEW TOTAL: ${formatNum(grandTotal)} KG</span>)`;
        }
      }
    } else {
      badge.innerHTML = `(TOTAL AVAIL: ${formatNum(totalExistingAvail)} KG)`;
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

window.smIsLiveTime = true;

function getIstNowParts(offsetDays = 0) {
  const targetMs = Date.now() + (offsetDays * 86400000);
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Kolkata',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).formatToParts(new Date(targetMs));

  const map = {};
  parts.forEach(p => { map[p.type] = p.value; });
  const hh = map.hour === '24' ? '00' : map.hour;
  return {
    date: `${map.year}-${map.month}-${map.day}`,
    time: `${hh}:${map.minute}`
  };
}

function formatSmHumanDate(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-');
  return `${d}-${m}-${y}`;
}

function updateSmDateTimeUi() {
  const dateInp = document.getElementById('sm-entry-date');
  const badge = document.getElementById('sm-datetime-mode-badge');
  const banner = document.getElementById('sm-backdate-banner');
  const bannerText = document.getElementById('sm-backdate-banner-text');
  const btnLive = document.getElementById('sm-btn-live-now');
  const btnYesterday = document.getElementById('sm-btn-yesterday');
  if (!dateInp) return;

  const todayIst = getIstNowParts(0).date;
  const yesterdayIst = getIstNowParts(-1).date;
  dateInp.max = todayIst;

  if (window.smIsLiveTime || dateInp.value === todayIst) {
    window.smIsLiveTime = (dateInp.value === todayIst);
    if (btnLive) btnLive.classList.toggle('is-active', dateInp.value === todayIst);
    if (btnYesterday) btnYesterday.classList.remove('is-active');
    if (badge) {
      badge.style.background = '#dcfce7';
      badge.style.color = '#15803d';
      badge.textContent = '● Live (Today)';
    }
    if (banner) banner.style.display = 'none';
  } else {
    if (btnLive) btnLive.classList.remove('is-active');
    if (btnYesterday) {
      if (dateInp.value === yesterdayIst) btnYesterday.classList.add('is-active');
      else btnYesterday.classList.remove('is-active');
    }
    const isPastDay = dateInp.value && dateInp.value < todayIst;
    const humanStr = formatSmHumanDate(dateInp.value);
    if (badge) {
      badge.style.background = '#fef3c7';
      badge.style.color = '#b45309';
      badge.textContent = isPastDay ? '🕒 Backdated Entry' : '📅 Selected Date';
    }
    if (banner && bannerText) {
      banner.style.display = 'flex';
      bannerText.textContent = isPastDay
        ? `⚠️ Backdated Entry: Stock will be recorded for ${humanStr}`
        : `📅 Stock will be recorded for ${humanStr}`;
    }
  }
}

function syncSmLiveClock() {
  if (!window.smIsLiveTime) return;
  const dateInp = document.getElementById('sm-entry-date');
  const nowIst = getIstNowParts(0);
  if (dateInp && !dateInp.value) {
    dateInp.max = nowIst.date;
    dateInp.value = nowIst.date;
  }
  updateSmDateTimeUi();
}

function setSmEntryLiveNow() {
  window.smIsLiveTime = true;
  const dateInp = document.getElementById('sm-entry-date');
  const nowIst = getIstNowParts(0);
  if (dateInp) {
    dateInp.max = nowIst.date;
    dateInp.value = nowIst.date;
  }
  updateSmDateTimeUi();
}

function setSmEntryYesterday() {
  window.smIsLiveTime = false;
  const dateInp = document.getElementById('sm-entry-date');
  const yesterdayIst = getIstNowParts(-1);
  if (dateInp) dateInp.value = yesterdayIst.date;
  updateSmDateTimeUi();
}

function onSmDateTimeManualChange() {
  const dateInp = document.getElementById('sm-entry-date');
  const todayIst = getIstNowParts(0).date;
  window.smIsLiveTime = (dateInp && dateInp.value === todayIst);
  updateSmDateTimeUi();
}

function submitSmAction(e) {
  e.preventDefault();
  const prodId = document.getElementById('sm-prod-id').value;
  const stageSelect = document.getElementById('sm-stage').value;
  const p = allMasterProducts.find(item => item.id == prodId);
  const stage = stageSelect !== 'ALL' ? stageSelect : (p ? p.type : 'RAW');
  const grade = document.getElementById('sm-grade').value;
  const actionType = document.getElementById('sm-action-type').value;
  const notes = document.getElementById('sm-notes').value;
  const entryDate = document.getElementById('sm-entry-date')?.value || '';
  const btn = document.getElementById('sm-submit-btn');

  if (!window.smIsLiveTime) {
    if (!entryDate) {
      if (typeof Swal !== 'undefined') Swal.fire('Missing Date', 'Please select Entry Date.', 'warning');
      else alert('Please select Entry Date.');
      return;
    }
    const nowIst = getIstNowParts(0);
    if (entryDate > nowIst.date) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Future Date Not Allowed',
          text: 'Entry Date cannot be in the future. Please pick a valid past or today\'s date, or click "Live (Now)".',
          confirmButtonColor: '#f59e0b'
        });
      } else {
        alert('Entry Date cannot be in the future.');
      }
      return;
    }
  }

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
            text: `Not enough quantity to do stock outward in ${loc.toUpperCase()}. Available: ${avail} kg, Requested: ${qty} kg`,
            confirmButtonColor: '#f59e0b',
            background: '#ffffff',
            color: '#333333'
          });
        } else if (typeof app !== 'undefined' && app.toast) {
          app.toast(`Not enough quantity to do stock outward in ${loc}. Available: ${avail} kg, Requested: ${qty} kg`, 'error');
        } else {
          alert(`Not enough quantity to do stock outward in ${loc}. Available: ${avail} kg, Requested: ${qty} kg`);
        }
        throw new Error('Outward limit exceeded');
      }
      locationSplits.push({ location: loc, quantity: qty });
    }
  });

  // Fallback: if user typed directly into main QTY field
  if (locationSplits.length === 0) {
    const mainQty = parseFloat(document.getElementById('sm-total-qty-input')?.value) || 0;
    if (mainQty > 0) {
      const targetLoc = window.selectedLocation || (inputs[0] ? inputs[0].getAttribute('data-loc') : 'Main Warehouse');
      
      // If outward, check avail for this targetLoc
      if (actionType === 'OUTWARD') {
        const locObj = (window.currentLocBreakdown || []).find(l => l.name.toLowerCase() === targetLoc.toLowerCase());
        const avail = locObj ? parseFloat(locObj.quantity) : 0;
        if (mainQty > avail) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Not Enough Quantity!',
              text: `Not enough quantity to do stock outward in ${targetLoc.toUpperCase()}. Available: ${avail} kg, Requested: ${mainQty} kg`,
              confirmButtonColor: '#f59e0b',
            });
          } else {
            alert(`Not enough quantity to do stock outward in ${targetLoc}. Available: ${avail} kg, Requested: ${mainQty} kg`);
          }
          return;
        }
      }
      locationSplits.push({ location: targetLoc, quantity: mainQty });
    }
  }

  if (locationSplits.length === 0) {
    if (typeof app !== 'undefined' && app.toast) app.toast('Please select a storage location and enter a quantity', 'error');
    else alert('Please select a storage location and enter a quantity');
    return;
  }

  if (window.isReadOnly) {
    if (typeof Swal !== 'undefined') Swal.fire('View-Only Mode', 'Action disabled in View-Only mode.', 'warning');
    else alert('Action disabled in View-Only mode.');
    return;
  }

  btn.disabled = true;
  btn.innerText = 'Processing...';

  const userSlug = window.userSlug || 'stock_manager';
  const baseUrl = (window.baseUrl || '').replace(/\/+$/, '');
  const pathname = window.location.pathname;

  // Sub-panel prefix is only applicable when visiting under another role (e.g. /sub_admin/stock-manager/action)
  const isSubPanel = (userSlug !== 'stock_manager' && userSlug !== 'stockmanager') && 
                     (pathname.includes('/stock-manager') || pathname.includes('/stock_manager'));
  const smSub = pathname.includes('/stock-manager') ? 'stock-manager' : 'stock_manager';

  const defaultInward = isSubPanel 
    ? (baseUrl ? `${baseUrl}/${userSlug}/${smSub}/action` : `/${userSlug}/${smSub}/action`)
    : (baseUrl ? `${baseUrl}/${userSlug}/action` : `/${userSlug}/action`);

  const defaultOutward = isSubPanel 
    ? (baseUrl ? `${baseUrl}/${userSlug}/${smSub}/outward` : `/${userSlug}/${smSub}/outward`)
    : (baseUrl ? `${baseUrl}/${userSlug}/outward` : `/${userSlug}/outward`);

  const targetUrl = actionType === 'INWARD'
    ? (window.smInwardUrl || defaultInward)
    : (window.smOutwardUrl || defaultOutward);

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
      notes: notes,
      entry_date: entryDate,
      is_live_time: Boolean(window.smIsLiveTime)
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: data.message || 'Transaction recorded successfully!',
          timer: 1500,
          showConfirmButton: false
        });
      } else if (typeof app !== 'undefined' && app.toast) {
        app.toast(data.message || 'Transaction recorded!');
      } else {
        alert(data.message || 'Transaction recorded!');
      }
      setTimeout(() => location.reload(), 1200);
    } else {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'Error processing request'
        });
      } else if (typeof app !== 'undefined' && app.toast) {
        app.toast(data.message || 'Error processing request', 'error');
      } else {
        alert(data.message || 'Error processing request');
      }
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

// Initialize Select2 & page components
document.addEventListener('DOMContentLoaded', function() {
  syncSmLiveClock();
  setInterval(syncSmLiveClock, 15000);

  if (window.jQuery && $.fn.select2) {
    function matchCustom(params, data) {
      if ($.trim(params.term) === '') return data;
      if (typeof data.text === 'undefined') return null;
      var term = params.term.toLowerCase().trim();
      var text = data.text.toLowerCase();
      var tokens = term.split(/\s+/);
      for (var i = 0; i < tokens.length; i++) {
        if (text.indexOf(tokens[i]) === -1) return null;
      }
      return data;
    }

    $('#sm-prod-id').select2({
      placeholder: "-- SELECT PRODUCT --",
      allowClear: true,
      width: '100%',
      matcher: matchCustom
    }).on('change', function() {
      onProductChange(this.value);
    });
  }

  const prodSelect = document.getElementById('sm-prod-id');
  if (prodSelect && prodSelect.value) {
    onProductChange(prodSelect.value);
  } else {
    loadSmLocations();
  }
});
</script>
@endsection
