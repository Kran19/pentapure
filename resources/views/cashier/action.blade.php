@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">New Transaction</div>
  
  <div class="tabs" style="margin-bottom:1.2rem;">
    <div class="tab-btn active" id="btn-tx-in" onclick="switchTxType('IN')">INCOME (IN)</div>
    <div class="tab-btn" id="btn-tx-out" onclick="switchTxType('OUT')">EXPENSE (OUT)</div>
  </div>
  
  <div class="form-group mt-1">
    <label>Category</label>
    <div style="display:flex; gap:8px; align-items:stretch;">
      <select id="tx-category" style="flex:1; padding:0.7rem; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
        @foreach($pageData['categories'] as $c)
          <option value="{{ $c['value'] }}">{{ $c['label'] }}</option>
        @endforeach
      </select>
      <button class="btn btn-secondary" onclick="app.addNewExpenseCategory()" style="width:auto; padding:0.5rem 1rem; white-space:nowrap; font-size:0.8rem;">+ New</button>
    </div>
  </div>
  
  <div class="form-group">
    <label>Amount (₹)</label>
    <input type="number" id="tx-amount" placeholder="0.00" step="0.01" min="0.01" required style="font-size:1.3rem; padding:0.8rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
  <div class="form-group">
    <label>Particulars / Note</label>
    <input type="text" id="tx-note" placeholder="Description of transaction" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
  <div class="form-group">
    <label>Reference / Bill No. (optional)</label>
    <input type="text" id="tx-ref" placeholder="e.g. INV-2024-001" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
  </div>
  <div class="form-group" style="margin-bottom:1.5rem;">
    <label>Attach Bill (optional)</label>
    <input type="file" id="tx-bill" accept="image/jpeg,image/png,application/pdf" style="font-size:0.9rem; padding:0.5rem; background:#0d1117; border:1px dashed #30363d; border-radius:6px; width:100%; color:#8b949e;">
  </div>
  <button class="btn mt-1" onclick="app.submitTransaction()">Save Transaction</button>
</div>

<script>
  window.txType = 'IN';

  function switchTxType(type) {
    window.txType = type;
    document.querySelectorAll('.tabs .tab-btn').forEach(btn => btn.classList.remove('active'));
    if (type === 'IN') {
      document.getElementById('btn-tx-in').classList.add('active');
    } else {
      document.getElementById('btn-tx-out').classList.add('active');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Populate categories globally so new expense category popups work
    window.serverPageData = window.serverPageData || {};
    window.serverPageData.categories = @json($pageData['categories'] ?? []);
  });
</script>
@endsection
