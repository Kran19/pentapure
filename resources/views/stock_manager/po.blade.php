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
          <th>Material</th>
          <th>Order Quantity (kg)</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pageData['purchaseOrders'] as $po)
        <tr id="po-row-{{ $po->id }}">
          <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
          <td style="color:var(--primary-light); font-weight:bold;">{{ number_format($po->quantity, 1) }} {{ $po->product?->unit ?? 'kg' }}</td>
          <td>
            @if($po->status === 'PENDING')
              <span class="badge" style="background:#ef4444; color:#fff;">PENDING</span>
            @elseif($po->status === 'READ')
              <span class="badge" style="background:#eab308; color:#fff;">READ</span>
            @elseif($po->status === 'ORDERED')
              <span class="badge" style="background:#3b82f6; color:#fff;">ORDERED</span>
            @elseif($po->status === 'RECEIVED')
              <span class="badge badge-done">RECEIVED</span>
            @elseif($po->status === 'REJECTED')
              <span class="badge" style="background:#dc2626; color:#fff;">REJECTED</span>
            @else
              <span class="badge badge-pending">{{ $po->status }}</span>
            @endif
          </td>
          <td style="font-size:0.8rem; color:var(--text-muted);">{{ $po->created_at->format('d M Y') }}</td>
          <td>
            <div style="display:flex; align-items:center; gap:0.4rem;">
              @if($po->status === 'ORDERED')
                <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; font-size:0.75rem; background:var(--primary); color:#fff; border:none; border-radius:4px; cursor:pointer;" onclick="smReceivePo('{{ $po->id }}', this)">
                  📦 Mark as Received
                </button>
              @endif
              <button class="btn-icon delete" onclick="deletePo('{{ $po->id }}')" title="Delete Request">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
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

<!-- Add PO Modal -->
<div id="po-modal" class="modal-overlay" onclick="if(event.target==this) closeModal()">
  <div class="modal-content card" style="max-width:400px; width:100%;">
    <div class="card-title" id="po-modal-title" style="margin-bottom:1rem; font-weight:bold; font-size:1.1rem;">Request Material</div>
    <form action="" method="POST" id="po-form" onsubmit="disableBtn(this)">
      @csrf
      <input type="hidden" id="po-id" name="po_id" value="">
      
      <div class="form-group" id="product-select-group" style="margin-bottom:1rem;">
        <label style="display:block; margin-bottom:0.4rem;">Select Material</label>
        <select name="product_id" id="po-product-id" required style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid var(--glass-border);">
            @foreach($pageData['products'] as $rm)
                @php
                    $typeDisp = strtolower($rm->type ?? 'material');
                    if ($typeDisp === 'finished') {
                        $typeDisp = 'fg';
                    }
                    $availQty = $rm->totalAvailableStock();
                @endphp
                <option value="{{ $rm->id }}">{{ $rm->name }} ({{ $typeDisp }}) (Avail: {{ number_format($availQty, 1) }} {{ $rm->unit ?? 'kg' }})</option>
            @endforeach
        </select>
      </div>

      <div class="form-group" id="product-name-group" style="margin-bottom:1rem; display:none;">
        <label style="display:block; margin-bottom:0.4rem;">Material</label>
        <input type="text" id="po-product-name" readonly style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid var(--glass-border); background-color: #f3f4f6;">
      </div>

      <div class="form-group" style="margin-bottom:1.5rem;">
        <label style="display:block; margin-bottom:0.4rem;">Order Quantity (kg)</label>
        <input type="number" id="po-quantity" name="quantity" step="0.01" min="0.1" required style="width:100%; padding:0.5rem; border-radius:6px; border:1px solid var(--glass-border);">
      </div>

      <div style="display:flex; gap:10px;">
        <button type="submit" id="po-submit-btn" class="btn btn-primary" style="flex:1;">Submit Request</button>
        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

function openNewPoModal() {
    document.getElementById('po-modal-title').textContent = 'Request Material';
    document.getElementById('po-form').action = window.baseUrl + '/' + window.userSlug + '/po';
    document.getElementById('po-id').value = '';
    document.getElementById('product-select-group').style.display = 'block';
    document.getElementById('product-name-group').style.display = 'none';
    document.getElementById('po-quantity').value = '';
    document.getElementById('po-submit-btn').textContent = 'Submit Request';
    document.getElementById('po-modal').classList.add('active');
}

function closeModal() {
    document.getElementById('po-modal').classList.remove('active');
}

function disableBtn(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.style.opacity = '0.7';
}

function smReceivePo(id, btn) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Mark as Received?',
      text: 'Confirm that this order has been received.',
      input: 'text',
      inputPlaceholder: 'Add optional note...',
      showCancelButton: true,
      confirmButtonText: 'Yes, Received!',
      confirmButtonColor: '#10b981'
    }).then((result) => {
      if (result.isConfirmed) {
        const note = result.value || '';
        fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ po_id: id, note: note })
        }).then(r => r.json()).then(d => {
          if (d.success) {
            location.reload();
          } else {
            alert(d.message || 'Error updating status');
          }
        });
      }
    });
  } else {
    const note = prompt('Confirm received? Add optional note:') || '';
    if (note !== null) {
      fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id, note: note })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          location.reload();
        } else {
          alert(d.message || 'Error updating status');
        }
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
