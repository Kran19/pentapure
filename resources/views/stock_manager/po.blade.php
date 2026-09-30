@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'stock_manager') || str_contains(request()->path(), 'stock-manager') || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

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
/* Modern Premium Styling for Request Material Modal */
#po-modal.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.65) !important;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  padding: 1rem;
  box-sizing: border-box;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
#po-modal.modal-overlay.active {
  opacity: 1;
  pointer-events: all;
}
#po-modal .modal-content.po-modal-card {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 16px !important;
  box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.3), 0 0 1px rgba(0, 0, 0, 0.1) !important;
  max-width: 520px !important;
  width: 100% !important;
  padding: 0 !important;
  overflow: visible !important;
  transform: translateY(12px) scale(0.98);
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}
#po-modal.modal-overlay.active .modal-content.po-modal-card {
  transform: translateY(0) scale(1) !important;
}

/* Modal Header */
.po-modal-header {
  padding: 1.25rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.po-modal-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  color: #b45309;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
  flex-shrink: 0;
}
.po-modal-close-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  line-height: 1;
  transition: all 0.15s ease;
}
.po-modal-close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* Modal Form Controls */
.po-modal-body {
  padding: 1.25rem 1.5rem 1.5rem 1.5rem;
}
.po-field-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.45rem;
  font-weight: 600;
  font-size: 0.86rem;
  color: #334155;
}
.po-input-control {
  width: 100%;
  height: 44px;
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  background: #ffffff;
  font-size: 0.92rem;
  font-weight: 600;
  color: #0f172a;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.po-input-control:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
}

/* Select2 Container & Dropdown Styling */
#po-modal .select2-container {
  width: 100% !important;
}
#po-modal .select2-container .select2-selection--single {
  background-color: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  height: 44px !important;
  border-radius: 8px !important;
  display: flex !important;
  align-items: center !important;
  padding: 0 0.65rem !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}
#po-modal .select2-container--default.select2-container--focus .select2-selection--single,
#po-modal .select2-container--default.select2-container--open .select2-selection--single {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
  outline: none !important;
}
#po-modal .select2-container .select2-selection--single .select2-selection__rendered {
  color: #0f172a !important;
  font-weight: 600 !important;
  font-size: 0.88rem !important;
  line-height: 44px !important;
  padding-left: 0.2rem !important;
  padding-right: 1.8rem !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}
#po-modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: #94a3b8 !important;
  font-weight: 500 !important;
}
#po-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 44px !important;
  right: 8px !important;
}

/* Dropdown Menu attached to #po-modal */
.po-select2-dropdown.select2-dropdown {
  border: 1px solid #cbd5e1 !important;
  border-radius: 10px !important;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.2) !important;
  z-index: 999999 !important;
  background: #ffffff !important;
  overflow: hidden !important;
  box-sizing: border-box !important;
}
.po-select2-dropdown .select2-search--dropdown {
  padding: 8px !important;
  background: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
}
.po-select2-dropdown .select2-search--dropdown .select2-search__field {
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 7px !important;
  padding: 0.45rem 0.65rem !important;
  font-size: 0.86rem !important;
  outline: none !important;
  background: #ffffff !important;
  color: #0f172a !important;
  width: 100% !important;
  box-sizing: border-box !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
}
.po-select2-dropdown .select2-search--dropdown .select2-search__field:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25) !important;
}
.po-select2-dropdown .select2-results__options {
  max-height: 250px !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 4px 0 !important;
}
.po-select2-dropdown .select2-results__option {
  font-size: 0.84rem !important;
  padding: 0.55rem 0.8rem !important;
  color: #0f172a !important;
  line-height: 1.35 !important;
  border-bottom: 1px solid #f1f5f9 !important;
  cursor: pointer !important;
  background-color: #ffffff !important;
  transition: background-color 0.1s ease, color 0.1s ease !important;
}
.po-select2-dropdown .select2-results__option:last-child {
  border-bottom: none !important;
}

/* Selected state */
.po-select2-dropdown .select2-results__option[aria-selected="true"] {
  background-color: #fef9c3 !important;
  border-left: 3px solid #eab308 !important;
}
.po-select2-dropdown .select2-results__option[aria-selected="true"] .prod-name {
  color: #854d0e !important;
  font-weight: 700 !important;
}

/* High-Contrast Hover & Highlight state (matches admin/stock!) */
.po-select2-dropdown .select2-results__option--highlighted,
.po-select2-dropdown .select2-results__option--highlighted[aria-selected],
.po-select2-dropdown .select2-results__option--highlighted[aria-selected="true"],
.po-select2-dropdown .select2-results__option--highlighted[aria-selected="false"],
.po-select2-dropdown .select2-results__option:hover {
  background-color: #fef08a !important;
  color: #000000 !important;
  border-left: 3px solid #ca8a04 !important;
}
.po-select2-dropdown .select2-results__option--highlighted .prod-name,
.po-select2-dropdown .select2-results__option:hover .prod-name {
  color: #000000 !important;
  font-weight: 700 !important;
}
.po-select2-dropdown .select2-results__option--highlighted .prod-stage-badge,
.po-select2-dropdown .select2-results__option:hover .prod-stage-badge {
  border-color: rgba(0, 0, 0, 0.25) !important;
  background-color: #ffffff !important;
  font-weight: 700 !important;
}
.po-select2-dropdown .select2-results__option--highlighted .prod-avail,
.po-select2-dropdown .select2-results__option:hover .prod-avail {
  color: #15803d !important;
  font-weight: 700 !important;
}

/* Material Stage Filter Pills */
.po-stage-pills { display: flex; gap: 6px; margin-bottom: 0.75rem; flex-wrap: wrap; }
.po-stage-pill {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 700;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
}
.po-stage-pill:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }
.po-stage-pill.active { background: #0f172a; color: #ffffff; border-color: #0f172a; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.2); }
.po-stage-pill[data-stage="RAW"].active { background: #059669; border-color: #059669; }
.po-stage-pill[data-stage="SEMI"].active { background: #2563eb; border-color: #2563eb; }
.po-stage-pill[data-stage="FG"].active { background: #d97706; border-color: #d97706; }
.po-stage-pill[data-stage="PKG"].active { background: #0284c7; border-color: #0284c7; }
</style>

<!-- Add PO Modal -->
<div id="po-modal" class="modal-overlay" onclick="if(event.target==this) closeModal()">
  <div class="modal-content po-modal-card">
    <!-- Header -->
    <div class="po-modal-header">
      <div style="display:flex; align-items:center; gap:12px;">
        <div class="po-modal-icon-badge">📦</div>
        <div>
          <div id="po-modal-title" style="font-weight:700; font-size:1.15rem; color:#0f172a; line-height:1.2;">Request Material</div>
          <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Submit purchase request for factory stock</div>
        </div>
      </div>
      <button type="button" class="po-modal-close-btn" onclick="closeModal()" title="Close">&times;</button>
    </div>

    <!-- Body -->
    <form action="" method="POST" id="po-form" class="po-modal-body" onsubmit="handlePoSubmit(event, this)">
      @csrf
      <input type="hidden" id="po-id" name="po_id" value="">
      
      <!-- Material Select Group -->
      <div class="form-group" id="product-select-group" style="margin-bottom:1.15rem;">
        <label for="po-product-id" class="po-field-label">
          <span>Select Material <span style="color:#ef4444;">*</span></span>
          <span style="font-size:0.72rem; color:#94a3b8; font-weight:500;">Smart Search</span>
        </label>

        <!-- Material Category Filter Pills -->
        <div class="po-stage-pills">
          <button type="button" class="po-stage-pill active" data-stage="ALL" onclick="filterPoStage('ALL', this)">ALL</button>
          <button type="button" class="po-stage-pill" data-stage="RAW" onclick="filterPoStage('RAW', this)">🌿 RAW</button>
          <button type="button" class="po-stage-pill" data-stage="SEMI" onclick="filterPoStage('SEMI', this)">⚗️ SEMI</button>
          <button type="button" class="po-stage-pill" data-stage="FG" onclick="filterPoStage('FG', this)">✅ FG</button>
          <button type="button" class="po-stage-pill" data-stage="PKG" onclick="filterPoStage('PKG', this)">📦 PACKAGING</button>
        </div>

        <select name="product_id" id="po-product-id" style="width:100%;">
          <option value="" disabled selected>-- Search &amp; Select Material --</option>
          @foreach($pageData['products'] as $rm)
            @php
              $rawType = strtoupper($rm->type ?? 'RAW');
              $shortType = match($rawType) {
                'FINISHED', 'FG' => 'FG',
                'PACKAGING', 'PKG' => 'PKG',
                'SEMI' => 'SEMI',
                default => 'RAW'
              };
              $availQty = (float) $rm->totalAvailableStock();
              $unit = $rm->unit ?? 'kg';
            @endphp
            <option value="{{ $rm->id }}" data-name="{{ $rm->name }}" data-type="{{ $shortType }}" data-raw-type="{{ $rawType }}" data-unit="{{ $unit }}" data-avail="{{ $availQty }}">
              {{ $rm->name }} [{{ $shortType }}] (Avail: {{ number_format($availQty, 1) }} {{ $unit }})
            </option>
          @endforeach
        </select>
        <!-- Dynamic Stock Hint Card -->
        <div id="po-avail-hint" style="margin-top:0.5rem; padding:0.55rem 0.8rem; border-radius:8px; font-size:0.8rem; font-weight:600; display:none; transition:all 0.2s ease;"></div>
      </div>

      <!-- Static Material Display (for edit mode if needed) -->
      <div class="form-group" id="product-name-group" style="margin-bottom:1.15rem; display:none;">
        <label for="po-product-name" class="po-field-label">
          <span>Material</span>
        </label>
        <input type="text" id="po-product-name" readonly class="po-input-control" style="background-color:#f8fafc; color:#475569;">
      </div>

      <!-- Quantity Input Group with Integrated Unit Badge -->
      <div class="form-group" style="margin-bottom:1.15rem;">
        <label for="po-quantity" class="po-field-label">
          <span>Order Quantity (<span id="po-unit-label">kg</span>) <span style="color:#ef4444;">*</span></span>
        </label>
        <div style="position:relative; display:flex; align-items:center;">
          <input type="number" id="po-quantity" name="quantity" step="0.001" min="0.001" required placeholder="Enter quantity..." class="po-input-control" style="padding-right:4.2rem;">
          <span id="po-unit-badge" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; font-size:0.75rem; font-weight:700; padding:4px 9px; border-radius:6px; text-transform:uppercase; pointer-events:none; letter-spacing:0.02em;">KG</span>
        </div>
      </div>

      <!-- Notes Field -->
      <div class="form-group" style="margin-bottom:1.4rem;">
        <label for="po-note" class="po-field-label">
          <span>Note <span style="font-weight:400; color:#94a3b8; font-size:0.78rem;">(Optional)</span></span>
        </label>
        <textarea id="po-note" name="note" placeholder="Add optional purchase notes, specifications, or urgency..." style="width:100%; height:68px; padding:0.6rem 0.85rem; border-radius:8px; border:1.5px solid #cbd5e1; font-size:0.86rem; color:#0f172a; resize:vertical; box-sizing:border-box; outline:none; transition:border-color 0.15s ease, box-shadow 0.15s ease;" onfocus="this.style.borderColor='#f59e0b'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.2)';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"></textarea>
      </div>

      <!-- Action Buttons -->
      <div style="display:flex; gap:12px;">
        <button type="button" class="btn" style="flex:1; height:42px; padding:0 1rem; font-weight:600; font-size:0.88rem; border-radius:8px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; cursor:pointer; transition:all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';" onclick="closeModal()">Cancel</button>
        <button type="submit" id="po-submit-btn" class="btn" style="flex:1.4; height:42px; padding:0 1.2rem; font-weight:700; font-size:0.88rem; border-radius:8px; background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#ffffff; border:none; cursor:pointer; box-shadow:0 3px 10px rgba(245, 158, 11, 0.35); transition:all 0.15s ease;" onmouseover="this.style.filter='brightness(1.06)';" onmouseout="this.style.filter='none';">Submit Request</button>
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

let currentPoStage = 'ALL';

function filterPoStage(stage, btn) {
  currentPoStage = stage;
  document.querySelectorAll('.po-stage-pill').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  const $select = $('#po-product-id');
  if ($select.hasClass('select2-hidden-accessible')) {
    $select.select2('close');
    setTimeout(() => {
      $select.select2('open');
    }, 50);
  }
}

function initPoSelect2() {
  if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
    setTimeout(initPoSelect2, 50);
    return;
  }

  const $select = $('#po-product-id');
  if (!$select.length) return;

  if ($select.hasClass('select2-hidden-accessible')) {
    try {
      $select.select2('destroy');
    } catch(e) {}
  }

  $select.select2({
    dropdownParent: $('#po-modal'),
    dropdownCssClass: 'po-select2-dropdown',
    placeholder: '-- Search & Select Material --',
    allowClear: false,
    width: '100%',
    matcher: function(params, data) {
      if (!data.id) return data;
      const $el = $(data.element);
      const shortType = ($el.data('type') || '').toUpperCase();
      const rawType = ($el.data('raw-type') || '').toUpperCase();

      if (currentPoStage !== 'ALL') {
        const matchStage = (currentPoStage === 'PKG' || currentPoStage === 'PACKAGING')
          ? (shortType === 'PKG' || rawType === 'PACKAGING')
          : (currentPoStage === 'FG' || currentPoStage === 'FINISHED')
            ? (shortType === 'FG' || rawType === 'FINISHED')
            : (shortType === currentPoStage || rawType === currentPoStage);
        if (!matchStage) return null;
      }

      if (!params.term || $.trim(params.term) === '') {
        return data;
      }
      const term = params.term.toLowerCase().trim();
      const text = (data.text || '').toLowerCase();
      const name = ($el.data('name') || '').toLowerCase();
      const tokens = term.split(/\s+/).filter(Boolean);
      for (let i = 0; i < tokens.length; i++) {
        const token = tokens[i];
        if (text.indexOf(token) === -1 && name.indexOf(token) === -1 && shortType.toLowerCase().indexOf(token) === -1 && rawType.toLowerCase().indexOf(token) === -1) {
          return null;
        }
      }
      return data;
    },
    templateResult: function(state) {
      if (!state.id) return $(`<span style="color:#94a3b8; font-weight:500;">${escapeHtml(state.text)}</span>`);
      const $el = $(state.element);
      const rawType = ($el.data('raw-type') || $el.data('type') || 'RAW').toUpperCase();
      let shortType = rawType;
      if (rawType === 'FINISHED' || rawType === 'FG') shortType = 'FG';
      else if (rawType === 'PACKAGING' || rawType === 'PKG') shortType = 'PKG';
      else if (rawType === 'SEMI') shortType = 'SEMI';
      else if (rawType === 'RAW') shortType = 'RAW';

      const unit = $el.data('unit') || 'kg';
      const avail = parseFloat($el.data('avail') || 0);
      const name = $el.data('name') || state.text;

      let badgeBg = '#d1fae5';
      let badgeColor = '#065f46';
      let badgeBorder = '#a7f3d0';
      if (shortType === 'SEMI') {
        badgeBg = '#dbeafe'; badgeColor = '#1e40af'; badgeBorder = '#bfdbfe';
      } else if (shortType === 'FG') {
        badgeBg = '#fef3c7'; badgeColor = '#92400e'; badgeBorder = '#fde68a';
      } else if (shortType === 'PKG') {
        badgeBg = '#e0f2fe'; badgeColor = '#0369a1'; badgeBorder = '#bae6fd';
      }

      const availFormatted = avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
      const availColor = avail > 0 ? '#15803d' : '#94a3b8';

      return $(`
        <div class="prod-option-row" style="display:flex; justify-content:space-between; align-items:center; width:100%; padding:2px 0;">
          <div style="display:flex; align-items:center; gap:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
            <span class="prod-name" style="font-weight:700; color:#0f172a;">${escapeHtml(name)}</span>
            <span class="prod-stage-badge" style="font-size:0.68rem; font-weight:700; padding:1px 6px; border-radius:4px; background:${badgeBg}; color:${badgeColor}; border:1px solid ${badgeBorder};">${escapeHtml(shortType)}</span>
          </div>
          <div class="prod-avail" style="font-size:0.75rem; font-weight:700; color:${availColor}; white-space:nowrap; margin-left:12px;">
            Avail: ${availFormatted} ${escapeHtml(unit)}
          </div>
        </div>
      `);
    },
    templateSelection: function(state) {
      if (!state.id) return state.text;
      const $el = $(state.element);
      const rawType = ($el.data('raw-type') || $el.data('type') || 'RAW').toUpperCase();
      let shortType = rawType;
      if (rawType === 'FINISHED' || rawType === 'FG') shortType = 'FG';
      else if (rawType === 'PACKAGING' || rawType === 'PKG') shortType = 'PKG';
      else if (rawType === 'SEMI') shortType = 'SEMI';
      else if (rawType === 'RAW') shortType = 'RAW';

      const unit = $el.data('unit') || 'kg';
      const avail = parseFloat($el.data('avail') || 0);
      const name = $el.data('name') || state.text;
      const availFormatted = avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
      return `${name} [${shortType}] (Avail: ${availFormatted} ${unit})`;
    }
  });

  $select.off('change.po').on('change.po', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption && selectedOption.value) {
      const unit = selectedOption.getAttribute('data-unit') || 'kg';
      const avail = parseFloat(selectedOption.getAttribute('data-avail') || 0);
      const type = (selectedOption.getAttribute('data-type') || 'RAW').toUpperCase();

      const unitLabel = document.getElementById('po-unit-label');
      if (unitLabel) unitLabel.textContent = unit;
      const unitBadge = document.getElementById('po-unit-badge');
      if (unitBadge) unitBadge.textContent = unit;

      const availHint = document.getElementById('po-avail-hint');
      if (availHint) {
        const availFormatted = avail.toLocaleString('en-IN', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
        if (avail > 0) {
          availHint.style.background = '#f0fdf4';
          availHint.style.border = '1px solid #bbf7d0';
          availHint.style.color = '#15803d';
          availHint.innerHTML = `<div style="display:flex; justify-content:space-between; align-items:center;"><span>✅ Current Available Stock: <strong>${availFormatted} ${escapeHtml(unit)}</strong></span><span style="font-size:0.7rem; background:#dcfce7; padding:2px 6px; border-radius:4px; font-weight:700;">IN STOCK</span></div>`;
        } else {
          availHint.style.background = '#fef2f2';
          availHint.style.border = '1px solid #fecaca';
          availHint.style.color = '#b91c1c';
          availHint.innerHTML = `<div style="display:flex; justify-content:space-between; align-items:center;"><span>⚠️ Factory Stock: <strong>0.0 ${escapeHtml(unit)}</strong></span><span style="font-size:0.7rem; background:#fee2e2; padding:2px 6px; border-radius:4px; font-weight:700;">ZERO STOCK</span></div>`;
        }
        availHint.style.display = 'block';
      }
    } else {
      const availHint = document.getElementById('po-avail-hint');
      if (availHint) availHint.style.display = 'none';
    }
  });

  $select.off('select2:open.poFocus').on('select2:open.poFocus', function() {
    setTimeout(() => {
      const searchField = document.querySelector('.po-select2-dropdown .select2-search__field');
      if (searchField) searchField.focus();
    }, 20);
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
  const unitBadge = document.getElementById('po-unit-badge');
  if (unitBadge) unitBadge.textContent = 'kg';

  const btn = document.getElementById('po-submit-btn');
  btn.textContent = 'Submit Request';
  btn.disabled = false;
  btn.style.opacity = '1';

  document.getElementById('po-modal').classList.add('active');

  // Reset stage filter to ALL
  currentPoStage = 'ALL';
  document.querySelectorAll('.po-stage-pill').forEach(b => {
    b.classList.toggle('active', b.getAttribute('data-stage') === 'ALL');
  });

  // Reset and initialize Select2 with smart search
  const $select = $('#po-product-id');
  if ($select.length) {
    $select.val('').trigger('change.po');
  }
  setTimeout(initPoSelect2, 20);
}

function closeModal() {
  document.getElementById('po-modal').classList.remove('active');
  if (typeof $ !== 'undefined' && $('#po-product-id').data('select2')) {
    $('#po-product-id').select2('close');
  }
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const poModal = document.getElementById('po-modal');
    if (poModal && poModal.classList.contains('active')) {
      closeModal();
    }
  }
});

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
