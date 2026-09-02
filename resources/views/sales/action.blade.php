@extends('layouts.app')

@section('content')
<style>
/* Remove number input spinner arrows (increase/decrease) */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button,
.no-spinners::-webkit-outer-spin-button,
.no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}

input[type="number"],
.no-spinners {
  -moz-appearance: textfield !important;
}

/* Full Width Order Product Rows */
#order-products {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.order-product-row {
  width: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
  background: transparent !important;
  padding: 0 !important;
  border: none !important;
  margin: 0 !important;
}

.order-product-row select.o-prod-id,
.order-product-row select.o-prod-grade {
  width: 100% !important;
  display: block !important;
  padding: 0.75rem !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  font-size: 0.9rem !important;
  box-sizing: border-box !important;
}

.order-product-row .order-product-inputs {
  width: 100% !important;
  display: flex !important;
  gap: 10px !important;
  align-items: center !important;
}

.order-product-row input.o-prod-qty,
.order-product-row input.o-prod-price {
  width: 100% !important;
  padding: 0.75rem !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  font-size: 0.9rem !important;
  box-sizing: border-box !important;
}

.btn-add-product {
  display: inline-flex !important;
  align-items: center !important;
  padding: 0.45rem 1rem !important;
  font-size: 0.85rem !important;
  font-weight: 600 !important;
  border-radius: 8px !important;
  border: 1px solid var(--border-soft, #DDCFAF) !important;
  background: var(--input-bg, transparent) !important;
  color: var(--text-main, #333) !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}

.btn-add-product:hover {
  background: var(--primary, #F4B400) !important;
  color: #000 !important;
}

.order-product-row .btn-remove-prod {
  flex: 0 0 42px !important;
  width: 42px !important;
  height: 42px !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 8px !important;
  background: #e11d48 !important;
  color: #fff !important;
  border: none !important;
  cursor: pointer !important;
}
.info-preview-box {
  margin-top: 8px;
  padding: 0.8rem 1rem;
  background: #bdbdbd !important;
  color: #1a1a1a !important;
  border-radius: 6px;
  font-size: 0.85rem;
  line-height: 1.5;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.info-preview-box .info-label {
  color: #784d00 !important;
  font-weight: 700 !important;
  margin-right: 4px;
}

html.dark-mode .info-preview-box {
  background: #334155 !important;
  color: #f8fafc !important;
  border: 1px solid #475569;
}

html.dark-mode .info-preview-box .info-label {
  color: #fbbf24 !important;
}
</style>
<div class="tabs">
  <button class="tab-btn active" onclick="switchSalesTab('order', this)">Create Order</button>
  <button class="tab-btn" onclick="switchSalesTab('company', this)">Company</button>
  <button class="tab-btn" onclick="switchSalesTab('transport', this)">Transport</button>
</div>

<div style="margin-top:1.5rem;">
  <!-- Tab 1: Create Order -->
  <div id="sales-tab-order" class="sales-tab-content animation-fadeIn" style="display:block;">
    <div class="card">
      <div class="card-title">📦 New Sales Order</div>
      
      @if(!empty($pageData['editOrder']))
        <input type="hidden" id="edit-order-id" value="{{ $pageData['editOrder']->id }}">
        <div style="margin-bottom:1rem; padding:0.5rem 1rem; background:rgba(255,165,0,0.15); border-left:4px solid var(--warning); border-radius:4px; font-size:0.9rem;">
          ✏️ Editing Order #<strong>{{ $pageData['editOrder']->id }}</strong>
        </div>
      @endif

      <div class="form-group">
        <label>Select Company *</label>
        <select id="order-company" onchange="app.onSalesCompanySelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>Choose Registered Company</option>
          @foreach($pageData['companies'] as $c)
            <option value="{{ $c['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->company_id == $c['id']) ? 'selected' : '' }}>
              {{ $c['name'] }} {{ $c['gst'] ? '('.$c['gst'].')' : '' }}
            </option>
          @endforeach
        </select>
        <div id="company-details" class="info-preview-box" style="display:none;"></div>
      </div>

      <div class="form-group">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
          <label style="margin-bottom:0;">Select Transport *</label>
          <button type="button" class="btn btn-sm" onclick="app.openAddTransportModal()" style="padding:0.35rem 0.8rem; font-size:0.78rem; font-weight:700; width:auto; border-radius:6px; letter-spacing:0.3px;">+ ADD TRANSPORT</button>
        </div>
        <select id="order-transport" onchange="app.onSalesTransportSelect(this.value)">
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>Choose Transporter</option>
          @foreach($pageData['transportCompanies'] as $t)
            <option value="{{ $t['id'] }}" {{ (!empty($pageData['editOrder']) && $pageData['editOrder']->transporter_id == $t['id']) ? 'selected' : '' }}>
              {{ $t['name'] }}
            </option>
          @endforeach
        </select>
        <div id="transport-details" class="info-preview-box" style="display:none;"></div>
      </div>

      <!-- Product Items -->
      <div style="margin-top:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:8px;">
          <h4 style="margin:0;">Products / Goods</h4>
          <button type="button" class="btn btn-sm" onclick="app.addOrderProductRow()" style="width:auto; padding:0.4rem 0.8rem; font-weight:bold;">+ Add Product</button>
        </div>

        <div id="order-products"></div>
      </div>

      <div class="form-group" style="margin-top:1.5rem;">
        <label>Notes / Special Instructions</label>
        <textarea id="order-notes" rows="2" placeholder="e.g. Deliver before 5 PM">{{ !empty($pageData['editOrder']) ? $pageData['editOrder']->notes : '' }}</textarea>
      </div>

      <button class="btn" onclick="app.submitOrder()" style="padding:1rem; font-size:1.1rem; margin-top:1rem;">
        {{ !empty($pageData['editOrder']) ? 'Update Sales Order' : 'Generate Sales Order' }}
      </button>
    </div>
  </div>

  <!-- Tab 2: Company -->
  <div id="sales-tab-company" class="sales-tab-content animation-fadeIn" style="display:none;">
    <div class="card" style="margin-bottom:1.5rem;">
      @if(!empty($pageData['editCompany']))
        <input type="hidden" id="edit-comp-id" value="{{ $pageData['editCompany']->id }}">
        <div style="margin-bottom:1rem; padding:0.5rem 1rem; background:rgba(255,165,0,0.15); border-left:4px solid var(--warning); border-radius:4px; font-size:0.9rem;">
          ✏️ Editing Company: <strong>{{ $pageData['editCompany']->name }}</strong>
        </div>
      @endif
      <div class="form-group">
        <label>Company Name *</label>
        <input type="text" id="comp-name" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->name : '' }}" placeholder="e.g. ABC Chemical Industries" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="form-group">
        <label>Company Type *</label>
        <select id="comp-type" onchange="app.onCompanyTypeChange(this.value)" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
          <option value="registered" {{ (!empty($pageData['editCompany']) && $pageData['editCompany']->gst && strtoupper($pageData['editCompany']->gst) !== 'N/A') ? 'selected' : (empty($pageData['editCompany']) ? 'selected' : '') }}>Registered Company</option>
          <option value="unregistered" {{ (!empty($pageData['editCompany']) && (!$pageData['editCompany']->gst || strtoupper($pageData['editCompany']->gst) === 'N/A')) ? 'selected' : '' }}>Un-Registered Company</option>
        </select>
      </div>
      <div class="form-group" id="comp-gst-group" style="{{ (!empty($pageData['editCompany']) && (!$pageData['editCompany']->gst || strtoupper($pageData['editCompany']->gst) === 'N/A')) ? 'display:none;' : 'display:block;' }}">
        <label>GST No. *</label>
        <input type="text" id="comp-gst" maxlength="15" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->gst : '' }}" placeholder="15-digit GST (e.g. 22AAAAA0000A1Z5)" style="text-transform:uppercase; padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea id="comp-address" rows="2" placeholder="Full factory / office address" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->address : '' }}</textarea>
      </div>
      <div class="form-group">
        <label>Pincode</label>
        <input type="text" id="comp-pincode" maxlength="6" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->pincode : '' }}" placeholder="6-digit Pincode (e.g. 380001)" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="form-group">
        <label>Contact / Mobile No</label>
        <div style="display:flex; gap:8px;">
          <select id="comp-country-code" onchange="app.onCountryCodeChange('comp')" style="width:68px; padding:0.7rem 0.2rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; flex-shrink:0; text-align:center; cursor:pointer;">
            <option value="+91" selected>+91</option>
            <option value="+1">+1</option>
            <option value="+44">+44</option>
            <option value="+971">+971</option>
            <option value="+966">+966</option>
            <option value="+61">+61</option>
            <option value="+65">+65</option>
            <option value="+49">+49</option>
            <option value="+33">+33</option>
            <option value="+86">+86</option>
            <option value="+81">+81</option>
            <option value="other">+...</option>
          </select>
          <input type="text" id="comp-contact" value="{{ !empty($pageData['editCompany']) ? $pageData['editCompany']->contact : '' }}" placeholder="10-digit mobile or 079 landline" style="flex:1; padding:0.7rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
        </div>
      </div>
      <button class="btn" onclick="app.submitCompany()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">{{ !empty($pageData['editCompany']) ? 'Update Company' : 'Save Company' }}</button>
    </div>

    <!-- Registered Companies List Table Below Form -->
    <div class="card" style="padding:1.2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px;">
        <div class="card-title" style="margin:0; font-weight:600; font-size:1.1rem; color:var(--primary, #D88A00);">
          🏢 Registered Companies ({{ count($pageData['companies']) }})
        </div>
        <input type="text" placeholder="Search company, GST, city..." oninput="filterActionCompaniesTable(this)" style="width:240px; padding:0.4rem 0.8rem; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="table-container" style="overflow-x:auto;">
        <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.08)); text-align:left;">
              <th style="padding:8px 6px;">#</th>
              <th style="padding:8px 6px;">Company Name</th>
              <th style="padding:8px 6px;">GST No.</th>
              <th style="padding:8px 6px;">Pincode</th>
              <th style="padding:8px 6px;">Contact</th>
              <th style="padding:8px 6px;">Address</th>
              <th style="padding:8px 6px; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody id="action-companies-tbody">
            @forelse($pageData['companies'] as $idx => $comp)
              <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:8px 6px; color:var(--text-muted);">{{ $idx + 1 }}</td>
                <td class="action-comp-name" style="padding:8px 6px; font-weight:600; color:var(--text-main);">{{ $comp['name'] }}</td>
                <td style="padding:8px 6px; font-family:monospace; color:var(--primary-light, #f59e0b);">{{ $comp['gst'] ?: 'N/A' }}</td>
                <td style="padding:8px 6px;">{{ $comp['pincode'] ?: '—' }}</td>
                <td style="padding:8px 6px;">{{ $comp['contact'] ?: '—' }}</td>
                <td style="padding:8px 6px; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $comp['address'] }}">{{ $comp['address'] ?: '—' }}</td>
                <td style="padding:8px 6px; text-align:center;">
                  <a class="btn btn-sm" href="/sales/action?editCompany={{ $comp['id'] }}" style="width:auto; padding:0.25rem 0.6rem; font-size:0.75rem; text-decoration:none; background:var(--warning, #FFA500); color:#000; font-weight:600; display:inline-block;">
                    ✏️ Edit
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No companies registered yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab 3: Transport -->
  <div id="sales-tab-transport" class="sales-tab-content animation-fadeIn" style="display:none;">
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="form-group">
        <label>Transporter Name *</label>
        <input type="text" id="trans-name" value="NA" placeholder="e.g. National Logistics" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="form-group">
        <label>GST No. *</label>
        <input type="text" id="trans-gst" maxlength="15" placeholder="15-digit GST (e.g. 22AAAAA0000A1Z5)" style="text-transform:uppercase; padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="form-group">
        <label>Driver Contact / Mobile No</label>
        <div style="display:flex; gap:8px;">
          <select id="trans-country-code" onchange="app.onCountryCodeChange('trans')" style="width:68px; padding:0.7rem 0.2rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; flex-shrink:0; text-align:center; cursor:pointer;">
            <option value="+91" selected>+91</option>
            <option value="+1">+1</option>
            <option value="+44">+44</option>
            <option value="+971">+971</option>
            <option value="+966">+966</option>
            <option value="+61">+61</option>
            <option value="+65">+65</option>
            <option value="+49">+49</option>
            <option value="+33">+33</option>
            <option value="+86">+86</option>
            <option value="+81">+81</option>
            <option value="other">+...</option>
          </select>
          <input type="text" id="trans-contact" placeholder="10-digit mobile or 079 landline" style="flex:1; padding:0.7rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
        </div>
      </div>
      <div class="form-group">
        <label>Vehicle No.</label>
        <input type="text" id="trans-vehicles" placeholder="e.g. MH 12 AB 1234" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <button class="btn" onclick="app.submitTransport()" style="padding:1rem; font-size:1.1rem; margin-top:0.5rem;">Save Transport</button>
    </div>

    <!-- Registered Transporters List Table Below Form -->
    <div class="card" style="padding:1.2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px;">
        <div class="card-title" style="margin:0; font-weight:600; font-size:1.1rem; color:var(--primary, #D88A00);">
          🚚 Registered Transporters ({{ count($pageData['transportCompanies']) }})
        </div>
        <input type="text" placeholder="Search transporter, GST..." oninput="filterActionTransportersTable(this)" style="width:240px; padding:0.4rem 0.8rem; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
      <div class="table-container" style="overflow-x:auto;">
        <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.08)); text-align:left;">
              <th style="padding:8px 6px;">#</th>
              <th style="padding:8px 6px;">Transporter Name</th>
              <th style="padding:8px 6px;">GST No.</th>
              <th style="padding:8px 6px;">Contact</th>
              <th style="padding:8px 6px;">Vehicles</th>
            </tr>
          </thead>
          <tbody id="action-transporters-tbody">
            @forelse($pageData['transportCompanies'] as $idx => $trans)
              <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:8px 6px; color:var(--text-muted);">{{ $idx + 1 }}</td>
                <td class="action-trans-name" style="padding:8px 6px; font-weight:600; color:var(--text-main);">{{ $trans['name'] }}</td>
                <td style="padding:8px 6px; font-family:monospace; color:var(--primary-light, #f59e0b);">{{ $trans['gst'] ?: 'N/A' }}</td>
                <td style="padding:8px 6px;">{{ $trans['contact'] ?: '—' }}</td>
                <td style="padding:8px 6px;">{{ $trans['vehicles'] ?: '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No transporters registered yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  window.serverPageData = @json($pageData);
  window.currentFinProds = @json($pageData['products']);

  function switchSalesTab(tab, btnEl) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    btnEl.classList.add('active');

    document.querySelectorAll('.sales-tab-content').forEach(content => content.style.display = 'none');
    document.getElementById('sales-tab-' + tab).style.display = 'block';
  }

  function filterActionCompaniesTable(input) {
    const q = (input.value || '').trim().toUpperCase();
    const rows = document.querySelectorAll('#action-companies-tbody tr');
    rows.forEach(tr => {
      const text = tr.innerText.toUpperCase();
      tr.style.display = (!q || text.indexOf(q) > -1) ? '' : 'none';
    });
  }

  function filterActionTransportersTable(input) {
    const q = (input.value || '').trim().toUpperCase();
    const rows = document.querySelectorAll('#action-transporters-tbody tr');
    rows.forEach(tr => {
      const text = tr.innerText.toUpperCase();
      tr.style.display = (!q || text.indexOf(q) > -1) ? '' : 'none';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    const savedTab = sessionStorage.getItem('activeSalesTab');
    if (savedTab) {
      sessionStorage.removeItem('activeSalesTab');
      const tabBtns = document.querySelectorAll('.tab-btn');
      if (savedTab === 'company' && tabBtns[1]) switchSalesTab('company', tabBtns[1]);
      if (savedTab === 'transport' && tabBtns[2]) switchSalesTab('transport', tabBtns[2]);
    }

    @if(empty($pageData['editOrder']))
      window.currentOrderType = 'ALL';
      window.currentFinProds = (window.serverPageData && window.serverPageData.products) || [];
      const prodList = document.getElementById('order-products');
      if (prodList && prodList.children.length === 0) {
        app.addOrderProductRow();
      }
    @endif
  });
</script>

@if(!empty($pageData['editOrder']))
<script>
  document.addEventListener('DOMContentLoaded', () => {
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

    const fullContact = "{{ $pageData['editCompany']->contact ?? '' }}".trim();
    if (fullContact) {
      const codeEl = document.getElementById('comp-country-code');
      const inputEl = document.getElementById('comp-contact');
      if (codeEl && inputEl) {
        const match = fullContact.match(/^(\+\d{1,4})\s*(.*)$/);
        if (match) {
          const code = match[1];
          const num = match[2];
          const hasOption = Array.from(codeEl.options).some(o => o.value === code);
          if (hasOption) {
            codeEl.value = code;
            inputEl.value = num;
          } else {
            codeEl.value = 'other';
            inputEl.value = fullContact;
          }
        } else {
          codeEl.value = '+91';
          inputEl.value = fullContact;
        }
      }
    }
  });
</script>
@endif
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const prodList = document.getElementById('order-products');
    if (prodList && prodList.children.length === 0) {
      app.addOrderProductRow();
    }
  });
</script>
@endsection