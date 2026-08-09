@extends('layouts.app')

@section('content')
@php
  $tab = request('tab', 'production');
@endphp
<div class="tabs" style="margin-bottom:1rem;">
  <a class="tab-btn {{ $tab==='production'?'active':'' }}" href="?tab=production" style="text-decoration:none;">Convert to FG</a>
  <a class="tab-btn {{ $tab==='transfer'?'active':'' }}" href="?tab=transfer" style="text-decoration:none;">Transfer Raw Stock</a>
</div>

@if($tab === 'production')
<div class="card">
  <div class="card-title">Convert to FG</div>
  
  <div class="form-group">
    <label>Target Product</label>
    <select id="prod-output" onchange="onTargetProductSelected()">
      <option value="" disabled selected>-- Select Product --</option>
      @foreach(collect($pageData['products'])->filter(fn($p) => $p['type'] !== 'RAW') as $p)
        <option value="{{ $p['id'] }}">{{ $p['name'] }} - (grade- N/A) (type - {{ strtolower($p['type']) === 'finished' ? 'fg' : strtolower($p['type']) }})</option>
      @endforeach
    </select>
  </div>

  <div class="form-group hidden" id="grade-selection-group">
    <label>Select Grade</label>
    <select id="prod-grade" onchange="onGradeSelected()">
      <!-- Grades injected dynamically -->
    </select>
  </div>
  
  <div id="materials-section" class="hidden" style="margin-top: 2rem; border-top: 1px dashed var(--glass-border); padding-top: 1.5rem;">
    <div class="form-group">
      <label>Expected Output Quantity (kg)</label>
      <input type="number" id="prod-out-qty" placeholder="Quantity produced" step="0.001">
    </div>

    <div class="form-group">
      <label>Notes (Optional)</label>
      <input type="text" id="finish-notes" placeholder="Enter notes here..." style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>

    <div class="flex-between mb-1 mt-1">
      <label style="margin:0; font-size:1rem; color:var(--primary-light);">Add Material (Consumed)</label>
      <button class="btn btn-sm btn-secondary" onclick="addInputRow()" style="width:auto;">+ Add Material</button>
    </div>
    
    <div id="input-rows" style="display:flex; flex-direction:column; gap:10px;">
      <!-- Rows injected here -->
    </div>
    
    <div class="form-group mt-1">
      <label>Storage Location</label>
      <select id="finished-storage-location" style="padding:0.7rem;">
        @forelse($pageData['locations'] as $loc)
          <option value="{{ $loc }}">{{ $loc }}</option>
        @empty
          <option value="Warehouse A">Warehouse A</option>
          <option value="Warehouse B">Warehouse B</option>
          <option value="Rack 1">Rack 1</option>
          <option value="Cold Room">Cold Room</option>
        @endforelse
      </select>
    </div>
    
    <button class="btn mt-2" onclick="reviewFinishedProduction()">
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

  <form id="finished-transfer-form" onsubmit="submitTransfer(event)">
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

  fetch('{{ route("finished.transfer_to_semi") }}', {
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
  // Dynamic grades data from PHP
  window.productsList = @json($pageData['products']);
  window.availableInputStock = @json(array_merge($pageData['rawStock'], $pageData['semiStock']));

  function onTargetProductSelected() {
    const productId = document.getElementById('prod-output').value;
    const p = window.productsList.find(x => x.id == productId);
    
    const gradeSelect = document.getElementById('prod-grade');
    const gradeGroup = document.getElementById('grade-selection-group');
    
    if (p && p.gradeNames && p.gradeNames.length > 0) {
      gradeSelect.innerHTML = `<option value="" disabled selected>-- Select Grade --</option>` + 
        p.gradeNames.map(g => `<option value="${g}">${g}</option>`).join('') + 
        (p.gradeNames.includes('N/A') ? '' : `<option value="N/A">N/A</option>`);
      gradeGroup.classList.remove('hidden');
      document.getElementById('materials-section').classList.add('hidden');
    } else {
      gradeGroup.classList.add('hidden');
      document.getElementById('materials-section').classList.remove('hidden');
      if(document.getElementById('input-rows').children.length === 0) {
        addInputRow();
      }
    }
  }

  function onGradeSelected() {
    document.getElementById('materials-section').classList.remove('hidden');
    if(document.getElementById('input-rows').children.length === 0) {
      addInputRow();
    }
  }

  function addInputRow() {
    // In finished action, we can consume both RAW and SEMI.
    const items = window.availableInputStock || [];
    
    const div = document.createElement('div');
    div.className = 'dynamic-row';
    div.style.display = 'flex';
    div.style.flexDirection = 'column';
    div.style.gap = '12px';
    div.style.alignItems = 'stretch';
    div.style.position = 'relative';
    div.style.background = 'rgba(255,255,255,0.05)';
    div.style.padding = '12px';
    div.style.borderRadius = '12px';
    
    div.innerHTML = `
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Material</label>
        <select class="prod-in-id" onchange="validateRowStock(this)" style="padding:0.75rem;">
          <option value="" disabled selected>Select Material</option>
          ${items.map(s => `<option value="${s.id}|${s.grade}|${s.stage}" data-max="${s.quantity}">${s.name} (${s.stage} - ${s.grade}) &mdash; Available: ${parseFloat(s.quantity).toFixed(2)} ${s.unit}</option>`).join('')}
        </select>
        <div class="stock-hint" style="font-size:0.75rem; color:var(--secondary); margin-top:4px; font-weight:600; min-height:12px;"></div>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label style="font-size:0.75rem; margin-bottom:4px;">Qty (kg)</label>
        <input type="number" class="prod-in-qty" placeholder="Enter quantity" style="padding:0.75rem;" step="0.001">
      </div>
      <button class="btn btn-danger btn-sm" style="position:absolute; top:8px; right:8px; width:32px; height:32px; padding:0; border-radius:50%; display:flex; align-items:center; justify-content:center;" onclick="this.parentElement.remove()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    `;
    document.getElementById('input-rows').appendChild(div);
  }

  function validateRowStock(selectEl) {
    const option = selectEl.options[selectEl.selectedIndex];
    const available = Number(option.dataset.max) || 0;
    
    const hint = selectEl.parentElement.querySelector('.stock-hint');
    if (hint) {
      hint.innerText = available > 0 ? `✓ ${available.toFixed(2)} kg available` : '⚠ No stock available';
      if(available === 0) hint.style.color = 'var(--danger)';
      else hint.style.color = 'var(--secondary)';
    }
  }

  function reviewFinishedProduction() {
    const outProdId = document.getElementById('prod-output').value;
    const gradeEl = document.getElementById('prod-grade');
    const gradeGroup = document.getElementById('grade-selection-group');
    const gradeHidden = gradeGroup && gradeGroup.classList.contains('hidden');
    const outGrade = gradeHidden ? 'N/A' : (gradeEl ? gradeEl.value : 'N/A');
    const outQty = Number(document.getElementById('prod-out-qty').value);
    const notes = document.getElementById('finish-notes').value;
    const loc = document.getElementById('finished-storage-location').value;

    if (!outProdId) return app.toast('Select target product', 'error');
    if (!gradeHidden && !outGrade) return app.toast('Select grade', 'error');
    if (!outQty || outQty <= 0) return app.toast('Enter valid output quantity', 'error');

    const inputs = [];
    let validationFailed = false;

    document.querySelectorAll('#input-rows .dynamic-row').forEach(row => {
      const selectEl = row.querySelector('.prod-in-id');
      const val = selectEl.value;
      const qty = Number(row.querySelector('.prod-in-qty').value);
      
      if (val && qty > 0) {
        const [id, grade, stage] = val.split('|');
        const option = selectEl.options[selectEl.selectedIndex];
        const available = Number(option.dataset.max) || 0;
        
        if (qty > available) {
          const pName = option.text;
          app.toast(`Not enough stock for ${pName}. Max: ${available}`, 'error');
          validationFailed = true;
        }
        const rawName = option.text.split('—')[0].split('(')[0].trim();
        inputs.push({ productId: id, grade: grade, stage: stage, quantity: qty, name: rawName });
      }
    });

    if (validationFailed) return;
    if (inputs.length === 0) return app.toast('Add at least one consumed material', 'error');

    window.tempFinishedProductionData = { outProdId, outGrade, outQty, notes, loc, inputs };
    const outProdName = window.productsList.find(x => x.id == outProdId)?.name;
    
    app.openDrawer(`
      <h3 style="margin-bottom:1rem; color:var(--secondary);">Review FG Production</h3>
      <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:10px; margin-bottom:1rem; border:1px solid var(--glass-border);">
        <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">Target Output</div>
        <div style="font-weight:700; font-size:1.1rem; color:var(--text-main);">${outQty} kg of ${outProdName} - (grade- ${outGrade || 'N/A'}) (type - fg)</div>
      </div>
      
      <div style="font-size:0.9rem; font-weight:600; margin-bottom:0.8rem; color:var(--primary-light);">Consumed Materials:</div>
      <ul style="list-style:none; padding:0; margin:0 0 1.5rem 0;">
        ${inputs.map(inp => `
          <li style="display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem;">
            <span>${inp.name} <span style="font-size:0.75rem; color:var(--text-muted);">(${inp.stage} - ${inp.grade})</span></span>
            <span style="font-weight:600; color:var(--danger);">- ${inp.quantity} kg</span>
          </li>
        `).join('')}
      </ul>
      
      <div style="display:flex; gap:10px;">
        <button class="btn btn-secondary" style="flex:1;" onclick="app.closeDrawer()">Cancel</button>
        <button class="btn" style="flex:2;" onclick="confirmFinishedProduction(this)">Confirm & Process</button>
      </div>
    `);
  }

  function confirmFinishedProduction(btn) {
    const data = window.tempFinishedProductionData;
    if (!data) return;

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Processing...`;
    }

    const payload = {
      output_product_id: data.outProdId,
      output_grade:      data.outGrade,
      output_qty:        data.outQty,
      location:          data.loc,
      notes:             data.notes,
      inputs: data.inputs.map(inp => ({
        product_id: inp.productId,
        grade:      inp.grade,
        stage:      inp.stage,
        quantity:   inp.quantity
      }))
    };

    fetch('/finished/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        app.toast(res.message || 'FG Production logged successfully!');
        window.tempFinishedProductionData = null;
        app.closeDrawer();
        document.getElementById('prod-out-qty').value = '';
        document.getElementById('finish-notes').value = '';
        document.querySelectorAll('.prod-in-qty').forEach(el => el.value = '');
        if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
        setTimeout(() => window.location.href = '{{ route('finished.home') }}', 1000);
      } else {
        app.toast(res.message || 'Error logging production', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
      }
    })
    .catch(() => {
      app.toast('Network error. Try again.', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = `Confirm Production`; }
    });
  }
</script>
@endsection
