@extends('layouts.admin')

@section('content')
<div style="padding:0.25rem 0 1rem 0;">
  <h2 style="margin-bottom:1.5rem;">📋 PO Received by System</h2>

  @if(empty($pageData['purchaseOrders']))
    <div class="card" style="padding:2rem; text-align:center;">
      <p style="color:var(--text-muted); margin:0;">No purchase requests yet. Users will submit them from their profiles.</p>
    </div>
  @else
  <div class="card" style="padding:1.2rem;">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Requested By</th>
            <th>Material</th>
            <th>Available Qty (kg)</th>
            <th>Order Quantity (kg)</th>
            <th style="min-width:280px; width:340px; padding-right:1.5rem;">Note</th>
            <th style="white-space:nowrap; padding-left:1.5rem; padding-right:2rem;">Status</th>
            <th style="padding-left:1.5rem;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['purchaseOrders'] as $po)
          <tr id="po-row-{{ $po->id }}">
            <td style="font-size:0.8rem; white-space:nowrap;">{{ $po->created_at->format('d M Y') }}</td>
            <td>
              <div style="font-weight:600;">{{ $po->user?->name }}</div>
              <div style="font-size:0.75rem; color:var(--text-muted);">{{ $po->user?->role }}</div>
            </td>
            <td style="font-weight:600;">{{ $po->product ? $po->product->formatName() : 'Unknown' }}</td>
            <td style="color:#10b981; font-weight:bold;">{{ number_format($po->product ? $po->product->totalAvailableStock() : 0, 1) }}</td>
            <td style="color:var(--primary-light); font-weight:bold;">{{ number_format($po->quantity, 1) }}</td>
            <td style="font-size:0.85rem; color:var(--text-muted); min-width:280px; width:340px; padding-right:1.5rem;">
              <div style="word-break:break-word; white-space:normal; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                {{ $po->note ?? '—' }}
              </div>
            </td>
            <td style="white-space:nowrap; padding-left:1.5rem; padding-right:2.5rem;">
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
            <td style="padding-left:1.5rem;">
              <div class="action-btns" style="display:flex; align-items:center; gap:0.4rem; flex-wrap:nowrap;">
                @if($po->status === 'PENDING')
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#eab308; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminApprovePO({{ $po->id }}, this)">
                    ✅ Mark as Read
                  </button>
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#3b82f6; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminOrderPO({{ $po->id }}, this)">
                    🛒 Mark as Order
                  </button>
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#ef4444; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminRejectPO({{ $po->id }}, this)">
                    ❌ Reject
                  </button>
                @elseif($po->status === 'READ')
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#3b82f6; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminOrderPO({{ $po->id }}, this)">
                    🛒 Mark as Order
                  </button>
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#ef4444; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminRejectPO({{ $po->id }}, this)">
                    ❌ Reject
                  </button>
                @elseif($po->status === 'ORDERED')
                  <button class="btn btn-sm" style="width:auto; padding:0.3rem 0.6rem; background:#ef4444; color:#fff; border:none; border-radius:4px; font-size:0.75rem; cursor:pointer;"
                    onclick="adminRejectPO({{ $po->id }}, this)">
                    ❌ Reject
                  </button>
                @endif
                <button class="btn-icon delete" onclick="adminDeletePO({{ $po->id }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
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
</div>

<script>
const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
function adminDeletePO(id) {
  Swal.fire({
    title: 'Are you sure?',
    text: "Delete this purchase request?",
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
          Swal.fire('Deleted!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
        }
      });
    }
  });
}

function adminApprovePO(id, btn) {
  Swal.fire({
    title: 'Mark as Read?',
    text: "This will acknowledge the request without modifying stock.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, mark as read'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Read!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '✅ Mark as Read';
        }
      });
    }
  });
}

function adminOrderPO(id, btn) {
  Swal.fire({
    title: 'Mark as Order?',
    text: "This will update the order status to ORDERED for the Stock Manager.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, mark as ordered'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Ordered!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '🛒 Mark as Order';
        }
      });
    }
  });
}

function adminRejectPO(id, btn) {
  Swal.fire({
    title: 'Reject Request?',
    text: "This will mark the purchase request as REJECTED.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, reject request'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Rejected!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '❌ Reject';
        }
      });
    }
  });
}

function adminReceivePO(id, btn) {
  Swal.fire({
    title: 'Mark as Received?',
    text: "This will acknowledge the physical receipt of the order.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, mark as received'
  }).then((result) => {
    if (result.isConfirmed) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
      fetch(window.baseUrl + '/' + window.userSlug + '/po/receive', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ po_id: id })
      }).then(r => r.json()).then(d => {
        if (d.success) {
          Swal.fire('Received!', d.message, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          Swal.fire('Error!', d.message || 'Error', 'error');
          btn.disabled = false;
          btn.textContent = '📦 Mark as Received';
        }
      });
    }
  });
}

</script>
@endsection
