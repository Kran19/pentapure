@extends('layouts.app')

@section('content')
<div class="tabs" style="margin-bottom:1rem;">
  <div class="tab-btn active" onclick="switchSalesTab('order', this)">
    {{ !empty($pageData['editOrder']) ? 'Edit Order' : 'Create Order' }}
  </div>
  <div class="tab-btn" onclick="switchSalesTab('company', this)">Company</div>
  <div class="tab-btn" onclick="switchSalesTab('transport', this)">Transport</div>
</div>

<div id="sales-form-container">
  <!-- Tab 1: Create Order -->
  <div id="sales-tab-order" class="sales-tab-content animation-fadeIn">
    <div class="card">
      @if(!empty($pageData['editOrder']))
        <input type="hidden" id="edit-order-id" value="{{ $pageData['editOrder']->id }}">
      @endif

      <div class="form-group">
        <label>Customer Company</label>
        <select id="order-company" onchange="app.onSalesCompanySelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>-- Select Company --</option>
          @foreach($pageData['companies'] as $c)
            <option value="{{ $c['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->company_id == $c['id']) ? 'selected' : '' }}>{{ $c['name'] }}</option>
          @endforeach
        </select>
        <div id="company-details" style="font-size:0.8rem; color:var(--text-muted); margin-top:8px; display:none; background:rgba(0,0,0,0.2); padding:8px; border-radius:6px;"></div>
      </div>

      <div class="form-group">
        <label>Order Type</label>
        <select id="order-type" onchange="app.onOrderTypeSelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>-- Select Type --</option>
          <option value="ALL" {{ !empty($pageData['editOrder']) ? 'selected' : '' }}>All Products (Universal)</option>
          <option value="RAW">Raw Material Sales</option>
          <option value="SEMI">Semi-Finished Sales</option>
          <option value="FINISHED">FG Sales</option>
        </select>
      </div>
      
      <div id="order-products-section" style="{{ !empty($pageData['editOrder']) ? 'display:block;' : 'display:none;' }}">
        <label style="display:block; margin-top:1.5rem; font-size:0.85rem; color:var(--text-muted); margin-bottom:0.4rem;">Products</label>
        <div id="order-products" style="display:flex; flex-direction:column; gap:10px; margin-bottom:10px;"></div>
        <button class="btn btn-sm btn-secondary mb-1" onclick="app.addOrderProductRow()" style="padding:0.6rem; width:auto;">+ Add Product</button>
      </div>
      
      <div class="form-group mt-1">
        <label>Transport Partner</label>
        <select id="order-transport" onchange="app.onSalesTransportSelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>-- Select Transport --</option>
          @foreach($pageData['transportCompanies'] as $t)
            <option value="{{ $t['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->transporter_id == $t['id']) ? 'selected' : '' }}>{{ $t['name'] }}</option>
          @endforeach
        </select>
        <div id="transport-details" style="font-size:0.8rem; color:var(--text-muted); margin-top:8px; display:none; background:rgba(0,0,0,0.2); padding:8px; border-radius:6px;"></div>
      </div>
      
      <div class="form-group">
        <label>Notes / Instructions</label>
        <textarea id="order-notes" rows="2" placeholder="Optional notes..." style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">{{ !empty($pageData['editOrder']) ? $pageData['editOrder']->notes : '' }}</textarea>
      </div>
      
      <button class="btn mt-1" onclick="app.submitOrder()" style="padding:1rem; font-size:1.1rem;">
        {{ !empty($pageData['editOrder']) ? 'Update Order' : 'Generate Order' }}
      </button>
    </div>
  </div>

  <!-- Tab 2: Company -->
  <div id="sales-tab-company" class="sales-tab-content animation-fadeIn" style="display:none;">
    <div class="card">
      @if(!empty($pageData['editCompany']))
        <input type="hidden" id="edit-comp-id" value="{{ $pageData['editCompany']->id }}">
      @endif
      <div class="form-group">
        <label>Company Name</label>
        <input type="text" id="comp-name" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->name : '' }}" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <div class="form-group">
        <label>GST No.</label>
        <input type="text" id="comp-gst" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->gst : '' }}" placeholder="e.g. 22AAAAA0000A1Z5 or N/A" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea id="comp-address" rows="2" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->address : '' }}</textarea>
      </div>
      <div class="form-group">
        <label>Mobile No</label>
        <input type="text" id="comp-contact" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->contact : '' }}" placeholder="10-digit mobile" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <button class="btn" onclick="app.submitCompany()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">{{ !empty($pageData['editCompany']) ? 'Update Company' : 'Save Company' }}</button>
    </div>
  </div>

  <!-- Tab 3: Transport -->
  <div id="sales-tab-transport" class="sales-tab-content animation-fadeIn" style="display:none;">
    <div class="card">
      <div class="form-group">
        <label>Transporter Name</label>
        <input type="text" id="trans-name" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <div class="form-group">
        <label>GST No.</label>
        <input type="text" id="trans-gst" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <div class="form-group">
        <label>Driver Mobile No</label>
        <input type="text" id="trans-contact" placeholder="10-digit mobile" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <div class="form-group">
        <label>Vehicle No.</label>
        <input type="text" id="trans-vehicles" placeholder="e.g. MH 12 AB 1234" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
      </div>
      <button class="btn" onclick="app.submitTransport()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">Save Transport</button>
    </div>
  </div>
</div>

<script>
  function switchSalesTab(tab, btnEl) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    btnEl.classList.add('active');

    document.querySelectorAll('.sales-tab-content').forEach(content => content.style.display = 'none');
    document.getElementById('sales-tab-' + tab).style.display = 'block';
  }
</script>

@if(!empty($pageData['editOrder']))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Fill details
    const compVal = "{{ $pageData['editOrder']->company_id }}";
    const transVal = "{{ $pageData['editOrder']->transporter_id }}";
    if (compVal) {
      document.getElementById('order-company').value = compVal;
      app.onSalesCompanySelect(compVal);
    }
    if (transVal) {
      document.getElementById('order-transport').value = transVal;
      app.onSalesTransportSelect(transVal);
    }

    // Populate rows
    window.currentOrderType = 'ALL';
    window.currentFinProds = (window.serverPageData && window.serverPageData.products) || [];
    
    const prodList = document.getElementById('order-products');
    if (prodList) {
      prodList.innerHTML = '';
      const editOrder = (window.serverPageData && window.serverPageData.editOrder) || {};
      if (editOrder && editOrder.items) {
        editOrder.items.forEach(item => {
          app.addOrderProductRow(item);
        });
      }
    }
  });
</script>
@endif

@if(!empty($pageData['editCompany']))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.tab-btn:nth-child(2)');
    if (btn) switchSalesTab('company', btn);
  });
</script>
@endif
@endsection
