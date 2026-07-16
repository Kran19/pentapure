@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'inward');
@endphp
<div class="tabs" style="margin-bottom:1rem;">
  <a class="tab-btn {{ $tab==='inward'?'active':'' }}" href="?tab=inward" style="text-decoration:none;">Inward Raw</a>
  <a class="tab-btn {{ $tab==='transfer'?'active':'' }}" href="?tab=transfer" style="text-decoration:none;">Transfer to Semi</a>
</div>

<div class="card">
  @if($tab === 'inward')
  <div class="card-title">🌿 Inward Raw Material</div>
  
  <div class="form-group" style="margin-bottom:0.8rem;">
    <input type="text" id="raw-search" placeholder="🔍 Search product..." oninput="filterMaterialsList(this.value)" style="padding:0.6rem 0.8rem; font-size:0.85rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
  
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); margin-bottom:1rem; max-height:250px; overflow-y:auto; padding:5px;">
    @foreach($pageData['rawMaterialsList'] as $rm)
      <div class="rm-card" data-id="{{ $rm['id'] }}" data-name="{{ strtolower($rm['name']) }}" onclick="selectRawMaterial('{{ $rm['id'] }}', '{{ addslashes($rm['name']) }}', this)" 
        style="border:2px solid transparent; border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.05); text-align:center; padding:12px 6px; transition:0.2s;">
        <div style="font-size:0.85rem; font-weight:600; padding:4px 3px; line-height:1.2;">{{ $rm['name'] }}</div>
      </div>
    @endforeach
  </div>
  
  <form id="raw-inward-form" onsubmit="submitRawInward(event)">
    <input type="hidden" id="raw-prod" name="product_id" value="">
    <div id="raw-selected-name" style="font-size:0.9rem; font-weight:bold; color:var(--primary-light); margin-bottom:0.8rem; min-height:1.2em;"></div>
    
    <div class="form-group" style="margin-bottom:1rem;">
      <label style="font-weight:600;">Quantity (kg)</label>
      <input type="number" name="quantity" id="raw-qty" step="0.001" min="0.001" placeholder="Enter inward quantity" required style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>
    
    <div class="form-group" style="margin-bottom:1.5rem;">
      <label style="font-weight:600;">Storage Location</label>
      <select id="raw-storage-location" name="location" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </select>
    </div>

    <div class="form-group" style="margin-bottom:1.5rem;">
      <label style="font-weight:600;">Notes</label>
      <textarea id="raw-notes" name="notes" placeholder="Enter notes (optional)..." style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff; height:70px; resize:vertical;"></textarea>
    </div>
    
    <button type="submit" class="btn mt-1" id="raw-submit-btn">Add to Stock</button>
  </form>
  @endif

  @if($tab === 'transfer')
  <div class="card-title">➡️ Transfer to Semi-Finished</div>
  
  <div class="responsive-grid" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); margin-bottom:1rem; max-height:250px; overflow-y:auto; padding:5px;">
    @foreach($pageData['rawStock'] as $rs)
      <div class="rs-card" onclick="selectTransferMaterial('{{ $rs['productId'] }}', '{{ addslashes($rs['name']) }}', '{{ addslashes($rs['grade']) }}', {{ $rs['quantity'] }}, this)" 
        style="border:2px solid transparent; border-radius:10px; overflow:hidden; cursor:pointer; background:rgba(255,255,255,0.05); text-align:center; padding:12px 6px; transition:0.2s;">
        <div style="font-size:0.85rem; font-weight:600; padding:4px 3px; line-height:1.2;">{{ $rs['name'] }} <small>({{ $rs['grade'] }})</small></div>
        <div style="font-size:0.75rem; color:var(--primary-light);">Avail: {{ number_format($rs['quantity'], 2) }} {{ $rs['unit'] }}</div>
      </div>
    @endforeach
  </div>

  <form id="raw-transfer-form" onsubmit="submitTransfer(event)">
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
  @endif
</div>

<script>
function filterMaterialsList(q) {
  const query = q.trim().toLowerCase();
  document.querySelectorAll('.rm-card').forEach(card => {
    const name = card.getAttribute('data-name');
    if (name.includes(query)) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

function selectRawMaterial(id, name, el) {
  document.querySelectorAll('.rm-card').forEach(c => c.style.borderColor = 'transparent');
  el.style.borderColor = 'var(--primary)';
  document.getElementById('raw-prod').value = id;
  document.getElementById('raw-selected-name').innerText = 'Selected: ' + name + ' - (grade- N/A) (type - raw)';
}

function submitRawInward(e) {
  e.preventDefault();
  const prodId = document.getElementById('raw-prod').value;
  const qty = Number(document.getElementById('raw-qty').value);
  const loc = document.getElementById('raw-storage-location').value;
  const notes = document.getElementById('raw-notes').value;
  const btn = document.getElementById('raw-submit-btn');

  if (!prodId) {
    app.toast('Please select a material from the grid', 'error');
    return;
  }
  if (!qty || qty <= 0) {
    app.toast('Enter a valid quantity', 'error');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align: middle; margin-right:5px;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Adding...`;

  fetch('/raw/action', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json', 
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken 
    },
    body: JSON.stringify({ product_id: prodId, quantity: qty, grade: 'NONE', location: loc, notes: notes })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      app.toast(data.message || 'Stock added!');
      setTimeout(() => location.reload(), 1000);
    } else {
      app.toast(data.message || 'Failed to add stock', 'error');
    }
  })
  .catch(err => app.toast('Network error: ' + err.message, 'error'))
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = 'Add to Stock';
  });
}

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
  btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align: middle; margin-right:5px;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Transferring...`;

  fetch('/raw/transfer-to-semi', {
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

document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('raw-storage-location');
  if(select) {
    const locs = app.storageLocations || ['Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
    select.innerHTML = locs.map(l => `<option value="${l}">${l}</option>`).join('');
  }
});
</script>
@endsection
