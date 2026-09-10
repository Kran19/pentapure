@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

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
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>NA</option>
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
          <option value="" disabled {{ empty($pageData['editOrder']) ? 'selected' : '' }}>NA</option>
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
        <textarea id="order-notes" rows="2">{{ !empty($pageData['editOrder']) ? $pageData['editOrder']->notes : '' }}</textarea>
      </div>

      <button class="btn" onclick="app.submitOrder()" style="padding:1rem; font-size:1.1rem; margin-top:1rem;">
        {{ !empty($pageData['editOrder']) ? 'Update Sales Order' : 'Generate Sales Order' }}
      </button>
    </div>
  </div>

  <!-- Tab 2: Company -->
  <div id="sales-tab-company" class="sales-tab-content animation-fadeIn" style="display:none;">
    <!-- Registered Companies List Table -->
    <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px;">
        <div class="card-title" style="margin:0; font-weight:600; font-size:1.1rem; color:var(--primary, #D88A00);">
          🏢 Registered Companies ({{ count($pageData['companies']) }})
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
          <input type="text" placeholder="Search company, GST, city..." oninput="filterActionCompaniesTable(this)" style="width:220px; padding:0.4rem 0.8rem; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
          <button type="button" class="btn btn-sm" onclick="openCompanyModal()" style="width:auto; padding:0.45rem 0.9rem; font-size:0.85rem; font-weight:700; white-space:nowrap; border-radius:6px;">+ ADD COMPANY</button>
        </div>
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
                  <button type="button" class="btn btn-sm" onclick="openCompanyModal({{ json_encode($comp) }})" style="width:auto; padding:0.25rem 0.6rem; font-size:0.75rem; background:var(--warning, #FFA500); color:#000; font-weight:600; border:none; border-radius:4px; cursor:pointer;">
                    ✏️ Edit
                  </button>
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
    <!-- Registered Transporters List Table -->
    <div class="card" style="padding:1.2rem; margin-bottom:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px;">
        <div class="card-title" style="margin:0; font-weight:600; font-size:1.1rem; color:var(--primary, #D88A00);">
          🚚 Registered Transporters ({{ count($pageData['transportCompanies']) }})
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
          <input type="text" placeholder="Search transporter, GST..." oninput="filterActionTransportersTable(this)" style="width:220px; padding:0.4rem 0.8rem; font-size:0.85rem; border-radius:6px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
          <button type="button" class="btn btn-sm" onclick="app.openAddTransportModal()" style="width:auto; padding:0.45rem 0.9rem; font-size:0.85rem; font-weight:700; white-space:nowrap; border-radius:6px;">+ ADD TRANSPORT</button>
        </div>
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
              <th style="padding:8px 6px; text-align:center;">Action</th>
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
                <td style="padding:8px 6px; text-align:center;">
                  <button type="button" class="btn btn-sm" onclick="app.editTransporterPrompt({{ json_encode($trans) }})" style="width:auto; padding:0.25rem 0.6rem; font-size:0.75rem; background:var(--warning, #FFA500); color:#000; font-weight:600; border:none; border-radius:4px; cursor:pointer;">
                    ✏️ Edit
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">No transporters registered yet.</td>
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

  function openCompanyModal(comp = null) {
    const isEdit = comp && comp.id;
    const title = isEdit ? '✏️ Edit Company' : '🏢 Add New Company';
    const compName = comp ? (comp.name || '') : '';
    const compGst = comp ? (comp.gst || '') : '';
    const isUnregistered = comp && (!comp.gst || comp.gst.toUpperCase() === 'N/A');
    const compAddress = comp ? (comp.address || '') : '';
    const compPincode = comp ? (comp.pincode || '') : '';
    
    let rawContact = comp ? (comp.contact || '') : '';
    let countryCode = '+91';
    let phoneNum = rawContact;
    if (rawContact) {
      const match = rawContact.match(/^(\+\d{1,4})\s*(.*)$/);
      if (match) {
        countryCode = match[1];
        phoneNum = match[2];
      }
    }

    Swal.fire({
      title: `<span style="font-size:1.2rem; font-weight:700; color:var(--text-main, #333);">${title}</span>`,
      html: `
        <div style="text-align:left; font-size:0.88rem; max-height:70vh; overflow-y:auto; padding:4px 2px;">
          ${isEdit ? `<input type="hidden" id="swal-comp-id" value="${comp.id}">` : ''}
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Company Name *</label>
            <input id="swal-comp-name" class="swal2-input" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${compName}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Company Type *</label>
            <select id="swal-comp-type" onchange="document.getElementById('swal-comp-gst-group').style.display = (this.value==='unregistered' ? 'none' : 'block')" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px; border:1px solid #d1d5db; background:#fff; color:#333;">
              <option value="registered" ${!isUnregistered ? 'selected' : ''}>Registered Company</option>
              <option value="unregistered" ${isUnregistered ? 'selected' : ''}>Un-Registered Company</option>
            </select>
          </div>
          <div id="swal-comp-gst-group" style="margin-bottom:12px; display:${isUnregistered ? 'none' : 'block'};">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">GST Number *</label>
            <input id="swal-comp-gst" class="swal2-input" maxlength="15" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; text-transform:uppercase; box-sizing:border-radius:6px;" value="${compGst}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Address *</label>
            <textarea id="swal-comp-address" class="swal2-textarea" rows="2" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;">${compAddress}</textarea>
          </div>
          <div style="margin-bottom:12px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Pincode *</label>
            <input id="swal-comp-pincode" class="swal2-input" maxlength="6" style="width:100%; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${compPincode}">
          </div>
          <div style="margin-bottom:8px;">
            <label style="display:block; font-weight:600; margin-bottom:4px; font-size:0.8rem; text-transform:uppercase;">Contact / Mobile No *</label>
            <div style="display:flex; gap:8px;">
              <input id="swal-comp-code" class="swal2-input" value="${countryCode}" oninput="if(this.value.trim()==='+91'){ document.getElementById('swal-comp-phone').setAttribute('maxlength','10'); document.getElementById('swal-comp-phone').value = document.getElementById('swal-comp-phone').value.replace(/\\D/g,'').slice(0,10); } else { document.getElementById('swal-comp-phone').removeAttribute('maxlength'); }" style="width:75px; margin:0; padding:0.6rem 0.4rem; font-size:0.9rem; font-weight:600; text-align:center; box-sizing:border-box; border-radius:6px; flex-shrink:0;">
              <input id="swal-comp-phone" class="swal2-input" oninput="if(document.getElementById('swal-comp-code').value.trim()==='+91'){ this.value = this.value.replace(/\\D/g,'').slice(0,10); }" ${countryCode === '+91' ? 'maxlength="10"' : ''} style="flex:1; margin:0; padding:0.6rem; font-size:0.9rem; box-sizing:border-box; border-radius:6px;" value="${phoneNum}">
            </div>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: isEdit ? 'Update Company' : 'Save Company',
      confirmButtonColor: '#f59e0b',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      preConfirm: () => {
        const name = (document.getElementById('swal-comp-name').value || '').trim();
        const type = document.getElementById('swal-comp-type').value;
        const gst = (document.getElementById('swal-comp-gst').value || '').trim().toUpperCase();
        const address = (document.getElementById('swal-comp-address').value || '').trim();
        const pincode = (document.getElementById('swal-comp-pincode').value || '').trim();
        const code = (document.getElementById('swal-comp-code').value || '').trim();
        const rawPhone = (document.getElementById('swal-comp-phone').value || '').trim();

        if (!name) { Swal.showValidationMessage('Company Name is required'); return false; }
        if (type === 'registered') {
          if (!gst) { Swal.showValidationMessage('GST Number is mandatory for Registered Company'); return false; }
          if (!/^[A-Za-z0-9]{15}$/.test(gst)) { Swal.showValidationMessage('GST must be exactly 15 alphanumeric characters'); return false; }
        }
        if (!address) { Swal.showValidationMessage('Address is required'); return false; }
        if (!pincode || !/^[0-9]{6}$/.test(pincode)) { Swal.showValidationMessage('Pincode must be exactly 6 digits'); return false; }
        if (!rawPhone) { Swal.showValidationMessage('Contact / Mobile number is required'); return false; }
        if (code === '+91') {
          const cleanDigits = rawPhone.replace(/\D/g, '');
          const isLandline = /^0?79[\s\-]?[0-9]{6,8}$/.test(rawPhone);
          if (!isLandline && cleanDigits.length !== 10) {
            Swal.showValidationMessage('Contact number must be exactly 10 digits for +91');
            return false;
          }
        }

        const contact = code ? (code + ' ' + rawPhone) : rawPhone;
        return { name, gst: type === 'unregistered' ? 'N/A' : gst, address, pincode, contact, isEdit };
      }
    }).then(result => {
      if (result.isConfirmed && result.value) {
        if (window.isReadOnly) return app.toast('You have View-Only permission. Saving company is disabled.', 'error');
        
        const payload = result.value;
        const isEditId = payload.isEdit ? comp.id : null;
        const url = isEditId ? `${app.getSalesPrefix()}/company/${isEditId}` : `${app.getSalesPrefix()}/company`;

        fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken || csrfToken
          },
          body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            app.toast(d.message || 'Company saved!');
            sessionStorage.setItem('activeSalesTab', 'company');
            setTimeout(() => location.reload(), 600);
          } else {
            app.toast(d.message || 'Failed to save company', 'error');
          }
        })
        .catch(() => app.toast('Network error saving company', 'error'));
      }
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

    @if(!empty($pageData['editCompany']))
      const btn = document.querySelector('.tab-btn:nth-child(2)');
      if (btn) switchSalesTab('company', btn);
      openCompanyModal(@json($pageData['editCompany']));
    @endif

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
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const prodList = document.getElementById('order-products');
    if (prodList && prodList.children.length === 0) {
      app.addOrderProductRow();
    }
  });
</script>
@endsection