@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'production');
@endphp
<div class="tabs" style="margin-bottom:1rem;">
  <a class="tab-btn {{ $tab==='production'?'active':'' }}" href="?tab=production" style="text-decoration:none;">Create Production</a>
  <a class="tab-btn {{ $tab==='transfer'?'active':'' }}" href="?tab=transfer" style="text-decoration:none;">Transfer Raw Stock</a>
</div>

@if($tab === 'production')
<div class="card">
  <div class="card-title">Create Semi Production Order</div>
  
  <div class="form-group">
    <label>Target Product</label>
    <select id="prod-output" onchange="app.onTargetProductSelected()">
      <option value="" disabled selected>-- Select Product --</option>
      @foreach(collect($pageData['products'])->filter(fn($p) => $p['type'] !== 'RAW') as $p)
        <option value="{{ $p['id'] }}">{{ $p['name'] }} - (grade- N/A) (type - semi)</option>
      @endforeach
    </select>
  </div>

  <div class="form-group hidden" id="grade-selection-group">
    <label>Select Grade</label>
    <select id="prod-grade" onchange="app.onGradeSelected()">
      <!-- Grades injected dynamically by JS -->
    </select>
  </div>
  
  <div id="materials-section" style="margin-top: 2rem; border-top: 1px dashed var(--glass-border); padding-top: 1.5rem;">
    <div class="form-group">
      <label>Expected Output Quantity (kg)</label>
      <input type="number" id="prod-out-qty" placeholder="Quantity produced">
    </div>

    <div class="flex-between mb-1 mt-1">
      <label style="margin:0; font-size:1rem; color:var(--primary-light);">Add Material (Consumed)</label>
      <button class="btn btn-sm btn-secondary" onclick="app.addInputRow()" style="width:auto;">+ Add Material</button>
    </div>
    
    <div id="input-rows" style="display:flex; flex-direction:column; gap:10px;">
      <!-- Rows injected here -->
    </div>
    
    <div class="form-group mt-1">
      <label>Storage Location</label>
      <select id="semi-storage-location" style="padding:0.7rem;">
        <!-- Injected by JS -->
      </select>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label style="font-weight:600;">Reference Type</label>
      <select id="semi-ref-type" name="reference_type" onchange="toggleSemiRefFields()" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
        <option value="">None</option>
        <option value="PO">Purchase Order</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <div class="form-group" id="semi-po-group" style="margin-bottom:1.5rem; display:none;">
      <label style="font-weight:600;">Select Purchase Order</label>
      <select id="semi-po-id" name="po_id" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
        <option value="">-- Select PO --</option>
        @foreach($pageData['purchaseOrders'] as $po)
          <option value="{{ $po->id }}">PO #{{ $po->id }} - {{ $po->product ? $po->product->name : 'Unknown' }} ({{ $po->quantity }}kg)</option>
        @endforeach
      </select>
    </div>

    <div class="form-group" id="semi-other-group" style="margin-bottom:1.5rem; display:none;">
      <label style="font-weight:600;">Other Reference Note</label>
      <input type="text" id="semi-other-note" name="other_note" placeholder="Enter reference..." style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>
    
    <button class="btn mt-2" onclick="app.reviewProduction('RAW', 'SEMI', 'rawStock')">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
      Review Production
    </button>
  </div>
</div>
@endif

@if($tab === 'transfer')
<div class="card">
  <div class="card-title">➡️ Transfer Raw Stock to Semi-Finished</div>
  
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); margin-bottom:1rem; max-height:250px; overflow-y:auto; padding:5px;">
    @foreach($pageData['rawStock'] as $rs)
      <div class="rs-card" onclick="selectTransferMaterial('{{ $rs['productId'] }}', '{{ addslashes($rs['name']) }}', '{{ addslashes($rs['grade']) }}', {{ $rs['quantity'] }}, this)" 
        style="border:2px solid transparent; border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.05); text-align:center; padding:12px 6px; transition:0.2s;">
        <div style="font-size:0.85rem; font-weight:600; padding:4px 3px; line-height:1.2;">{{ $rs['name'] }} <small>({{ $rs['grade'] }})</small></div>
        <div style="font-size:0.75rem; color:var(--primary-light);">Avail: {{ number_format($rs['quantity'], 2) }} {{ $rs['unit'] }}</div>
      </div>
    @endforeach
  </div>

  <form id="semi-transfer-form" onsubmit="submitTransfer(event)">
    <input type="hidden" id="transfer-prod" name="product_id" value="">
    <input type="hidden" id="transfer-grade" name="grade" value="">
    <div id="transfer-selected-name" style="font-size:0.9rem; font-weight:bold; color:var(--secondary); margin-bottom:0.8rem; min-height:1.2em;"></div>
    
    <div class="form-group" style="margin-bottom:1rem;">
      <label style="font-weight:600;">Quantity to Transfer</label>
      <input type="number" name="quantity" id="transfer-qty" step="0.001" min="0.001" placeholder="Enter quantity" required style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label style="font-weight:600;">Notes</label>
      <textarea id="transfer-notes" name="notes" placeholder="Optional notes..." style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; height:70px; resize:vertical;"></textarea>
    </div>
    
    <button type="submit" class="btn mt-1" id="transfer-submit-btn">Transfer to Semi</button>
  </form>
</div>

<script>
function selectTransferMaterial(id, name, grade, maxQty, el) {
  document.querySelectorAll('.rs-card').forEach(c => c.style.borderColor = 'transparent');
  el.style.borderColor = 'var(--secondary)';
  document.getElementById('transfer-prod').value = id;
  document.getElementById('transfer-grade').value = grade;
  document.getElementById('transfer-qty').max = maxQty;
  document.getElementById('transfer-selected-name').innerText = 'Selected: ' + name + ' (' + grade + ') - Max: ' + maxQty;
}

function submitTransfer(e) {
  e.preventDefault();
  const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
  const prodId = document.getElementById('transfer-prod').value;
  const grade = document.getElementById('transfer-grade').value;
  const qty = Number(document.getElementById('transfer-qty').value);
  const notes = document.getElementById('transfer-notes').value;
  const btn = document.getElementById('transfer-submit-btn');

  if (!prodId) {
    app.toast('Please select a material to transfer', 'error');
    return;
  }
  if (!qty || qty <= 0) {
    app.toast('Enter a valid quantity', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = `Transferring...`;

  fetch('{{ route("semi.transfer_to_semi") }}', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken 
    },
    body: JSON.stringify({ product_id: prodId, quantity: qty, grade: grade, notes: notes })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      app.toast(data.message || 'Stock transferred!');
      setTimeout(() => location.reload(), 1000);
    } else {
      app.toast(data.message || 'Failed to transfer stock', 'error');
      btn.disabled = false;
      btn.innerHTML = 'Transfer to Semi';
    }
  })
  .catch(err => {
    app.toast('Network error: ' + err.message, 'error');
    btn.disabled = false;
    btn.innerHTML = 'Transfer to Semi';
  });
}
</script>
@endif

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('semi-storage-location');
    if(select) {
      const locs = app.storageLocations || ['Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
      select.innerHTML = locs.map(l => `<option value="${l}">${l}</option>`).join('');
    }
    window.currentAvailableInputStock = @json($pageData['rawStock']);
    if(document.getElementById('input-rows') && document.getElementById('input-rows').children.length === 0) {
      app.addInputRow();
    }
  });

  function toggleSemiRefFields() {
    const type = document.getElementById('semi-ref-type').value;
    document.getElementById('semi-po-group').style.display = type === 'PO' ? 'block' : 'none';
    document.getElementById('semi-other-group').style.display = type === 'Other' ? 'block' : 'none';
  }
</script>
@endsection
