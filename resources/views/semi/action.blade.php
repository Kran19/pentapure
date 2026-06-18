@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">Create Semi Production Order</div>
  
  <div class="form-group">
    <label>Target Product</label>
    <select id="prod-output" onchange="app.onTargetProductSelected()">
      <option value="" disabled selected>-- Select Product --</option>
      @foreach($pageData['products'] as $p)
        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
      @endforeach
    </select>
  </div>

  <div class="form-group hidden" id="grade-selection-group">
    <label>Select Grade</label>
    <select id="prod-grade" onchange="app.onGradeSelected()">
      <!-- Grades injected dynamically by JS -->
    </select>
  </div>
  
  <div id="materials-section" class="hidden" style="margin-top: 2rem; border-top: 1px dashed var(--glass-border); padding-top: 1.5rem;">
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
    
    <button class="btn mt-2" onclick="app.reviewProduction('RAW', 'SEMI', 'rawStock')">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
      Review Production
    </button>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('semi-storage-location');
    const locs = app.storageLocations || ['Warehouse A', 'Warehouse B', 'Rack 1', 'Cold Room'];
    select.innerHTML = locs.map(l => `<option value="${l}">${l}</option>`).join('');
    
    // Inject stock data for app.addInputRow() to consume
    window.currentAvailableInputStock = @json($pageData['rawStock']);
  });
</script>
@endsection
