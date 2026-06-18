@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">Convert Semi to Finished Goods</div>
  
  <div class="form-group">
    <label>Select Available Semi Stock</label>
    <select id="semi-stock-select" onchange="updateSemiMaxHint()">
      <option value="" disabled selected>-- Select Semi Stock --</option>
      @foreach($pageData['semiStock'] as $s)
        <option value="{{ $s['productId'] }}|{{ $s['grade'] }}" data-max="{{ $s['quantity'] }}" data-name="{{ $s['name'] }}">
          {{ $s['name'] }} (Grade: {{ $s['grade'] }}) - {{ number_format($s['quantity'], 2) }} {{ $s['unit'] }} available
        </option>
      @endforeach
    </select>
    <div id="semi-max-hint" style="font-size:0.8rem; color:var(--secondary); margin-top:4px;"></div>
  </div>

  <div class="form-group">
    <label>Quantity to Convert</label>
    <input type="number" id="finish-qty" placeholder="Enter quantity" step="0.01">
  </div>
  
  <div class="form-group">
    <label>Notes (Optional)</label>
    <input type="text" id="finish-notes" placeholder="Enter notes here...">
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
  
  <button class="btn mt-2" onclick="submitFinishAction(this)">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;"><polyline points="20 6 9 17 4 12"></polyline></svg>
    Mark as Finished
  </button>
</div>

<script>

  function updateSemiMaxHint() {
    const select = document.getElementById('semi-stock-select');
    const option = select.options[select.selectedIndex];
    const max = option.dataset.max;
    if(max) {
      document.getElementById('semi-max-hint').innerText = 'Max Available: ' + max;
    } else {
      document.getElementById('semi-max-hint').innerText = '';
    }
  }

  function submitFinishAction(btn) {
    const select = document.getElementById('semi-stock-select');
    if(!select.value) return app.toast('Please select a semi stock', 'error');

    const [productId, grade] = select.value.split('|');
    const qty = Number(document.getElementById('finish-qty').value);
    const max = Number(select.options[select.selectedIndex].dataset.max);

    if(!qty || qty <= 0) return app.toast('Enter valid quantity', 'error');
    if(qty > max) return app.toast('Quantity exceeds available stock', 'error');

    const notes = document.getElementById('finish-notes').value;
    const location = document.getElementById('finished-storage-location').value;

    const payload = {
      output_product_id: productId,
      output_grade: grade,
      output_qty: qty,
      location: location,
      notes: notes,
      inputs: [
        {
          product_id: productId,
          grade: grade,
          quantity: qty
        }
      ]
    };

    btn.disabled = true;
    btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin" style="vertical-align:middle;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg> Processing...`;

    fetch('/finished/action', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        app.toast(res.message || 'Finished Production logged successfully!');
        setTimeout(() => window.location.href = '{{ route('finished.home') }}', 1000);
      } else {
        app.toast(res.message || 'Error logging production', 'error');
        btn.disabled = false;
        btn.innerHTML = `Mark as Finished`;
      }
    })
    .catch(() => {
      app.toast('Network error. Try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = `Mark as Finished`;
    });
  }
</script>
@endsection
