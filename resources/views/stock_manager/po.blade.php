@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
  <h2 style="margin:0;">🛒 PO Received by System</h2>
  <button class="btn btn-primary btn-sm" onclick="openNewPoModal()">+ New Request</button>
</div>

@if(empty($pageData['purchaseOrders']) || $pageData['purchaseOrders']->isEmpty())
  <div class="card" style="padding:2rem; text-align:center;">
    <p style="color:var(--text-muted); margin:0;">No purchase requests found.</p>
  </div>
@else
<div class="card" style="padding:1.2rem;">
  <div class="table-container" style="overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; padding-bottom:6px;">
    <table style="min-width: 750px; width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Material</th>
          <th>Order Quantity (kg)</th>
          <th>Note</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pageData['purchaseOrders'] as $po)
        <tr id="po-row-{{ $po->id }}">
          <td style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap;">
            <div style="font-weight:600; color:var(--text-main);">{{ $po->created_at->format('d-m-Y') }}</div>
            @if($po->status === 'RECEIVED' && $po->date)
              <div style="font-size:0.72rem; color:#059669; font-weight:600;">Rec: {{ \Carbon\Carbon::parse($po->date)->format('d-m-Y') }}</div>
            @endif
          </td>
          <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
          <td style="color:var(--primary-light); font-weight:bold;">{{ number_format($po->quantity, 1) }} {{ $po->product?->unit ?? 'kg' }}</td>
          <td style="font-size:0.85rem; color:var(--text-muted); max-width:250px;">
            <div style="word-break:break-word; white-space:normal; line-height:1.35;">
              {{ $po->note ?? '—' }}
            </div>
          </td>
          <td>
            @if($po->status === 'PENDING')
              <span class="badge" style="background:#ef4444; color:#ffffff !important;">PENDING</span>
            @elseif($po->status === 'READ')
              <span class="badge" style="background:#eab308; color:#ffffff !important;">READ</span>
            @elseif($po->status === 'ORDERED')
              <span class="badge" style="background:#3b82f6; color:#ffffff !important;">ORDERED</span>
            @elseif($po->status === 'RECEIVED')
              <span class="badge badge-done">RECEIVED</span>
            @elseif($po->status === 'REJECTED')
              <span class="badge" style="background:#dc2626; color:#ffffff !important;">REJECTED</span>
            @else
              <span class="badge badge-pending">{{ $po->status }}</span>
            @endif
          </td>
          <td>
            <div style="display:flex; align-items:center; gap:0.4rem;">
              @if($po->status === 'ORDERED')
                <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:#fff; border:none; border-radius:4px; cursor:pointer;" onclick="smReceivePo('{{ $po->id }}', this)">
                  📦 Mark as Received
                </button>
              @endif
              <button class="btn-icon delete" onclick="deletePo('{{ $po->id }}')" title="Delete Request">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="14" x2="14" y2="17"></line></svg>
              </button>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Pagination Links -->
  <div style="margin-top:1.5rem; display:flex; justify-content:center;">
    {{ $pageData['purchaseOrders']->links() }}
  </div>
</div>
@endif

<!-- Select2 Stylesheet fallback -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
/* Modern Select2 Styling for PO Modal */
#po-modal .select2-container {
  width: 100% !important;
}
#po-modal .select2-container .select2-selection--single {
  background-color: #ffffff !important;
  border: 1px solid #d1d5db !important;
  height: 2.6rem !important;
  border-radius: 8px !important;
  display: flex !important;
  align-items: center !important;
  padding: 0 0.5rem !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
#po-modal .select2-container--default .select2-selection--single:focus,
#po-modal .select2-container--default.select2-container--open .select2-selection--single {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
}
#po-modal .select2-container .select2-selection--single .select2-selection__rendered {
  color: #111827 !important;
  font-weight: 600 !important;
  font-size: 0.9rem !important;
  line-height: 2.6rem !important;
  padding-left: 0.2rem !important;
  padding-right: 1.8rem !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}
#po-modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: #9ca3af !important;
  font-weight: 500 !important;
}
#po-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 2.6rem !important;
  right: 8px !important;
}
.po-select2-dropdown.select2-dropdown {
  border: 1px solid #d1d5db !important;
  border-radius: 8px !important;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18) !important;
  z-index: 999999 !important;
  background: #ffffff !important;
  overflow: hidden !important;
}
.po-select2-dropdown .select2-search--dropdown {
  padding: 8px !important;
  background: #f9fafb !important;
  border-bottom: 1px solid #e5e7eb !important;
}
.po-select2-dropdown .select2-search--dropdown .select2-search__field {
  border: 1px solid #d1d5db !important;
  border-radius: 6px !important;
  padding: 0.45rem 0.65rem !important;
  font-size: 0.88rem !important;
  outline: none !important;
  background: #ffffff !important;
  color: #111827 !important;
  width: 100% !important;
  box-sizing: border-box !important;
}
.po-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
  border-color: #f59e0b !important;
}
.po-select2-dropdown .select2-results__options {
  max-height: 260px !important;
  overflow-y: auto !important;
  padding: 4px 0 !important;
}
.po-select2-dropdown .select2-results__option {
  font-size: 0.86rem !important;
  padding: 0.55rem 0.75rem !important;
  color: #374151 !important;
  line-height: 1.35 !important;
  border-bottom: 1px solid #f3f4f6 !important;
}
.po-select2-dropdown .select2-results__option:last-child {
  border-bottom: none !important;
}
.po-select2-dropdown .select2-results__option--highlighted[aria-selected] {
  background-color: #f59e0b !important;
  color: #ffffff !important;
}
.po-select2-dropdown .select2-results__option--highlighted[aria-selected] * {
  color: #ffffff !important;
}
.po-select2-dropdown .select2-results__option[aria-selected="true"] {
  background-color: #fef3c7 !important;
  color: #92400e !important;
  font-weight: 700 !important;
}
</style>

<!-- Add PO Modal -->
<div id="po-modal" class="modal-overlay" onclick="if(event.target==this) closeModal()">
  <div class="modal-content card" style="max-width:480px; width:100%; border-radius:12px; padding:1.5rem;">
    <div class="card-title" id="po-modal-title" style="margin-bottom:1.2rem; font-weight:700; font-size:1.15rem; color:#111827;">Request Material</div>
    <form action="" method="POST" id="po-form" onsubmit="handlePoSubmit(event, this)">
      @csrf
      <input type="hidden" id="po-id" name="po_id" value="">
      
      <div class="form-group" id="product-select-group" style="margin-bottom:1.2rem;">
        <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.88rem; color:#374151;">Select Material *</label>
        <select name="product_id" id="po-product-id" style="width:100%;">
            <option value="" disabled selected>-- Search &amp; Select Material --</option>
            @foreach($pageData['products'] as $rm)
                @php
                    $typeDisp = strtoupper($rm->type ?? 'RAW');
                    $availQty = (float) $rm->totalAvailableStock();
                    $unit = $rm->unit ?? 'kg';
                @endphp
                <option value="{{ $rm->id }}" data-name="{{ $rm->name }}" data-type="{{ $typeDisp }}" data-unit="{{ $unit }}" data-avail="{{ $availQty }}">
                  {{ $rm->name }} [{{ $typeDisp }}] (Avail: {{ number_format($availQty, 1) }} {{ $unit }})
                </option>
            @endforeach
        </select>
        <div id="po-avail-hint" style="font-size:0.8rem; color:#059669; font-weight:600; margin-top:0.4rem; display:none;"></div>
      </div>

      <div class="form-group" id="product-name-group" style="margin-bottom:1.2rem; display:none;">
        <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.88rem; color:#374151;">Material</label>
        <input type="text" id="po-product-name" readonly style="width:100%; padding:0.55rem; border-radius:8px; border:1px solid #d1d5db; background-color: #f3f4f6; color:#111827;">
      </div>

      <div class="form-group" style="margin-bottom:1.2rem;">
        <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.88rem; color:#374151;">Order Quantity (<span id="po-unit-label">kg</span>) *</label>
        <input type="number" id="po-quantity" name="quantity" step="0.001" min="0.001" required placeholder="Enter quantity..." style="width:100%; padding:0.55rem; border-radius:8px; border:1px solid #d1d5db; font-size:0.95rem; font-weight:600; color:#111827;">
      </div>

      <div class="form-group" style="margin-bottom:1.5rem;">
        <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.88rem; color:#374151;">Note <span style="font-weight:400; color:#9ca3af;">(Optional)</span></label>
        <textarea id="po-note" name="note" placeholder="Add optional purchase notes..." style="width:100%; padding:0.55rem; border-radius:8px; border:1px solid #d1d5db; font-size:0.88rem; color:#111827; height:65px; resize:vertical;"></textarea>
      </div>

      <div style="display:flex; gap:10px;">
        <button type="submit" id="po-submit-btn" class="btn btn-primary" style="flex:1; padding:0.7rem; font-weight:700; border-radius:8px; background:#f59e0b; color:#fff; border:none; cursor:pointer;">Submit Request</button>
        <button type="button" class="btn btn-secondary" style="flex:1; padding:0.7rem; font-weight:600; border-radius:8px;" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

function escapeHtml(text) {
  if (!text) return '';
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function ensureDependencies(callback) {
  if (typeof jQuery === 'undefined') {
    const s = document.createElement('script');
    s.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
    s.onload = () => loadSelect2(callback);
    document.head.appendChild(s);
  } else {
    loadSelect2(callback);
  }
}

function loadSelect2(callback) {
  if (typeof jQuery.fn.select2 === 'undefined') {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
    s.onload = () => { if (callback) callback(); };
    document.head.appendChild(s);
  } else {
    if (callback) callback();
  }
}

function initPoSelect2() {
  ensureDependencies(() => {
    const $select = $('#po-product-id');
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      dropdownParent: $('#po-modal .modal-content'),
      dropdownCssClass: 'po-select2-dropdown',
      placeholder: '-- Search & Select Material --',
      allowClear: false,
      width: '100%',
      matcher: function(params, data) {
        if ($.trim(params.term) === '') {
          return data;
        }

        const terms = $.trim(params.term).toLowerCase().split(/\s+/);
        const text = (data.text || '').toLowerCase();
        const allMatched = terms.every(t => text.indexOf(t) > -1);

        if (allMatched) {
          return data;
        }
        return null;
      },
      templateResult: function(state) {
        if (!state.id) return state.text;
        const $el = $(state.element);
        const type = $el.data('type') || '';
        const unit = $el.data('unit') || 'kg';
        const avail = parseFloat($el.data('avail') || 0);
        const name = $el.data('name') || state.text;

        let typeColor = '#f59e0b';
        if (type === 'SEMI') typeColor = '#3b82f6';
        if (type === 'FINISHED' || type === 'FG') typeColor = '#10b981';

        const availFormatted = avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
        const availColor = avail > 0 ? '#059669' : '#9ca3af';

        return $(`
          <div style="display:flex; justify-content:space-between; align-items:center; width:100%; padding:2px 0;">
            <div style="font-weight:600; color:#111827; display:flex; align-items:center; gap:6px;">
              <span>${escapeHtml(name)}</span>
              <span style="font-size:0.68rem; font-weight:700; padding:1px 6px; border-radius:4px; background:${typeColor}20; color:${typeColor}; border:1px solid ${typeColor}40;">${escapeHtml(type)}</span>
            </div>
            <div style="font-size:0.75rem; font-weight:700; color:${availColor}; white-space:nowrap; margin-left:12px;">
              Avail: ${availFormatted} ${escapeHtml(unit)}
            </div>
          </div>
        `);
      },
      templateSelection: function(state) {
        if (!state.id) return state.text;
        const $el = $(state.element);
        const type = $el.data('type') || '';
        const unit = $el.data('unit') || 'kg';
        const avail = parseFloat($el.data('avail') || 0);
        const name = $el.data('name') || state.text;
        const availFormatted = avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
        return `${name} [${type}] (Avail: ${availFormatted} ${unit})`;
      }
    });

    $select.off('change.po').on('change.po', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (selectedOption && selectedOption.value) {
        const unit = selectedOption.getAttribute('data-unit') || 'kg';
        const avail = parseFloat(selectedOption.getAttribute('data-avail') || 0);

        const unitLabel = document.getElementById('po-unit-label');
        if (unitLabel) unitLabel.textContent = unit;

        const availHint = document.getElementById('po-avail-hint');
        if (availHint) {
          availHint.innerHTML = `✅ Current Available Stock: <strong>${avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 })} ${unit}</strong>`;
          availHint.style.display = 'block';
        }
      } else {
        const availHint = document.getElementById('po-avail-hint');
        if (availHint) availHint.style.display = 'none';
      }
    });

    $select.off('select2:open').on('select2:open', function() {
      setTimeout(() => {
        const searchField = document.querySelector('.po-select2-dropdown .select2-search__field');
        if (searchField) searchField.focus();
      }, 50);
    });
  });
}

function openNewPoModal() {
  document.getElementById('po-modal-title').textContent = 'Request Material';
  document.getElementById('po-form').action = window.baseUrl + '/' + window.userSlug + '/po';
  document.getElementById('po-id').value = '';
  document.getElementById('product-select-group').style.display = 'block';
  document.getElementById('product-name-group').style.display = 'none';
  document.getElementById('po-quantity').value = '';
  document.getElementById('po-note').value = '';
  document.getElementById('po-avail-hint').style.display = 'none';
  document.getElementById('po-unit-label').textContent = 'kg';

  const btn = document.getElementById('po-submit-btn');
  btn.textContent = 'Submit Request';
  btn.disabled = false;
  btn.style.opacity = '1';

  document.getElementById('po-modal').classList.add('active');

  // Reset and initialize Select2 with smart search
  const $select = $('#po-product-id');
  if ($select.length) {
    $select.val('');
  }
  initPoSelect2();
}

function closeModal() {
  document.getElementById('po-modal').classList.remove('active');
  if (typeof $ !== 'undefined' && $('#po-product-id').data('select2')) {
    $('#po-product-id').select2('close');
  }
}

function handlePoSubmit(e, form) {
  const isSelectVisible = document.getElementById('product-select-group').style.display !== 'none';
  const prodVal = document.getElementById('po-product-id').value;
  if (isSelectVisible && !prodVal) {
    e.preventDefault();
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Select Material',
        text: 'Please search and select a material to proceed.',
        icon: 'warning',
        confirmButtonColor: '#f59e0b'
      });
    } else {
      alert('Please search and select a material.');
    }
    if (typeof $ !== 'undefined' && $('#po-product-id').data('select2')) {
      $('#po-product-id').select2('open');
    }
    return false;
  }

  const qty = parseFloat(document.getElementById('po-quantity').value);
  if (!qty || qty <= 0) {
    e.preventDefault();
    alert('Please enter a valid order quantity.');
    return false;
  }

  disableBtn(form);
  return true;
}

function disableBtn(form) {
  const btn = form.querySelector('button[type="submit"]');
  if (btn) {
    btn.disabled = true;
    btn.style.opacity = '0.7';
    btn.innerHTML = 'Submitting...';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initPoSelect2();
});

function smReceivePo(id, btn) {
  const todayStr = new Date().toISOString().split('T')[0];
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Mark as Received?',
      html: `
        <div style="text-align: left; padding: 0.2rem 0.4rem;">
          <p style="color: #4b5563; font-size: 0.88rem; margin: 0 0 1rem 0;">Confirm that this order has been received.</p>
          
          <div style="margin-bottom: 1rem;">
            <label for="swal-receive-date" style="display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.35rem;">
              Received Date *
            </label>
            <input type="date" id="swal-receive-date" class="swal2-input" value="${todayStr}" max="${todayStr}" style="width: 100%; height: 2.6rem; margin: 0; box-sizing: border-box; font-size: 0.9rem; border: 1px solid #d1d5db; border-radius: 6px; padding: 0 0.75rem;">
          </div>
          
          <div style="margin-bottom: 0.5rem;">
            <label for="swal-receive-note" style="display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.35rem;">
              Note <span style="font-weight: 400; color: #9ca3af;">(Optional)</span>
            </label>
            <textarea id="swal-receive-note" class="swal2-textarea" placeholder="Add optional note..." style="width: 100%; height: 65px; margin: 0; box-sizing: border-box; font-size: 0.88rem; border: 1px solid #d1d5db; border-radius: 6px; padding: 0.5rem; resize: vertical;"></textarea>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Yes, Received!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#10b981',
      cancelButtonColor: '#6b7280',
      focusConfirm: false,
      didOpen: () => {
        const dateInput = document.getElementById('swal-receive-date');
        if (dateInput) dateInput.focus();
      },
      preConfirm: () => {
        const dateVal = document.getElementById('swal-receive-date').value;
        if (!dateVal) {
          Swal.showValidationMessage('Please select a received date');
          return false;
        }
        const noteVal = document.getElementById('swal-receive-note').value;
        return { date: dateVal, note: noteVal };
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const { date, note } = result.value;
        fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ po_id: id, date: date, note: note })
        }).then(r => r.json()).then(d => {
          if (d.success) {
            Swal.fire({
              icon: 'success',
              title: 'Received!',
              text: d.message || 'PO marked as received successfully!',
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: d.message || 'Error updating status'
            });
          }
        }).catch(err => {
          Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: 'An error occurred while updating the order status.'
          });
        });
      }
    });
  } else {
    const dateVal = prompt('Enter Received Date (YYYY-MM-DD):', todayStr);
    if (dateVal !== null) {
      const note = prompt('Add optional note:') || '';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id, date: dateVal, note: note })
      }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'Error updating status');
      });
    }
  }
}

function deletePo(id) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Delete this purchase request?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete!'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(window.baseUrl + '/' + window.userSlug + '/po/' + id, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if (d.success) {
            location.reload();
          } else {
            alert(d.message || 'Error deleting PO');
          }
        });
      }
    });
  } else if (confirm('Are you sure you want to delete this purchase request?')) {
    fetch(window.baseUrl + '/' + window.userSlug + '/po/' + id, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => {
      if (d.success) {
        location.reload();
      } else {
        alert(d.message || 'Error deleting PO');
      }
    });
  }
}
</script>
@endsection
